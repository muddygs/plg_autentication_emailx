<?php

/**
 * Original author's header:
 * version		$Id: email.php 20196 2011-03-04 02:40:25Z mrichey $
 * package		plg_auth_email
 * copyright	Copyright (C) 2005 - 2011 Michael Richey. All rights reserved.
 * license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

/**
 * @package plgSystemEmailx
 * @author Merrill Squiers
 * @since  Joomla 4.0
 * @version 4.0.0
 */

class plgSystemEmailx extends CMSPlugin implements SubscriberInterface
{
    // The following properties are initialized by CMSPlugin::__construct()
    protected $db;
    protected $app;
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array { 
        return [
            'onAfterRoute' => 'handleResetConfirm',
        ];
    }

	function handleResetConfirm()
	{
            $app = $this->app;
            if($app->getName() === 'administrator') return;
            $component = $app->input->getCmd('option');
            if($component != 'com_users') return;
            $task = $app->input->getCmd('task');
            if($task != 'reset.confirm') return;
            
            // ok, at this point we know that the form has been submitted.
            $jform = $app->input->get('jform',array(),'array');
            if(count($jform) && preg_match('/@/',$jform['username'])) {
                $db = $this->db;
                $query = $db->getQuery(true);
                $query->select('username')
                    ->from('#__users')
                    ->where('UPPER(email) = UPPER('.$db->quote($jform['username']).')')
                    ->where('block = 0');
                $db->setQuery($query);
                $username = $db->loadResult();
                if( $username !== null ) {
                    $jform['username']=$username;
                    $app->input->set('jform',$jform);
                }
            }
        }
}
