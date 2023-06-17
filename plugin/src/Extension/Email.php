<?php

/**
 * @version		$Id: email.php 20196 2011-03-04 02:40:25Z mrichey $
 * @package		plg_auth_email
 * @copyright	Copyright (C) 2005 - 2011 Michael Richey. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Authentication\Email\Extension;

use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseAwareTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

final class Email extends CMSPlugin
{
    use DatabaseAwareTrait;
    
    private $_paj;

    public function __construct(&$subject, $config = [])
    {
        parent::__construct($subject, $config);
        // require_once JPATH_PLUGINS . '/authentication/joomla/joomla.php';
        $paj = PluginHelper::getPlugin('authentication', 'joomla');
        $this->_paj = new \Joomla\Plugin\Authentication\Joomla\Extension\Joomla($subject, (array)$paj);
    }

    /**
     * This method should handle any authentication and report back to the subject
     */
    function onUserAuthenticate(&$credentials, $options, &$response)
    {
        // Get a database object
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $username = Factory::getApplication()->input->post->get('username', false, 'RAW');
        $query->select('id, username, password');
        $query->from('#__users');
        $query->where('UPPER(email) = UPPER(' . $db->Quote($username) . ')');

        $db->setQuery($query);
        $result = $db->loadObject();

        if ($result) {
            // why mess with re-creating authentication - just use the system.
            $credentials['username'] = $result->username;
            $this->_paj->setDatabase($this->getDatabase());
            $this->_paj->setApplication(Factory::getApplication());
            $this->_paj->onUserAuthenticate($credentials, $options, $response);
        } else {
            $response->status = Authentication::STATUS_FAILURE;
            $response->error_message = Text::_('JGLOBAL_AUTH_INVALID_PASS');
        }
    }
}
