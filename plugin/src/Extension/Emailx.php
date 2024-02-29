<?php

/**
 * Noting original copyright ownership prior to update to Joomla 4
 * package		plg_auth_email
 * copyright	Copyright (C) 2005 - 2011 Michael Richey. All rights reserved.
 * license		GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Authentication\Emailx\Extension;

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
 * @package Joomla\Plugin\Authentication\Emailx\Extension 
 * @author Merrill Squiers
 * @since  Joomla 4.0
 * @version 4.0.0
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

    private \Joomla\Plugin\Authentication\Joomla\Extension\Joomla $_paj;

    public function __construct(DispatcherInterface $dispatcher, $config = [])
    {
        parent::__construct($dispatcher, $config);
        $paj = PluginHelper::getPlugin('authentication', 'joomla');
        $this->_paj = new \Joomla\Plugin\Authentication\Joomla\Extension\Joomla($dispatcher, (array)$paj);
    }

    /**
     * This method should handle any authentication and report back to the subject
     */
    function onUserAuthenticate(AuthenticationEvent $event)
    {
        $credentials = $event->getCredentials();
        $response    = $event->getAuthenticationResponse();
        $options     = $event->getOptions();

        $query = $this->db->getQuery(true);

        $username = $this->app->input->post->get('username', false, 'RAW');
        $query->select('username')
            ->from('#__users')
            ->where('block = 0')
            ->where('UPPER(email) = UPPER(' . $this->db->Quote($username) . ')');

        $this->db->setQuery($query);
        $username = $this->db->loadResult();

        if ($username) {
            // why mess with re-creating authentication - just use the system.
            $credentials['username'] = $username;
            $this->_paj->setDatabase($this->db);
            $this->_paj->setApplication($this->app);
            $this->_paj->setUserFactory($this->getUserFactory());

            $authenticationEvent = new AuthenticationEvent('onUserAuthenticate', [
                'credentials' => $credentials,
                'options'     => $options,
                'subject'     => $response,
            ]);
            $this->_paj->onUserAuthenticate($authenticationEvent);
            $response->username = $username;
        } else {
            $response->status = Authentication::STATUS_FAILURE;
            $response->error_message = $this->getApplication()->getLanguage()->_('JGLOBAL_AUTH_INVALID_PASS');
        }
    }
}
