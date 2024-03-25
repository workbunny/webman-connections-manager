<?php

use Illuminate\Redis\Connections\Connection;
use Workbunny\WebmanRqueue\Headers;

return [
    'enable'                 => true,
    // manager-server port
    'server_port'            => 7701,
    // connection event builder config
    'connection_event_builder' => [
        'interval'          => 1.0,
        'prefetch_count'    => 1,
        'queue_size'        => 0,
        'pending_timeout'   => 0,
        'handler'           => function (Headers $headers, array $data, Connection $connection) {
//            // 示例 使用webhook通知
//            $client = new \GuzzleHttp\Client([
//                'base_uri' => 'http://xxx.xx.com',
//                'timeout'  => 60
//            ]);
//            $client->post('/webhook', [
//                \GuzzleHttp\RequestOptions::JSON => [
//                    'headers' => $headers,
//                    'data'    => $data
//                ]
//            ]);
        }
    ],
    // manager client
    'client' => [
        'timeout' => 120,
        'host'    => 'http://127.0.0.1:7701'
    ]
];