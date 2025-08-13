<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Oauth2Connect;

use Dotclear\App;
use Dotclear\Database\Statement\{ DeleteStatement, SelectStatement };
use Dotclear\Helper\Container\{ Factories, Factory };
use Dotclear\Helper\OAuth2\Client\{ Consumer, Store, Token, User };

/**
 * @brief       Oauth2Connect client store class.
 * @ingroup     Oauth2Connect
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class OAuth2Store extends Store
{
    public const CONTAINER_ID = 'FrontendOauth2Consumer';

    /**
     * Consumers stack.
     *
     * @var     array<string, Consumer>     $consumers
     */
    protected array $consumers = [];

    public function getConsumer(string $provider): Consumer
    {
        if (!isset($this->consumers[$provider])) {
            // consumer stores in blog settings
            $consumer = json_decode((string) My::settings()->get($provider . '_consumer'), true);
            if (!is_array($consumer) || ($consumer['provider'] ?? '') != $provider) {
                $consumer = ['provider' => $provider];
            }

            $this->consumers[$provider] = new Consumer($consumer);
        }

        return $this->consumers[$provider];
    }

    public function setConsumer(string $provider, string $key = '', string $secret = '', string $domain = ''): void
    {
        $config = [
            'provider' => $provider,
            'key'      => $key,
            'secret'   => $secret,
            'domain'   => $domain,
        ];

        // consumer stores in blog settings
        My::settings()->put($provider . '_consumer', json_encode($config), 'string');
        $this->consumers[$provider] = new Consumer($config);
    }

    public function getToken(string $provider, string $user_id): Token
    {
        $res = [];
        if ($user_id != '') {
            $rs = App::credential()->getCredentials([
                'credential_type' => $this->getType($provider, true),
                'credential_id'   => $user_id,
                'user_id'         => $user_id,
            ]);

            if ($rs->isEmpty()) {
                // Set an empty token
                if (App::auth()->userID() != '') {
                    $this->setToken($provider, $user_id);
                }
            } else {
                $res = json_decode((string) $rs->f('credential_data'), true);
                if (!is_array($res)) {
                    $res = [];
                }
            }
        }

        return new Token($res);
    }

    public function setToken(string $provider, string $user_id, ?Token $token= null): void
    {
        $this->delToken($provider, $user_id);

        $cur = App::credential()->openCredentialCursor();
        $cur->setField('credential_type', $this->getType($provider, true));
        $cur->setField('credential_id', $user_id);
        $cur->setField('user_id', $user_id);
        $cur->setField('credential_data', json_encode($token?->getConfiguration() ?? []));

        App::credential()->setCredential((string) $user_id, $cur);
    }

    public function delToken(string $provider, string $user_id): void
    {
        App::credential()->delCredential(
            $this->getType($provider, true),
            $user_id
        );
    }

    public function getUser(string $provider, string $uid): User
    {
        $rs = App::credential()->getCredentials([
            'credential_type' => $this->getType($provider, false),
            'credential_id'   => $uid,
        ]);

        $res = [];
        if (!$rs->isEmpty()) {
            $res = json_decode((string) $rs->f('credential_data'), true);
        }
        if (!is_array($res) || $res === []) {
            $res = [
                'user_id' => (string) App::auth()->userID(),
                'uid'     => $uid,
            ];
        }

        return new User($res);
    }

    public function setUser(string $provider, User $user, string $user_id): void
    {
        $this->delUser($provider, $user_id);

        $cur = App::credential()->openCredentialCursor();
        $cur->setField('credential_type', $this->getType($provider, false));
        $cur->setField('credential_id', $user->get('uid'));
        $cur->setField('user_id', $user_id);
        $cur->setField('credential_data', json_encode(array_merge($user->getConfiguration(), ['user_id' => $user_id])));

        App::credential()->setCredential((string) $user_id, $cur);
    }

    public function delUser(string $provider, string $user_id): void
    {
        $rs = App::credential()->getCredentials([
            'credential_type' => $this->getType($provider, false),
            'user_id'         => $user_id,
        ]);

        while($rs->fetch()) {
            $res = json_decode($rs->f('credential_data'), true);
            App::credential()->delCredential(
                $this->getType($provider, false),
                $res['uid'] ?? ''
            );
        }

        // clear cache
        App::blog()->triggerBlog();
    }
}
