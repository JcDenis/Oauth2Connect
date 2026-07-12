<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Oauth2Connect;

use Dotclear\App;
use Dotclear\Core\Backend\Helper;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\OAuth2\Client\Client;
use Dotclear\Helper\OAuth2\Client\Provider;
use Dotclear\Helper\OAuth2\Client\Exception\InvalidUser;
use Dotclear\Plugin\FrontendSession\FrontendSession;
use Dotclear\Schema\OAuth2\Auth0Connect;
use Dotclear\Schema\OAuth2\GithubConnect;
use Dotclear\Schema\OAuth2\GoogleConnect;
use Dotclear\Schema\OAuth2\Lwa;
use Dotclear\Schema\OAuth2\SlackConnect;
use Exception;

/**
 * @brief       Oauth2Connect client helper.
 * @ingroup     Oauth2Connect
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class OAuth2Client extends Client
{
    protected function getDefaultServices(): array
    {
        return [    // @phpstan-ignore-line
            GoogleConnect::PROVIDER_ID => GoogleConnect::CLASS,
            GithubConnect::PROVIDER_ID => GithubConnect::CLASS,
            SlackConnect::PROVIDER_ID  => SlackConnect::CLASS,
            Auth0Connect::PROVIDER_ID  => Auth0Connect::CLASS,
            Lwa::PROVIDER_ID           => Lwa::CLASS,
        ];
    }

    protected function checkSession(): void
    {
        App::session()->start();
    }

    protected function requestActionError(Exception $e): bool
    {
        if (is_a($e, InvalidUser::CLASS)) {
            $fs = App::frontend()->context()->__get('frontend_session');
            if ($fs instanceof FrontendSession) {
                $fs->addError(
                    __('This user is not registered on this blog.') . " \n" .
                    __('You must have a valid account and authorizsed application connection.')
                );
            }

            return true;
        }

        return false;
    }

    protected function checkUser(string $user_id): bool
    {
        if (App::auth()->checkUser($user_id, null, null, false)) {
            $fs = App::frontend()->context()->__get('frontend_session');
            if (($fs instanceof FrontendSession) && $fs->check($user_id)) {
                App::blog()->triggerBlog();
            }

            return true;
        }

        return false;
    }

    public function getDisabledProviders(): array
    {
        $disabled = App::blog()->settings()->get(self::CONTAINER_ID)->get('disabled_providers');
        $disabled = json_decode(is_string($disabled) ? $disabled : '');

        return is_array($disabled) ? array_filter($disabled, fn (mixed $v): bool => is_string($v)) : [];
    }

    public function setDisabledProviders(array $providers): void
    {
        App::blog()->settings()->get(self::CONTAINER_ID)->put('disabled_providers', json_encode($providers), 'string');
    }

    public function getProviderLogo(Provider $provider): string
    {
        return (new Img(My::fileURL(sprintf('img/%s.svg', $provider->getId()))))
            ->width(24)
            ->height(24)
            ->render();
    }
}
