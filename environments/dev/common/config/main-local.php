<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=procreatlab.com;dbname=publishefficientYii',
            'username' => 'root',
            'password' => 'Yte0nmtfkz',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
            ],
        ],

        'log' => [

            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error'],
                    'categories' => ['facebook'],
                    'logFile' => '@app/runtime/logs/facebook/error.log',
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error'],
                    'categories' => ['facebookCron'],
                    'logFile' => '@app/runtime/logs/facebookCron/error.log',
                ],

            ],
        ],
    ],
    'modules' => [
        'utility' => [
            'class' => 'c006\utility\migration\Module',
        ],
    ],
];
