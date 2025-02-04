<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by
    | the framework when an event needs to be broadcast. You may set this
    | to any of the connections defined in the "connections" array below.
    |
    | Supported: "pusher", "ably", "redis", "log", "null", "reverb"
    |
    */

    'default' => env('BROADCAST_DRIVER', 'reverb'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over websockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('qgnozyqbvjp5lxnv00rq'),
            'secret' => env('3bgezctew9dilhrhnweg'),
            'app_id' => env('R673226'),
            'options' => [
                'host' => env('localhost'),
                'port' => env('REVERB_PORT', 8080), // Default port 8080 for Reverb
                'scheme' => env('REVERB_SCHEME', 'http'), // Default to http, change to https if needed
                'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        // 'pusher' => [
        //     'driver' => 'pusher',
        //     'key' => env('f40285425e8036ff9290'),
        //     'secret' => env('af21f7d721c395351034'),
        //     'app_id' => env('1935759'),
        //     'options' => [
        //         'cluster' => env('mt1'),
        //         'useTLS' => true,
        //         'host' => '127.0.0.1',
        //         'port' => 6001,
        //         'scheme' => 'http',
        //     ],
        // ],
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => true,
            ],


        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
