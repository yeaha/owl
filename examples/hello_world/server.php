<?php
define('SITE_MODE', true);

require __DIR__.'/boot.php';

$command = isset($argv[1]) ? $argv[1] : '';

switch ($command) {
    case 'start':
        __start();
        break;
    case 'stop':
        __stop();
        break;
    case 'status':
        __status();
        break;
    case 'reload':
        __reload();
        break;
    default:
        echo "usage: php -q server.php [start|stop|reload|status]\n";
        exit(1);
}

exit(0);

////////////////////////////////////////////////////////////////////////////////

function __start() {
    if ($pid = __getpid()) {
        echo sprintf("other server run at pid %d\n", $pid);
        exit(1);
    }

    echo "server start\n";

    $config = __getconfig();

    $app = __get_swoole_app($config);
    $app->start();
}

function __stop() {
    if (!$pid = __getpid()) {
        echo "server not run\n";
        exit(1);
    }

    posix_kill($pid, SIGTERM);
    echo "server stoped\n";
}

function __status() {
    if ($pid = __getpid()) {
        echo sprintf("server run at pid %d\n", $pid);
    } else {
        echo "server not run\n";
    }
}

function __reload() {
    if (!$pid = __getpid()) {
        echo "server not run\n";
        exit(1);
    }

    posix_kill($pid, SIGUSR1);
    echo "server reloaded\n";
}

function __getconfig() {
    static $config;

    $add_path = function($file) {
        if (substr($file, 0, 1) === '/') {
            return $file;
        }

        return ROOT_DIR.'/'.$file;
    };

    if (!$config) {
        $config = parse_ini_file(ROOT_DIR.'/server.ini', true);

        if (isset($config['server']['pid_file'])) {
            $config['server']['pid_file'] = $add_path($config['server']['pid_file']);
        }

        if (isset($config['swoole_setting']['log_file'])) {
            $config['swoole_setting']['log_file'] = $add_path($config['swoole_setting']['log_file']);
        }
    }

    return $config;
}

function __getpid() {
    $config = __getconfig();

    $pid_file = $config['server']['pid_file'];

    $pid = file_exists($pid_file) ? file_get_contents($pid_file) : 0;

    // 检查进程是否真正存在
    if ($pid && !posix_kill($pid, 0)) {
        $errno = posix_get_last_error();

        if ($errno === 3) {
            $pid = 0;
        }
    }

    return $pid;
}
