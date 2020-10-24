<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | This value determines which of the following gateway to use.
    | You can switch to a different driver at runtime.
    |
    */
    'default' => 'textlocal',

    /*
    |--------------------------------------------------------------------------
    | List of Drivers
    |--------------------------------------------------------------------------
    |
    | These are the list of drivers to use for this package.
    | You can change the name. Then you'll have to change
    | it in the map array too.
    |
    */
    'drivers' => [
        // Install: composer require aws/aws-sdk-php
        'sns' => [
            'key' => 'Your AWS SNS Access Key',
            'secret' => 'Your AWS SNS Secret Key',
            'region' => 'Your AWS SNS Region',
            'sender' => 'Your AWS SNS Sender ID',
            'type' => 'Tansactional', // Or: 'Promotional'
        ],
        'textlocal' => [
            'url' => 'http://api.textlocal.in/send/', // Country Wise this may change.
            'username' => 'Your Username',
            'hash' => 'Your Hash',
            'sender' => 'Sender Name',
        ],
        // Install: composer require twilio/sdk
        'twilio' => [
            'sid' => 'Your SID',
            'token' => 'Your Token',
            'from' => 'Your Default From Number',
        ],
        // Install: composer require mediaburst/clockworksms
        'clockwork' => [
            'key' => 'Your clockwork API Key',
        ],
        'linkmobility' => [
            'url' => 'http://simple.pswin.com', // Country Wise this may change.
            'username' => 'Your Username',
            'password' => 'Your Password',
            'sender' => 'Sender name',
        ],
        // Install: composer require melipayamak/php
        'melipayamak' => [
            'username' => 'Your Username',
            'password' => 'Your Password',
            'from' => 'Your Default From Number',
            'flash' => false,
        ],
        // Install: composer require kavenegar/php
        'kavenegar' => [
            'apiKey' => 'Your Api Key',
            'from' => 'Your Default From Number',
        ],
        'smsir' => [
            'url' => 'https://ws.sms.ir/',
            'apiKey' => 'Your Api Key',
            'secretKey' => 'Your Secret Key',
            'from' => 'Your Default From Number',
        ],
        'tsms' => [
            'url' => 'http://www.tsms.ir/soapWSDL/?wsdl',
            'username' => 'Your Username',
            'password' => 'Your Password',
            'from' => 'Your Default From Number',
        ],
        'farazsms' => [
            'url' => '188.0.240.110/services.jspd',
            'username' => 'Your Username',
            'password' => 'Your Password',
            'from' => 'Your Default From Number',
        ],
        'smsgatewayme' => [
            'apiToken' => 'Your Api Token',
            'from' => 'Your Default Device ID',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Maps
    |--------------------------------------------------------------------------
    |
    | This is the array of Classes that maps to Drivers above.
    | You can create your own driver if you like and add the
    | config in the drivers array and the class to use for
    | here with the same name. You will have to extend
    | Shetabit\Sms\Abstracts\Driver in your driver.
    |
    */
    'map' => [
        'sns' => \Shetabit\Sms\Drivers\Sns::class,
        'textlocal' => \Shetabit\Sms\Drivers\Textlocal::class,
        'twilio' => \Shetabit\Sms\Drivers\Twilio::class,
        'clockwork' => \Shetabit\Sms\Drivers\Clockwork::class,
        'linkmobility' => \Shetabit\Sms\Drivers\Linkmobility::class,
        'melipayamak' => \Shetabit\Sms\Drivers\Melipayamak::class,
        'kavenegar' => \Shetabit\Sms\Drivers\Kavenegar::class,
        'smsir' => \Shetabit\Sms\Drivers\Smsir::class,
        'tsms' => \Shetabit\Sms\Drivers\Tsms::class,
        'farazsms' => \Shetabit\Sms\Drivers\Farazsms::class,
        'smsgatewayme' => \Shetabit\Sms\Drivers\SmsGatewayMe::class,
    ]
];
