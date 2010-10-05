<?php
/**
 * SSO JAuthTools Token Login Plugin 
 * 
 * This file handles token logins 
 * 
 * PHP4/5
 *  
 * Created on July 3, 2008
 * 
 * @package JAuthTools
 * @author Sam Moffatt <pasamio@gmail.com>
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2009 Sam Moffatt 
 * @version SVN: $Id:$
 * @see JoomlaCode Project: http://joomlacode.org/gf/project/jauthtools/
 */

jimport('joomla.plugin.plugin');
jimport('jauthtools.token');

/**
 * SSO SimpleSSO
 * Attempts to match a user based on a key which is valid with SimpleSSO
 */
class plgSSOTokenLogin extends JPlugin {
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
	function plgSSOTokenLogin(& $subject, $params) {
		parent :: __construct($subject, $params);
	}

	function detectRemoteUser() {
		$key = JRequest::getVar('logintoken','');
		if($key) {
			$result = JAuthToolsToken::validateToken($key);
			if($result) {
				return $result->username;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}
