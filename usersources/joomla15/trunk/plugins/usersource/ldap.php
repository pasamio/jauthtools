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
 * @package JAuthTools
 * @author Sam Moffatt <sam.moffatt@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Department
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Sam Moffatt
 * @version SVN: $Id:$
 * @see JoomlaCode Project: http://joomlacode.org/gf/project/jauthtools/
 */

jimport('joomla.plugin.plugin');
jimport('joomla.client.ldap');

/**
 * LDAP User Source
 * Finds people using LDAP
 * @package JAuthTools
 * @subpackage User-Source
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
		$ldapplugin =& JPluginHelper::getPlugin('authentication','ldap');
		$ldapparams = new JParameter($ldapplugin->params);
		$params->merge($ldapparams);
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
		$ldapplugin =& JPluginHelper::getPlugin('authentication','ldap');
		$ldapparams = new JParameter($ldapplugin->params);
		$params->merge($ldapparams);
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
		$loginDisabled = $params->getValue('ldap_blocked','loginDisabled');
		$groupMembership = $params->getValue('ldap_groups', 'groupMembership');
		$groupmember = $params->getValue('ldap_groupmember','member');
		$user->username = $username;
		$userdetails = $ldap->simple_search(str_replace("[search]", $user->username, $params->getValue('search_string')));
		$user->gid = 29;
		$user->usertype = 'Public Frontend';
		$user->email = $user->username; // Set Defaults
		$user->name = $user->username; // Set Defaults		
		$ldap_email = $params->getValue('ldap_email','mail');
		$ldap_fullname = $params->getValue('ldap_fullname', 'fullName');
		// we need at least a DN and a potentially valid email
		if (isset ($userdetails[0]['dn']) && isset ($userdetails[0][$ldap_email][0])) {
			$user->email = $userdetails[0][$ldap_email][0];
			if (isset ($userdetails[0][$ldap_fullname][0])) {
				$user->name = $this->_convert($userdetails[0][$ldap_fullname][0], $params);
			}

			$user->block = isset($userdetails[0][$loginDisabled]) ? intval($userdetails[0][$loginDisabled][0]) : 0;
	
			if ($map) {
				$groupMap = $this->_parseGroupMap($map);
				// add group memberships for active directory
				if($params->getValue('reversegroupmembership',0)) {
					$groupMemberships = Array();
					$cnt = 0;
				    if($params->getValue('authenticategroupsearch',0)) {
                        // since we are bound as the user, we have to bind as
                        // admin in order to search the groups and their attributes
                        $ldap_bind_uid = $params->get('username');
                        $ldap_bind_password = $params->get('password');
                        $ldap->bind($ldap_bind_uid , $ldap_bind_password , 1);
                     }
                     		
					foreach ($groupMap as $groupMapEntry) {
						$group = $groupMapEntry['groupname'];
						//Build search conext by splitting the cn=GROUPNAME from the search context
						$sSearch = substr($group, 0, strpos($group,','));
						$sContext = substr($group,strpos($group,',')+1,strlen($group));
						
						//query for the group
						$groupdetails = $ldap->search(array($sSearch),$sContext);
						$groupMembers = isset($groupdetails[0][$groupmember]) ? $groupdetails[0][$groupmember] : Array();
						
						foreach ($groupMembers as $groupMember) {
							if($groupMember == $userdetails[0]['dn']) {
								$groupMemberships[$cnt++] = $group;
							}
						}
					}
					if($cnt > 0) {
						$userdetails[0][$groupMembership] = $groupMemberships;
					}
				}
				$this->_reMapUser($user, $userdetails[0], $groupMap, $groupMembership);
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
			$groups = explode("\n", $map);
			foreach ($groups as $group) {
				if (trim($group)) {
					$details = explode(';', $group);
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
	
	function _convert($string, $params) {
		if(function_exists('iconv') && $params->get('use_iconv',0)) {
			return iconv($params->get('iconv_to','UTF-8'), $params->get('iconv_from','ISO8859-1'), $string);
		} else return $string;
	}
}
