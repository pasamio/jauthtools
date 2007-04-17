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
 * SSO HTTP Source
 * Attempts to match a user based on the supplied server variables
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
		$remote_user = JArrayHelper::getValue($_SERVER,$params->getValue('userkey','REMOTE_USER'),'');
		$replace_set = explode('|', $params->getValue('username_replace',''));
		foreach($replace_set as $replacement) {
			$remote_user = str_replace($replacement,'',$remote_user);
		}
		return $remote_user;
	}
}

?>