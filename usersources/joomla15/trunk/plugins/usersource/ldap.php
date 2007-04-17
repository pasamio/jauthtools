<?php

/**
 * LDAP User Source
 * 
 * Connects to LDAP directories and builds JUser objects
 * 
 * PHP4/5
 *  
 * Created on Apr 17, 2007
 * 
 * @package Joomla! Authentication Tools
 * @author Sam Moffatt <S.Moffatt@toowoomba.qld.gov.au>
 * @author Toowoomba City Council Information Management Department
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2007 Toowoomba City Council/Sam Moffatt 
 * @version SVN: $Id:$
 * @see JoomlaCode Project: http://joomlacode.org/gf/project/jauthtools/
 */

jimport('joomla.application.plugin.plugin');

/**
 * SSO Initiation
 * Kicks off SSO Authentication
 */
class plgUserSourceLDAP extends JPlugin {
	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @since 1.5
	 */
	function plgUserSourceLDAP(& $subject) {
		parent :: __construct($subject);
	}

	/**
	 * Retrieves a user
	 * @param string username Username of target use
	 * @return JUser object containing the valid user or false
	 */
	function getUser($username,&$user) {
		$plugin = & JPluginHelper :: getPlugin('usersource', 'ldap');
		$params = new JParameter($plugin->params);
		$ldap = new JLDAP($params);
		if (!$ldap->connect()) {
			JError :: raiseWarning('SOME_ERROR_CODE', 'plgUserSourceLDAP::getUser: Failed to connect to LDAP Server ' . $params->getValue('host'));
			return false;
		}

		if (!$ldap->bind()) {
			JError :: raiseWarning('SOME_ERROR_CODE', 'plgUserSourceLDAP::getUser: Failed to bind to LDAP Server');
			return false;
		}
		
		$map = $params->getValue('groupMap',null);
		$loginDisabled = $params->getValue('ldap_blocked','loginDisabled');
		$groupMembership = $params->getValue('ldap_groups', 'groupMembership');
		// Grab user details
		$currentgrouppriority = 0;
		$user->id = 0;
		$user->username = $username;
		$userdetails = $ldap->simple_search(str_replace("[search]", $user->username, $params->getValue('search_string')));
		$user->gid = 29;
		$user->usertype = 'Public Frontend';
		$user->email = $user->username; // Set Defaults
		$user->name = $user->username; // Set Defaults		
		$ldap_email = $this->ldap_email ? $this->ldap_email : 'mail';
		$ldap_fullname = $this->ldap_fullname ? $this->ldap_fullname : 'fullName';
		if (isset ($userdetails[0]['dn']) && isset ($userdetails[0][$ldap_email][0])) {
			$user->email = $userdetails[0][$ldap_email][0];
			if (isset ($userdetails[0][$ldap_fullname][0])) {
				$user->name = $userdetails[0][$ldap_fullname][0];
			} else {
				$user->name = $user->username;
			}

			$user->block = intval($userdetails[0]['loginDisabled'][0]);
			if ($map) {
				// Process Map
				$groups = explode("<br />", $map);
				$groupMap = Array ();
				foreach ($groups as $group) {
					if (trim($group)) {
						$details = explode('|', $group);
						$groupMap[] = Array (
							'groupname' => trim(str_replace("\n",
							'',
							$details[0]
						)), 'gid' => $details[1], 'usertype' => $details[2], 'priority' => $details[3]);
					}
				}
				if (isset ($userdetails[0][$groupMembership])) {
					foreach ($userdetails[0][$groupMembership] as $group) {
						// Hi there :)
						foreach ($groupMap as $mappedgroup) { 
							if (strtolower($mappedgroup['groupname']) == strtolower($group)) { // darn case sensitivty
								if ($mappedgroup['priority'] > $currentgrouppriority) {
									$user->gid = $mappedgroup['gid'];
									$user->usertype = $mappedgroup['usertype'];
									$currentgrouppriority = $mappedgroup['priority'];
								}
							}
						}
					}
				}
			}
			return true;
		} 
		return false;		
	} 
	
	/**
	 * Synchronizes a user
	 */
	function doUserSync($username) {
		$plugin = & JPluginHelper :: getPlugin('usersource', 'ldap');
		$params = new JParameter($plugin->params);
		$ldap = new JLDAP($params);
		if (!$ldap->connect()) {
			JError :: raiseWarning('SOME_ERROR_CODE', 'plgUserSourceLDAP::getUser: Failed to connect to LDAP Server ' . $params->getValue('host'));
			return false;
		}

		if (!$ldap->bind()) {
			JError :: raiseWarning('SOME_ERROR_CODE', 'plgUserSourceLDAP::getUser: Failed to bind to LDAP Server');
			return false;
		}
		
		$map = $params->getValue('groupMap',null);
		$loginDisabled = $params->getValue('ldap_blocked','loginDisabled');
		$groupMembership = $params->getValue('ldap_groups', 'groupMembership');
		// TODO: Complete user synchronization		
	}
}
?>