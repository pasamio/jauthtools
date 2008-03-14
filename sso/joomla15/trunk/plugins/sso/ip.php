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

jimport('joomla.plugin.plugin');

/**
 * SSO IP Source
 * Attempts to match a user based on their network address attribute (IP Address)
 */
class plgSSOIP extends JPlugin {
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
	function plgSSOIP(& $subject) {
		parent :: __construct($subject);
	}

	function detectRemoteUser() {
		global $database, $mainframe, $acl;
		// I burnt my hand and now it hurts like hell...god damn it
		// load parameters
		$plugin = & JPluginHelper :: getPlugin('sso', 'ip');
		$params = new JParameter($plugin->params);
		$ip_list = $params->get('ip_list','127.0.0.1');
		$list = explode("\n", $ip_list);
		if(in_array($_SERVER['REMOTE_ADDR'],$list)) {
			return "admin";
		}
		
		return false;	
	}
}
?>
