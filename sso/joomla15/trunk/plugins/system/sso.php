<?php
/**
 * SSO Login System
 * 
 * This starts an SSO Login. SSO Login may occur via a variety of sources 
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
jimport('jauthtools.sso');
jimport('jauthtools.usersource');

/**
 * SSO Initiation
 * Kicks off SSO Authentication
 * @package JAuthTools
 * @subpackage SSO 
 */
class plgSystemSSO extends JPlugin {
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
	function plgSystemSSO(& $subject) {
		parent :: __construct($subject);
	}

	function onAfterInitialise() {
		$plugin = & JPluginHelper :: getPlugin('system', 'sso');
		$params = new JParameter($plugin->params);
		$ip_blacklist = $params->get('ip_blacklist','');
		$list = explode("\n", $ip_blacklist);
		if(in_array($_SERVER['REMOTE_ADDR'],$list)) {
			return false;
		}	
		
		if(!$params->get('backend',0)) {
			$app =& JFactory::getApplication();
			if($app->isAdmin()) return false;
		}
		$sso = new JAuthSSOAuthentication();
		$sso->doSSOAuth($params->getValue('autocreate',false));
	}
}
