<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Oauth2Connect;

use ArrayObject;
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
        $config = [];
        if ($user_id != '') {
            $rs = App::credential()->getCredentials([
                'user_id'          => $user_id,
                'blog_id'          => App::blog()->id(), // by blog
                'credential_type'  => $this->getType($provider),
                'credential_value' => 'token', // not an id but a token selector
            ]);

            if ($rs->isEmpty()) {
                // Set an empty token
                if (App::auth()->userID() != '') {
                    $this->setToken($provider, $user_id);
                }
            } else {
                $config = $rs->getAllData();
            }
        }

        return new Token($config);
    }

    public function setToken(string $provider, string $user_id, ?Token $token= null): void
    {
        $this->delToken($provider, $user_id);

        $cur = App::credential()->openCredentialCursor();
        $cur->setField('user_id', $user_id);
        $cur->setField('blog_id', App::blog()->id()); // by blog
        $cur->setField('credential_type', $this->getType($provider));
        $cur->setField('credential_value', 'token'); // not an id but a token selector
        $cur->setField('credential_data', new ArrayObject($token?->getConfiguration() ?? []));

        App::credential()->setCredential((string) $user_id, $cur);
    }

    public function delToken(string $provider, string $user_id): void
    {
        App::credential()->delCredentials(
            $this->getType($provider),
            'token',
            $user_id,
            false // by blog
        );
    }

    public function getUser(string $provider, string $uid): User
    {
        $rs = App::credential()->getCredentials([
            'blog_id'          => App::blog()->id(), // by blog
            'credential_type'  => $this->getType($provider),
            'credential_value' => $uid,
        ]);

        $config = [];
        if (!$rs->isEmpty()) {
            $config = $rs->getAllData();
        }
        if ($config === []) {
            $config = [
                'user_id' => (string) App::auth()->userID(),
                'uid'     => $uid,
            ];
        }

        return new User($config);
    }

    public function setUser(string $provider, User $user, string $user_id): void
    {
        $this->delUser($provider, $user_id);

        $cur = App::credential()->openCredentialCursor();
        $cur->setField('user_id', $user_id);
        $cur->setField('blog_id', App::blog()->id()); // by blog
        $cur->setField('credential_type', $this->getType($provider));
        $cur->setField('credential_value', $user->get('uid'));
        $cur->setField('credential_data', new ArrayObject(array_merge($user->getConfiguration(), ['user_id' => $user_id])));

        App::credential()->setCredential((string) $user_id, $cur);
    }

    public function delUser(string $provider, string $user_id): void
    {
        $rs = App::credential()->getCredentials([
            'user_id'         => $user_id,
            'blog_id'         => App::blog()->id(), // by blog
            'credential_type' => $this->getType($provider),
        ]);

        while($rs->fetch()) {
            App::credential()->delCredentials(
                $this->getType($provider),
                (string) $rs->f('credential_value'),
                null, // do not take care of local user
                false // by blog
            );
        }

        // clear cache
        App::blog()->triggerBlog();
    }
}
