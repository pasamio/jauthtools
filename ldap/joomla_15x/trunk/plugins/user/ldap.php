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
		$ldap_rdnprefix = $params->get('ldap_rdnprefix', $ldapuid);
		$ldap = new JLDAP($params);
		if(!$ldap->connect()) {
			JError::raiseWarning(39, JText::_('Failed to connect to LDAP server').': '. $ldap->getErrorMsg());
			return false;
		}
		if(!$ldap->bind()) {
			JError::raiseWarning(40, JText::_('Failed to bind to LDAP Server'). ': '. $ldap->getErrorMsg());
			return false;
		}
		
		$template = $params->get('template', 'joomla');
		// set up the user
		$ldapuser = Array();
		switch($template) {
			case 'opendirectory':
				$ldapuser['cn'] = $user['name'];
				$parts = explode(' ',$user['name']);
				$ldapuser['sn'] = array_pop($parts); // Get the last part, ensures we at least have a value for surname (req)
				if(!strlen($ldapuser['sn'])) {
					$ldapuser['sn'] = $user['name'];
				} else {
					if(count($parts)) {
						$ldapuser['givenName'] = array_shift($parts); // Try for the given name
						if(!strlen($ldapuser['givenname'])) unset($ldapuser['givenname']);	
					}	
				}
				
				$ldapuser['userpassword'] = $user['password_clear']; // apple passwords appear to be clear text, go figure
				$ldapuser['mail'] = $user['email'];
				$ldapuser[$ldapuid] = $user['username'];
				$ldapuser['gidNumber'] = $params->get('gidNumber', 20);
				$ldapuser['uidNumber'] = $params->get('uidOffset', 10000) + $user['id']; 
				$ldapuser['homeDirectory'] = str_replace('[username]', $user['username'], $params->get('homeDirectory',''));
				// new user needs to have the objectClass set
				// JoomlaUser and Internet Organisation: Person (structural)
				$ldapuser['objectclass'] = Array('top','inetOrgPerson','posixAccount','shadowAccount','apple-user','extensibleObject');
				break;
			case 'openldap':
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
				// new user needs to have the objectClass set
				// Internet Organisation: Person (structural)
				$ldapuser['objectclass'] = Array('top','inetOrgPerson');
				break;
			case 'joomla':
			default:
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
				// new user needs to have the objectClass set
				// JoomlaUser and Internet Organisation: Person (structural)
				$ldapuser['objectclass'] = Array('top','inetOrgPerson','JoomlaUser'); 	
				break;
		}
		// create the user in the default location
		// this can be moved later
		
		$dn = $ldap_rdnprefix.'='. $user['username'].','.$defaultdn;
		if ($isnew)
		{
			if(!$this->_createUser($ldap, $dn, $ldapuser)) 
				JError::raiseWarning(45, JText::sprintf('Failed to create user: %s', $ldap->getErrorMsg()));
		}
		else
		{
			// update the user in the location
			// need to find the user before we can update them
			$result = $ldap->simple_search($ldapuid.'='.$user['username']);
			if(count($result) == 1) {
				if(!isset($ldapuser['initials'])) $ldapuser['initials'] = array();
				if(!isset($ldapuser['givenname'])) $ldapuser['givenname'] = array();
				if(!$ldap->modify($result[0]['dn'],$ldapuser)) {
					JError::raiseWarning(44, JText::_('LDAP Modify failed'));
				}
			} else {
				if(!$this->_createUser($ldap, $dn, $ldapuser))
					JError::raiseWarning(45, JText::sprintf('Failed to create user: %s', $ldap->getErrorMsg()));
			}
		}
		// testing code; delete the user after they've been created
		//$temp = JUser::getInstance($user['id']);
		//$temp->delete();
		//echo '<hr />';
		//die(print_r($temp,1).'<br />'.print_r($user,1));
		return true;
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
		if(!$ldap->connect()) {
			JError::raiseWarning(39, JText::_('Failed to connect to LDAP server').': '. $ldap->getErrorMsg());
			return false;
		}
		if(!$ldap->bind()) {
			JError::raiseWarning(40, JText::_('Failed to bind to LDAP Server'). ': '. $ldap->getErrorMsg());
			return false;
		}
		// search for the user
		$result = $ldap->simple_search($ldapuid.'='.$user['username']);
		$c = count($result);
		if($c == 1) {
			$ldap->delete($result[0]['dn']);// or die('failed to delete user');
			JError::raiseWarning(41, JText::_('LDAP User deleted'));
		} else if($c > 1) {
			// there was more than one DN returned, special situation!
			JError::raiseWarning(42,JText::_('Too many users found in LDAP'));			
		} else {
			// didn't find a result
			JError::raiseWarning(43,JText::_('No matching LDAP user found'));
		}
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
		return $ldap->create($dn,$ldapuser); // or die('Failed to add '. $dn .': ' . $ldap->getErrorMsg());	
	}

}
