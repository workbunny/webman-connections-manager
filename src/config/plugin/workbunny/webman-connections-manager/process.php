<?php
declare(strict_types=1);

use Workbunny\WebmanConnectionsManager\Connection;
use Workbunny\WebmanConnectionsManager\Server;
use Workbunny\WebmanConnectionsManager\Process\ConnectionEventBuilder;

return [
    // 连接管理服务
    'connections-manager-server' => [
        'handler'   => Server::class,
        'listen'    => 'http://0.0.0.0:7701',
        'reusePort' => true,
        'constructor' => [
            'connectionCallback' => function (Connection $connection) {
//                // 设置ssl
//                $connection->transport  = 'ssl';
//                $connection->headers    = [
//                    'cookie'        => "xxxxxxx",
//                    'accept'        => '*/*',
//                    'user-agent'    => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
//                ];
//                // 设置心跳
//                $connection->setHeartbeatTimer(60, function () use ($connection) {
//                    $connection->send('ping');
//                });
                return $connection;
            },
        ]
    ],
    // 连接事件回调
    'connection-event-builder' => [
        'handler' => ConnectionEventBuilder::class,
        'count'   => cpu_count(),
        'mode'    => 'queue',
    ]
];