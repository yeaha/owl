# Installation

## Composer

```json
{
    "require": {
        "yeaha/owl": "0.1.*"
    }
}
```

## Manual

```php
require 'src/autoload.php';
```

# [Hello world](https://github.com/yeaha/owl/tree/master/examples/hello_world)

## php-fpm + nginx

nginx.conf
```
server {
    listen              127.0.0.1:12345;
    root                /PATH/TO/examples/hello_world/public;
    index               index.php;

    location @default {
        include        fastcgi_params;
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_param  SCRIPT_FILENAME    /PATH/TO/examples/hello_word/index.php;
    }

    location / {
        try_files $uri @default;
    }
}
```

## swoole

require [swoole](https://github.com/swoole/swoole-src) extension

```
php -q examples/hello_world/server.php start
```
