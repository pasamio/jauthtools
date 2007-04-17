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
 * @package Joomla! Authentication Tools
 * @author Sam Moffatt <S.Moffatt@toowoomba.qld.gov.au>
 * @author Toowoomba City Council Information Management Department
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2007 Toowoomba City Council/Sam Moffatt 
 * @version SVN: $Id:$
 * @see JoomlaCode Project: http://joomlacode.org/gf/project/jauthtools/
 */

jimport('joomla.application.plugin.plugin');
jimport('jauthtools.sso');
jimport('jauthtools.usersource');

/**
 * SSO Initiation
 * Kicks off SSO Authentication
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
		$sso = new JAuthSSOAuthentication();
		$sso->doSSOAuth($params->getValue('autocreate',false));
	}
}
?>