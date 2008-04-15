<?php
/**
 * HTTP Based SSO
 * 
 * This use Server variables to identify the user 
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
/**
 * SSO HTTP Source
 * Attempts to match a user based on the supplied server variables
 * @package JAuthTools
 * @subpackage SSO 
 */
class plgSSOHTTP extends JPlugin {
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
	function plgSSOHTTP(& $subject) {
		parent :: __construct($subject);
	}

	function detectRemoteUser() {
		$plugin = & JPluginHelper :: getPlugin('sso', 'http');
		$params = new JParameter($plugin->params);
		$ip_blacklist = $params->get('ip_blacklist','127.0.0.1');
		$list = explode("\n", $ip_blacklist);
		if(in_array($_SERVER['REMOTE_ADDR'],$list)) {
			return false;
		}		
		$remote_user = JArrayHelper::getValue($_SERVER,$params->getValue('userkey','REMOTE_USER'),'');
		$replace_set = explode('|', $params->getValue('username_replace',''));
		foreach($replace_set as $replacement) {
			$remote_user = str_replace($replacement,'',$remote_user);
		}
		return $remote_user;
	}
}

