<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Oauth2Connect;

use Dotclear\App;
use Dotclear\Core\Backend\Helper;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\OAuth2\Client\{ Client, Provider };
use Dotclear\Helper\OAuth2\Client\Exception\InvalidUser;
use Dotclear\Schema\OAuth2\{ Auth0Connect, GithubConnect, GoogleConnect, Lwa, SlackConnect };
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
        App::frontend()->session()->start();
    }

    protected function requestActionError(Exception $e): bool
    {
        if (is_a($e, InvalidUser::CLASS)) {
            App::frontend()->context()->frontend_session->addError(
                __('This user is not registered on this blog.') . " \n" .
                __('You must have a valid account and authorizsed application connection.')
            );

            return true;
        }

        return false;
    }

    protected function checkUser(string $user_id): bool
    {
        if (App::auth()->checkUser($user_id, null, null, false) 
            && App::frontend()->context()->frontend_session?->check($user_id)
        ) {
            App::blog()->triggerBLog();

            return true;
        }

        return false;
    }

    public function getDisabledProviders(): array
    {
        $disabled = json_decode((string) App::blog()->settings()->get(self::CONTAINER_ID)->get('disabled_providers'));

        return is_array($disabled) ? $disabled : [];
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
