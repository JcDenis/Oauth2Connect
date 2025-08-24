<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Oauth2Connect;

use ArrayObject, Throwable;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\{ Li, Para, Ul };
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\OAuth2\Client\Exception\InvalidUser;
use Dotclear\Plugin\FrontendSession\FrontendSessionProfil;

/**
 * @brief       Oauth2Connect module frontend process.
 * @ingroup     Oauth2Connect
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Frontend extends Process
{
    /**
     * oauth2 client instance.
     *
     * @var     null|OAuth2Client   $oauth2
     */
    private static ?OAuth2Client $oauth2 = null;

    /**
     * Get uniq frontend oAuth2 client instance.
     */
    private static function oauth2(): ?OAuth2Client
    {
        if (self::$oauth2 === null) {
            // Create oAuth2 client instance
            try {
                self::$oauth2 = new OAuth2Client(new OAuth2Store(App::blog()->url() . App::url()->getURLFor('FrontendSession')));
            } catch (Throwable) {

            }
        }

        return self::$oauth2;
    }

    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {

            return false;
        }

        L10n::set(implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'locales', App::lang()->getLang(), 'public']));

        App::behavior()->addBehaviors([
            /**
             * Do oauth flow actions.
             */
            'FrontendSessionServeTemplate' => function (): string {
                if (self::oauth2() !== null) {
                    self::oauth2()->requestAction((string) App::auth()->userID());
                }

                return '';
            },

            /**
             * Add session page user settings form.
             */
            'FrontendSessionProfil' => function (FrontendSessionProfil $profil): void {
                $oauth2_items = [];
                if (self::oauth2() !== null) {
                    foreach (self::oauth2()->services()->getProviders() as $oauth2_service) {
                        if (self::oauth2()->services()->hasDisabledProvider($oauth2_service::getId())
                            || !self::oauth2()->store()->hasConsumer($oauth2_service::getId())
                        ) {
                            continue;
                        }
                        $link = self::oauth2()->getActionButton((string) App::auth()->userID(), $oauth2_service::getId(), Http::getSelfURI(), true);
                        if ($link !== null) {
                            $oauth2_items[] = (new Li())->items([$link]);
                        }
                    }
                }

                if ($oauth2_items !== []) {
                    $profil->addAction(My::id(), __('Third party connections'), [(new Ul())->items($oauth2_items)]);
                }
            },

            /**
             * Add session widget user menu.
             *
             * @param   ArrayObject<int, Li>    $lines
             */
            'FrontendSessionWidget'        => function (ArrayObject $lines, string $redir): void {
                if (self::oauth2() !== null && !App::auth()->userID()) {
                    foreach (self::oauth2()->services()->getProviders() as $oauth2_service) {
                        if (self::oauth2()->services()->hasDisabledProvider($oauth2_service::getId())
                            || !self::oauth2()->store()->hasConsumer($oauth2_service::getId())
                        ) {
                            continue;
                        }
                        $link = self::oauth2()->getActionButton((string) App::auth()->userID(), $oauth2_service::getId(), Http::getSelfURI());
                        if ($link !== null) {
                            $lines[] = (new Li())
                                ->items([$link]);
                        }
                    }
                }
            },
        ]);

        return true;
    }
}
