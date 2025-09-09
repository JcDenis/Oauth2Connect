<?php

/**
 * @file
 * @brief       The plugin Oauth2Connect definition
 * @ingroup     Oauth2Connect
 *
 * @defgroup    Oauth2Connect Plugin cinecturlink2.
 *
 * Allow third party connection on frontend.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

$this->registerModule(
    'Oauth2 Connect',
    'Allow third party connection on frontend.',
    'Jean-Christian Paul Denis and Contributors',
    '0.3',
    [
        'requires'    => [
            ['core', '2.36'],
            ['FrontendSession', '0.34'],
        ],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-08-24T09:22:45+00:00',
    ]
);
