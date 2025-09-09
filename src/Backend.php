<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Oauth2Connect;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief       Oauth2Connect backend class.
 * @ingroup     Oauth2Connect
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Backend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem();

        return true;
    }
}
