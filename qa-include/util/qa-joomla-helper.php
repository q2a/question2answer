<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-external-users-joomla.php
	Description: External user functions for basic Joomla integration


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

/**
 * Link to Joomla app.
 */
class qa_joomla_helper {

    private $app;

    public function __construct()
    {
        $this->find_joomla_path();
        $this->load_joomla_app();
    }

    /* 
     * If your Q2A installation is in a subfolder of your Joomla, then this file should be in joomla-root/qa-root/qa-include/util/
     * We can use this info to track back up the tree to find the Joomla root.
     * If your Q2A installation is in a different folder structure, then you should define JOOMLA_PATH_BASE manually in your qa-config file.
     */
    private function find_joomla_path()
    {
        if (!defined('JPATH_BASE')) {   //JPATH_BASE must be defined for Joomla to work.
            $joomlaPath = dirname(dirname(dirname(__DIR__)));
            define('JPATH_BASE', $joomlaPath);
        }
    }

    private function load_joomla_app()
    {
        define( '_JEXEC', 1 ); //This will define the _JEXEC constant that will allow us to access the rest of the Joomla framework
        require_once(JPATH_BASE.'/includes/defines.php' );
        require_once(JPATH_BASE.'/includes/framework.php' );
        // Instantiate the application.
        $this->app = JFactory::getApplication('site');
        // Initialise the application.
        $this->app->initialise();
    }

    public function get_app()
    {
        return $this->app;
    }

    public function get_user($userid = null)
    {
        return JFactory::getUser($userid);
    }

    public function get_userid($username)
    {
        JUserHelper::getUserId($username);
    }

    function trigger_access_event($user)
    {
        return $this->trigger_joomla_event('onQnaAccess', [$user]);
    }
    function trigger_team_group_event($user)
    {
        return $this->trigger_joomla_event('onTeamGroup', [$user]);
    }
    function trigger_get_urls_event()
    {
        return $this->trigger_joomla_event('onGetURLs', []);
    }
    function trigger_get_avatar_event($userid, $size)
    {
        return $this->trigger_joomla_event('onGetAvatar', [$userid, $size]);
    }
    function trigger_log_event($userid, $action)
    {
        return $this->trigger_joomla_event('onWriteLog', [$userid, $action], false);
    }

    private function trigger_joomla_event($event, $args = [], $expectResponse = true)
    {
        JPluginHelper::importPlugin('q2a');
        $dispatcher = JEventDispatcher::getInstance();
        $results = $dispatcher->trigger($event, $args);

        if ($expectResponse && (!is_array($results) || count($results) < 1)) {
            //no Q2A plugins installed in Joomla, so we'll have to resort to defaults
            $results = $this->default_response($event, $args);
        }
        return array_pop($results);
    }
    private function default_response($event, $args)
    {
        return array(qa_joomla_default_integration::$event($args));
    }
}

/**
 * Implements the same methods as a Joomla plugin would implement, but called locally within Q2A.
 * This is intended as a set of default actions in case no Joomla plugin has been installed.
 * (Installing a Joomla plugin is recommended however, as there is a lot more flexibility that way)
 * Note that the implementation of these methods is not the same as it would be in a Joomla plugin, so please don't use it to crib from to create one! Use the actual Joomla Q2A Integration plugin instead.
 */
class qa_joomla_default_integration
{
    /**
     * If you're relying on the defaults, you must make sure that your Joomla instance has the following pages configured.
     */
    public static function onGetURLs()
    {
        return array(
            'login'  => JRoute::_('index.php?option=com_users&view=login'),
            'logout' => JRoute::_('index.php?option=com_users&view=login&layout=logout'),
            'reg'    => JRoute::_('index.php?option=com_users&view=registration'),
            'denied' => '/',
        );
    }

    /**
     * Return the access levels available to the user. A proper Joomla plugin would allow you to fine tune this in as much
     * detail as you needed, but this default method can only look at the core Joomla system permissions and try to map
     * those to the Q2A perms. Not ideal; enough to get started, but recommend switching to the Joomla plugin if possible.
     */
    public static function onQnaAccess(array $args)
    {
        list($user) = $args;

        return array(
            'view' => !($user->guest || $user->block),
            'post' => !($user->guest || $user->block),
            'edit' => $user->authorise('core.edit'),
            'mod'  => $user->authorise('core.edit.state'),
            'admin'=> $user->authorise('core.manage'),
            'super'=> $user->authorise('core.admin') || $user->get('isRoot'),
        );
    }

    /**
     * Return the group name (if any) that was responsible for granting the user access to the given view level.
     * For this default method, we just won't return anything.
     */
    public static function onTeamGroup($args)
    {
        list($user) = $args;
        return null;
    }

    /**
     * This method would be used to ask Joomla to supply an avatar for a user.
     * For this default method, we just won't do anything.
     */
    public static function onGetAvatar($args)
    {
        list($userid, $size) = $args;
        return null;
    }

    /**
     * This method would be used to notify Joomla of a Q2A action, eg so it could write a log entry.
     * For this default method, we just won't do anything.
     */
    public static function onWriteLog($args)
    {
        list($userid, $action) = $args;
        return null;
    }
}

/*
	Omit PHP closing tag to help avoid accidental output
*/
