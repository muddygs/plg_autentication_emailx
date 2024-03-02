<?php

/**
 * Noting original copyright ownership prior to update to Joomla 4
 * package		plg_auth_email
 * copyright	Copyright (C) 2005 - 2011 Michael Richey. All rights reserved.
 * license		GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace ClawCorp\Plugin\Authentication\Emailx\Extension;

use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Event\User\AuthenticationEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * @package ClawCorp\Plugin\Authentication\Emailx\Extension 
 * @since  Joomla 5.0
 * @version 5.0.0
 */
final class Emailx extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;
    use UserFactoryAwareTrait;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   5.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return ['onUserAuthenticate' => 'onUserAuthenticate'];
    }

    // The following properties are initialized by CMSPlugin::__construct()
    protected $db;
    protected $app;
    protected $autoloadLanguage = true;

    private \Joomla\Plugin\Authentication\Joomla\Extension\Joomla $authPlugin;

    public function __construct(DispatcherInterface $dispatcher, $config = [])
    {
        parent::__construct($dispatcher, $config);
        $paj = PluginHelper::getPlugin('authentication', 'joomla');
        $this->authPlugin = new \Joomla\Plugin\Authentication\Joomla\Extension\Joomla($dispatcher, (array)$paj);
    }

    /**
     * This method should handle any authentication and report back to the subject
     */
    // function onUserAuthenticate(AuthenticationEvent $event)
    function onUserAuthenticate(&...$params)
    {
        $isJ4 = true;
        $joomlaVersion = new \Joomla\CMS\Version;
        if ($joomlaVersion->isCompatible('5.0.0')) {
            $isJ4 = false;
        }

        if ( $isJ4 ) {
            // Joomla 4: function onUserAuthenticate(&$credentials, $options, &$response)
            /** @var array */
            $credentials = $params[0];
            /** @var array */
            $options     = $params[1];
            /** @var object */
            $response    = $params[2];
        } else {
            // Joomla 5: function onUserAuthenticate(AuthenticationEvent $event)
            /** @var AuthenticationEvent */
            $event = $params[0];
            $credentials = $event->getCredentials();
            $response    = $event->getAuthenticationResponse();
            $options     = $event->getOptions();
        }

        $query = $this->db->getQuery(true);

        $username = $this->app->input->post->get('username', false, 'RAW');
        $query->select('username')
            ->from('#__users')
            ->where('block = 0')
            ->where('UPPER(email) = UPPER(' . $this->db->Quote($username) . ')');

        $this->db->setQuery($query);
        $username = $this->db->loadResult();

        if ($username) {

            // Update variables and properties and use stock authentication plugin
            if ( $isJ4 ) {
                $credentials['username'] = $username;
                $this->authPlugin->setDatabase($this->db);
                $this->authPlugin->setApplication($this->app);
                $this->authPlugin->onUserAuthenticate($credentials, $options, $response);
            } else {
                $credentials['username'] = $username;
                $this->authPlugin->setDatabase($this->db);
                $this->authPlugin->setApplication($this->app);
                $this->authPlugin->setUserFactory($this->getUserFactory());

                $authenticationEvent = new AuthenticationEvent('onUserAuthenticate', [
                    'credentials' => $credentials,
                    'options'     => $options,
                    'subject'     => $response,
                ]);
                $this->authPlugin->onUserAuthenticate($authenticationEvent);
            }

            if ($response->status === \Joomla\CMS\Authentication\Authentication::STATUS_SUCCESS) {
                $response->username = $username;
            }
        } else {
            $response->status = Authentication::STATUS_FAILURE;
            $response->error_message = $this->getApplication()->getLanguage()->_('JGLOBAL_AUTH_INVALID_PASS');
        }
    }
}
