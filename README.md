# Installation

## Composer

```json
{
    "require": {
        "yeaha/owl": "1.0.*"
    }
}
```

# [Hello world](https://github.com/yeaha/owl-site)

## install
```
composer create-project yeaha/owl-site ./mysite
```

## php-fpm + nginx

nginx.conf
```
server {
    listen              127.0.0.1:12345;
    root                /PATH/TO/mysite/public;
    index               index.php;

    location @default {
        include        fastcgi_params;
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_param  SCRIPT_FILENAME    /PATH/TO/mysite/index.php;
    }

    location / {
        try_files $uri @default;
    }
}
```

## swoole

require [swoole](https://github.com/swoole/swoole-src) extension

```
php -q /PATH/TO/mysite/server.php start
```
