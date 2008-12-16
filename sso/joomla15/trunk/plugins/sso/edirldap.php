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
 * SSO eDirectory Source
 * Attempts to match a user based on their network address attribute (IP Address)
 * @package JAuthTools
 * @subpackage SSO
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

		$ip_blacklist = $params->get('ip_blacklist','');
		$list = explode("\n", $ip_blacklist);
		if(in_array($_SERVER['REMOTE_ADDR'],$list)) {
			return false;
		}
		
		// really ugly, but what it does it looks for the file and then includes it
		// it turns out that joomla doesn't check the file exists first before trying
		// to include it
		if(file_exists(JPATH_LIBRARIES.DS.'jauthtools'.DS.'helper.php') && jimport('jauthtools.helper')) {
			$ldapparams =& JAuthToolsHelper::getPluginParams('authentication','ldap');
		} else {
			$ldapplugin =& JPluginHelper::getPlugin('authentication','ldap');
			if($ldapplugin) {
				$ldapparams = new JParameter($ldapplugin->params);
			} else {
				$ldapparams = new JParameter('');
			}
		}
		$params->merge($ldapparams);
		$ldapuid = $params->get('ldap_uid','uid');
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
		if (isset ($attributes[0][$ldapuid][0])) {
			$username = $attributes[0][$ldapuid][0];
			
			if ($username != NULL) {
				return $username; // eDir returns the appropriate username, no alteration required
			}
		}
	}
}
