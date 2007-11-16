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

jimport('joomla.event.plugin');
jimport('joomla.client.ldap');

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
		
		return $this->_updateUser($ldap, $username, $user, $params);
	} 
	
	/**
	 * Synchronizes a user
	 */
	function &doUserSync($username) {
		$plugin = & JPluginHelper :: getPlugin('usersource', 'ldap');
		$params = new JParameter($plugin->params);
		$ldap = new JLDAP($params);
		$return = false;
		if (!$ldap->connect()) {
			JError :: raiseWarning('SOME_ERROR_CODE', 'plgUserSourceLDAP::getUser: Failed to connect to LDAP Server ' . $params->getValue('host'));
			return false;
		}

		if (!$ldap->bind()) {
			JError :: raiseWarning('SOME_ERROR_CODE', 'plgUserSourceLDAP::getUser: Failed to bind to LDAP Server');
			return false;
		}
		
		$user = new JUser();
		$user->load(JUserHelper::getUserId($username));
		if($this->_updateUser($ldap, $username, $user, $params)) {
			$return = $user;
		}
		return $return;
	}	
	
	/**
	 * Update user
	 */
	function _updateUser(&$ldap, $username, &$user, &$params) {
		$map = $params->getValue('groupMap',null);
		//echo '<pre>';print_r($map); echo '</pre>';
		$loginDisabled = $params->getValue('ldap_blocked','loginDisabled');
		$groupMembership = $params->getValue('ldap_groups', 'groupMembership');
		// Grab user details
		//$user->id = 0;
		$user->username = $username;
		$userdetails = $ldap->simple_search(str_replace("[search]", $user->username, $params->getValue('search_string')));
		$user->gid = 29;
		$user->usertype = 'Public Frontend';
		$user->email = $user->username; // Set Defaults
		$user->name = $user->username; // Set Defaults		
		$ldap_email = $params->getValue('ldap_email','mail');
		$ldap_fullname = $params->getValue('ldap_fullname', 'fullName');
		if (isset ($userdetails[0]['dn']) && isset ($userdetails[0][$ldap_email][0])) {
			$user->email = $userdetails[0][$ldap_email][0];
			if (isset ($userdetails[0][$ldap_fullname][0])) {
				$user->name = $userdetails[0][$ldap_fullname][0];
			} else {
				$user->name = $user->username;
			}

			$user->block = intval($userdetails[0]['loginDisabled'][0]);
			if ($map) {
				$this->_remapUser($user, $userdetails[0], $this->_parseGroupMap($map), $groupMembership);
			}
			return true;
		}
		return false;
	}
	
	/**
	 * Group map handler
	 * This is defunct due to core param handling
	 */
	function &_parseGroupMap($map) {
		// Process Map
		$groupMap = Array ();
		if(!is_array($map)) { // we may have got it preprocessed into an array! probably from JParam
			// however its probably a string
			$groups = explode("<br />", $map);
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
		} else {
			// preprocessed array! just need to rename things
			foreach($map as $details) {
					$groupMap[] = Array (
						'groupname' => trim(str_replace("\n",
							'',
							$details[0]
						)), 
						'gid' => $details[1], 
						'usertype' => $details[2], 
						'priority' => $details[3]);
			}
		}
		return $groupMap;
	}
	
	/**
	 * Remap the user
	 */
	function _reMapUser(&$user, $details, $groupMap, $groupMembership) {
		$currentgrouppriority = 0;
		if (isset ($details[$groupMembership])) {
			foreach ($details[$groupMembership] as $group) {
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
		return true;
	}
}
?>