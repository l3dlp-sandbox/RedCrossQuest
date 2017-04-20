<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
        ],

        'db' => [
            'host'  => '127.0.0.1',
            'user'  => 'rcq',
            'pwd'   => 'rcq',
            'dbname'=> 'RCQ',

        ],
        'jwt' => [
          'secret'   => 'secret-rcq',
          'issuer'   => 'https://rcq.paquerette.com',
          'audience' => 'https://rcq.paquerette.com'
        ],
        'appSettings' => [
          'appUrl'        => 'http://localhost:3000/',
          'resetPwdPath'  => '#/resetPassword?key='

        ],
        'email' => [
          'server'    => 'smtp.server.com',
          'port'      => 465,
          'from'      => 'email@server.com',
          'username'  => 'login',
          'password'  => 'password'
        ]
    ],
];
