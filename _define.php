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

$id = 'Oauth2Connect';

$this->registerModule(
    'Oauth2 Connect',
    'Allow third party connection on frontend.',
    'Jean-Christian Paul Denis and Contributors',
    '0.3.1',
    [
        'requires'    => [
            ['core', '2.39'],
            ['FrontendSession', '0.34'],
        ],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $id . '/master/dcstore.xml',
        'date'        => '2025-09-13T15:56:02+00:00',
    ]
);
