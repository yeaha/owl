<?php
return [
    'services' => [
        'redis' => [
            'class' => '\Owl\Service\Predis',
            'parameters' => [
                'scheme' => 'tcp',
                'host' => '127.0.0.1',
                'port' => 6379,
                'persistent' => true,
                'timeout' => 3,
            ],
            'options' => [
                'exception' => true,
            ],
        ],
    ],
];
