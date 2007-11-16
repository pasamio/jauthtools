<?php
/**
 * SSO eDirectory LDAP Plugin
 * 
 * This file handles eDirectory based LDAP SSO 
 * 
 * PHP4/5
 *  
 * Created on Apr 17, 2007
 * 
 * @package JAuthTools
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
 * SSO eDirectory Source
 * Attempts to match a user based on their network address attribute (IP Address)
 */
class plgSSOEDirLDAP extends JPlugin {
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
	function plgSSOEDirLDAP(& $subject) {
		parent :: __construct($subject);
	}

	function detectRemoteUser() {
		global $database, $mainframe, $acl;
		// I burnt my hand and now it hurts like hell...god damn it
		// load parameters
		$plugin = & JPluginHelper :: getPlugin('sso', 'edirldap');
		$params = new JParameter($plugin->params);
		$ldap = new JLDAP($params);
		
		if (!$ldap->connect()) {
			JError::raiseWarning('SOME_ERROR_CODE', 'plgSSOEDirLDAP::detectRemoteUser: Failed to connect to LDAP Server '. $params->getValue('host'));
			return '';
		}
			
		if(!$ldap->bind()) {
			JError::raiseWarning('SOME_ERROR_CODE', 'plgSSOEDirLDAP::detectRemoteUser: Failed to bind to LDAP Server');
			return '';
		}

		$ip = $_SERVER['REMOTE_ADDR'];
		$na = $ldap->ipToNetAddress($ip);

		// just a test, please leave
		$search_filters = array (
			"(networkAddress=$na)"
		);
 		$dn = $params->getValue('base_dn');
		$attributes = $ldap->search($search_filters,	$dn);
		$ldap->close();
		if (isset ($attributes[0]['uid'][0])) {
			$username = $attributes[0]['uid'][0];
			
			if ($username != NULL) {
				return $username; // eDir returns the appropriate username, no alteration required
			}
		}
	}
}
?>
