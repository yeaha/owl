<?php
namespace Jobs;

/**
 * 后台任务例子
 */
class Foobar extends \Model\Crontab {
    // 1分钟超时
    protected $timeout = 60;

    // 每分钟执行一次
    protected function testTimer() {
        // 每5分钟执行一次
        // return !((new \Datetime)->format('i') % 5);

        return true;
    }

    protected function execute() {
        $this->log('debug', 'foobar job run');

        sleep(10);
    }
}
