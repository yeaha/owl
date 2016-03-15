<?php

namespace Owl;

abstract class Crontab
{
    use \Owl\Traits\Context;

    const KEY_PROC_ID = '__proc_id__';
    const KEY_PROC_TIME = '__proc_start__';

    protected $name;

    protected $timeout = 600;

    abstract protected function execute();

    private static $logger;

    /**
     * 执行任务
     *
     * @return bool
     */
    public function start()
    {
        $try = $this->tryStart();
        $this->log('debug', 'try start', [
            'result' => (int) $try,
        ]);

        if (!$try) {
            return false;
        }

        $this->log('info', 'Job start');

        // 把进程ID和开始时间记录到上下文中
        $this->setContext(self::KEY_PROC_ID, posix_getpid());
        $this->setContext(self::KEY_PROC_TIME, time());
        $this->saveContext();

        // 执行任务逻辑
        try {
            $this->execute();
        } catch (\Exception $ex) {
            $this->log('error', 'Job execute error', [
                'error' => $ex->getMessage(),
            ]);

            return false;
        }

        $this->stop();

        return true;
    }

    /**
     * 任务执行完毕.
     */
    public function stop()
    {
        $this->removeContext(self::KEY_PROC_ID);
        $this->removeContext(self::KEY_PROC_TIME);
        $this->saveContext();

        $this->log('info', 'Job execute completed');
    }

    /**
     * 尝试开始任务
     *
     * @return bool
     */
    protected function tryStart()
    {
        // 检查是否达到预定时间
        try {
            if (!$this->testTimer()) {
                return false;
            }
        } catch (\Exception $ex) {
            $this->log('error', 'Job testTimer() error', [
                'error' => $ex->getMessage(),
            ]);

            return false;
        }

        // 上下文中是否保存了前一个任务pid
        if (!$recent_proc_id = $this->getContext(self::KEY_PROC_ID)) {
            return true;
        }

        // 检查进程ID是否真正存在
        if (!posix_kill($recent_proc_id, 0)) {
            $errno = posix_get_last_error();

            if ($errno === 3) {
                return true;
            }

            $this->log('warning', 'Job kill error', [
                'error' => posix_strerror($errno),
            ]);

            return false;
        }

        // 如果上一个任务还没有超时就放弃当前任务
        $recent_proc_time = $this->getContext(self::KEY_PROC_TIME);
        if (time() - $recent_proc_time < $this->timeout) {
            $this->log('notice', 'Job cancel, previous job still run', [
                'previous_proc_id' => $recent_proc_id,
            ]);

            return false;
        }

        // 中止超时任务
        posix_kill($recent_proc_id, SIGKILL);
        $this->log('warning', 'Job killed by timeout', [
            'previous_proc_id' => $recent_proc_id,
        ]);

        return true;
    }

    /**
     * 是否达到任务可执行时间.
     *
     * @return bool
     */
    protected function testTimer()
    {
        return false;
    }

    protected function log($level, $message, array $context = [])
    {
        if ($logger = self::$logger) {
            $defaults = [
                'class' => get_class($this),
            ];

            if ($this->name) {
                $defaults['name'] = $this->name;
            }

            $context = array_merge($defaults, $context);

            $logger->log($level, $message, $context);
        }
    }

    protected function getName()
    {
        return $this->name ?: get_class($this);
    }

    public static function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        self::$logger = $logger;
    }
}
