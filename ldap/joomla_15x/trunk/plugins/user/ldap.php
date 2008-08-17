<?php
/**
 * @version		$Id: example.php 10094 2008-03-02 04:35:10Z instance $
 * @package		JAuthTools
 * @subpackage	LDAP
 * @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.client.ldap');

/**
 * LDAP Integration Plugin
 *
 * @package		JAuthTools
 * @subpackage	LDAP
 * @since 		1.5
 */
class plgUserLDAP extends JPlugin {

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param 	array  $config  An array that holds the plugin configuration
	 * @since 1.5
	 */
	function plgUserLDAP(& $subject, $config)
	{
		parent::__construct($subject, $config);
	}

	/**
	 * LDAP store user method
	 *
	 * We store the user in the default location if they are new,
	 * or save them back to where they are if they are not a new
	 * user.
	 *
	 * @param 	array		holds the new user data
	 * @param 	boolean		true if a new user is stored
	 * @param	boolean		true if user was succesfully stored in the database
	 * @param	string		message
	 */
	function onAfterStoreUser($user, $isnew, $success, $msg)
	{
		global $mainframe;
		if(!$success) return false; // bail out if not successfully deleted
		// convert the user parameters passed to the event
		// to a format the external application

		$args = array();
		$args['username']	= $user['username'];
		$args['email'] 		= $user['email'];
		$args['fullname']	= $user['name'];
		$args['password']	= $user['password'];

		// get the plugin params
		$plugin = & JPluginHelper :: getPlugin('user', 'ldap');
		$params = new JParameter($plugin->params);
		// grab the global ldap plugin params
		$ldapplugin =& JPluginHelper::getPlugin('authentication','ldap');
		$ldapparams = new JParameter($ldapplugin->params);
		$params->merge($ldapparams);
		$ldapuid = $params->get('ldap_uid','uid');	
		$defaultdn = $params->get('defaultdn','');
		$ldap = new JLDAP($params);
		$ldap->connect() or die('failed to connect');
		$ldap->bind() or die('failed to bind: '. $ldap->getErrorMsg());
		// set up the user
		$ldapuser = Array();
		$ldapuser['cn'] = $user['username'];
		$ldapuser['displayname'] = $user['name'];
		$parts = explode(' ',$user['name']);
		$ldapuser['sn'] = array_pop($parts); // Get the last part, ensures we at least have a value for surname (req)
		$ldapuser['givenname'] = array_shift($parts); //$parts[0];
		if(!strlen($ldapuser['givenname'])) unset($ldapuser['givenname']);
		if(count($parts)) {
			$ldapuser['initials'] = implode(' ', $parts); // abuse this; outlook does the same
		}
		$ldapuser['userpassword'] = Array($ldap->generatePassword($user['password_clear']));
		$ldapuser['mail'] = $user['email'];		
		$ldapuser[$ldapuid] = $user['username'];
		$ldapuser['joomlagroup'] = $user['usertype'];

		// create the user in the default location
		// this can be moved later
		$dn = $ldapuid.'='. $user['username'].','.$defaultdn;
		if ($isnew)
		{
			$this->_createUser($ldap, $dn, $ldapuser);
		}
		else
		{
			// update the user in the location
			// need to find the user before we can update them
			$result = $ldap->simple_search($ldapuid.'='.$user['username']);
			if(count($result) == 1) {
				if(!isset($ldapuser['initials'])) $ldapuser['initials'] = array();
				if(!isset($ldapuser['givenname'])) $ldapuser['givenname'] = array();
				$ldap->modify($result[0]['dn'],$ldapuser) or die('modify failed');
			} else {
				$this->_createUser($ldap, $dn, $ldapuser);
			}
		}
		// testing code; delete the user after they've been created
		//$temp = JUser::getInstance($user['id']);
		//$temp->delete();
		//echo '<hr />';
		//die(print_r($temp,1).'<br />'.print_r($user,1));
		return true;
	}
	
	function onBeforeDeleteUser($user, $success, $msg) {
			
	}

	/**
	 * LDAP User Deletion
	 *
	 * Method is called after user data is deleted from the database
	 *
	 * @param 	array		holds the user data
	 * @param	boolean		true if user was succesfully deleted in the database
	 * @param	string		message
	 */
	function onAfterDeleteUser($user, $success, $msg)
	{
		global $mainframe;
		if(!$success) return false; // bail out if not successfully deleted
		// mainframe again!
		// so the user has been deleted

	 	// only the $user['id'] exists and carries valid information
	 	// ^^ this is bs, actually the user is fully populated

		// Find the user in the tree
		// load parameters
		$plugin = & JPluginHelper :: getPlugin('user', 'ldap');
		$params = new JParameter($plugin->params);
		
		// grab the global params
		$ldapplugin =& JPluginHelper::getPlugin('authentication','ldap');
		$ldapparams = new JParameter($ldapplugin->params);
		$params->merge($ldapparams);
		$ldapuid = $params->get('ldap_uid','uid');
		$ldap = new JLDAP($params);
		$ldap->connect();
		$ldap->bind();
		// search for the user
		$result = $ldap->simple_search($ldapuid.'='.$user['username']);
		$c = count($result);
		if($c == 1) {
			$ldap->delete($result[0]['dn']);// or die('failed to delete user');
			JError::raiseWarning(41, JText::_('User deleted'));
		} else if($c > 1) {
			// there was more than one DN returned, special situation!
			JError::raiseWarning(42,JText::_('Too many users found in LDAP'));			
		} else {
			// didn't find a result
			JError::raiseWarning(43,JText::_('No matching LDAP user found'));
		}
	}

	/**
	 * This method should handle any login logic and report back to the subject
	 *
	 * @access	public
	 * @param 	array 	holds the user data
	 * @param 	array    extra options
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function onLoginUser($user, $options)
	{
		// Initialize variables
		$success = false;
		$success = true;

		// Here you would do whatever you need for a login routine with the credentials
		//
		// Remember, this is not the authentication routine as that is done separately.
		// The most common use of this routine would be logging the user into a third party
		// application.
		//
		// In this example the boolean variable $success would be set to true
		// if the login routine succeeds

		// ThirdPartyApp::loginUser($user['username'], $user['password']);

		return $success;
	}

	/**
	 * This method should handle any logout logic and report back to the subject
	 *
	 * @access public
	 * @param array holds the user data
	 * @return boolean True on success
	 * @since 1.5
	 */
	function onLogoutUser($user)
	{
		// Initialize variables
		$success = false;
		$success = true;

		// Here you would do whatever you need for a logout routine with the credentials
		//
		// In this example the boolean variable $success would be set to true
		// if the logout routine succeeds

		// ThirdPartyApp::loginUser($user['username'], $user['password']);

		return $success;
	}
	
	/**
	 * Create a user with the details at a DN
	 * Populates the objectclass
	 *
	 * @param string dn DN where to create the user
	 * @param array ldapuser The LDAP user details
	 * @return bool result of create
	 * @access private
	 */
	function _createUser(&$ldap, $dn, $ldapuser) {
		// new user needs to have the objectClass set
		// JoomlaUser and Internet Organisation: Person (structural)
		$ldapuser['objectclass'] = Array('top','inetOrgPerson','JoomlaUser'); 			
		return $ldap->create($dn,$ldapuser);// or die('Failed to add '. $dn .': ' . $ldap->getErrorMsg());	
	}

}
