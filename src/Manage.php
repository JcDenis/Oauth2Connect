<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Oauth2Connect;

use Exception;
use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;

/**
 * @brief       Oauth2Connect manage class.
 * @ingroup     Oauth2Connect
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Manage
{
    use TraitProcess;

    /**
     * Oauth2 client instance.
     *
     * @var     null|OAuth2Client    $oauth2
     */
    private static $oauth2 = null;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Create oAuth2 client instance
        try {
            self::$oauth2 = new OAuth2Client(new OAuth2Store(App::blog()->url()));
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        if (!empty($_POST) && self::$oauth2 !== null) {
            $disabled = self::$oauth2->getDisabledProviders();

            foreach (self::$oauth2->services()->getProviders() as $id => $class) {
                if (($_POST['provider'] ?? '') == $id) {
                    $key = array_search($id, $disabled);
                    if (!empty($_POST[$id . 'disable'])) {
                        if ($key === false) {
                            // disable provider
                            $disabled[] = $id;
                        }
                    } elseif (!empty($_POST[$id . 'enable'])) {
                        if ($key !== false) {
                            // enable provider
                            unset($disabled[$key]);
                        }
                    } else {
                        // save provider
                        self::$oauth2->store()->setConsumer(
                            $id,
                            $_POST[$id . 'key'] ?? '',
                            $_POST[$id . 'secret'] ?? '',
                            $_POST[$id . 'domain'] ?? ''
                        );
                    }
                }
            }

            self::$oauth2->setDisabledProviders($disabled);

            Notices::addSuccessNotice(__('Consumer successfully updated.'));
            My::redirect();
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $items = [];
        if (self::$oauth2 !== null) {
            $disabled = self::$oauth2->getDisabledProviders();
            foreach (self::$oauth2->services()->getProviders() as $id => $class) {
                if (in_array($id, $disabled)) {
                    // disabled provider
                    $items[] = (new Fieldset())
                        ->legend(new Legend($class::getName()))
                        ->items([
                            (new Form($id . '_config'))
                                ->method('post')
                                ->action(My::manageUrl())
                                ->fields([
                                    (new Text('p', $class::getDescription())),
                                    (New Para())
                                        ->items([
                                            (new Link())
                                                ->href($class::getConsoleUrl())
                                                ->title(__('Go to application console'))
                                                ->text(__('Application console'))
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Submit($id . 'enable'))
                                                ->class('add')
                                                ->value(__('Enable')),
                                            ... My::hiddenFields(['provider' => $id]),
                                        ]),
                                ]),
                        ]);
                } else {
                    // enabled provider
                    $consumer = self::$oauth2->store()->getConsumer($id);
                    $provider = self::$oauth2->services()->getProvider($consumer);

                    $items[] = (new Fieldset())
                        ->legend(new Legend(self::$oauth2->getProviderLogo($provider) . ' ' .$class::getName()))
                        ->items([
                            (new Form($id . '_config'))
                                ->method('post')
                                ->action(My::manageUrl())
                                ->fields([
                                    (new Text('p', $class::getDescription())),
                                    (New Para())
                                        ->items([
                                            (new Link())
                                                ->href($class::getConsoleUrl())
                                                ->title(__('Go to application console'))
                                                ->text(__('Application console'))
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Input($id . 'key'))
                                                ->class('maximal')
                                                ->size(65)
                                                ->maxlength(255)
                                                ->value($consumer->get('key'))
                                                ->label(new Label(__('Application key:'), Label::OL_TF)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Input($id . 'secret'))
                                                ->class('maximal')
                                                ->size(65)
                                                ->maxlength(255)
                                                ->value($consumer->get('secret'))
                                                ->label(new Label(__('Application secret:'), Label::OL_TF)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Input($id . 'domain'))
                                                ->class('maximal')
                                                ->size(65)
                                                ->maxlength(255)
                                                ->value($consumer->get('domain'))
                                                ->label(new Label(__('Application domain:'), Label::OL_TF)),
                                        ]),

                                    (new Para())
                                        ->separator(' ')
                                        ->items([
                                            (new Submit($id . 'action'))
                                                ->value(__('Save')),
                                            (new Submit($id . 'disable'))
                                                ->class('delete')
                                                ->value(__('Disable')),
                                            ... My::hiddenFields(['provider' => $id]),
                                        ]),
                                ]),
                        ]);
                }
            }
        }

        if (empty($items)) {
            $items[] = (new Text('p', __('No service provider to configure')));
        } else {
            array_unshift($items, (new Div())
                ->class('static-msg')
                ->items([
                    (new Text('p', __('Rules:'))),
                    (new Ul())
                        ->items([
                            (new li())
                                ->text(__('Your blog MUST be publically accessible. (No IP nor local network)')),
                            (new li())
                                ->text(__('You blog MUST be secured using HTTPS.')),
                            (new li())
                                ->text(__('You MUST have a valid third party application. (see console link)')),
                            (new li())
                                ->text(sprintf(__('Plugin %s MUST be configured and enabled.'), __('Frontend sessions'))),
                            (new li())
                                ->text(sprintf(__('You MUST set blog URL "%s" as callback URL in your application.'), App::blog()->url() . App::url()->getBase('FrontendSession'))),
                        ]),
                ]),
            );
        }

        Page::openModule(
            My::name(),
            ''
        );

        echo
        Page::breadcrumb([
            __('Plugins') => '',
            My::name()    => My::manageUrl(),
        ]) .
        Notices::getNotices() .
        (new Div())
            ->items($items)
            ->render();

        Page::closeModule();
    }

}
