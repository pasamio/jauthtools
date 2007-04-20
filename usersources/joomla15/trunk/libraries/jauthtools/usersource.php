<?php
/**
 * JAuthTools: User Sources
 * 
 * This file handles the retrieval and autocreation of a user
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

jimport('joomla.user.helper');
jimport('joomla.utilities.string');
/**
 * User Source Provider
 */
class JAuthUserSource extends JObservable {

	/**
	 * Constructor
	 *
	 * @access protected
	 */
	function __construct() {
		// Import User Source Library Files
		$isLoaded = JPluginHelper :: importPlugin('usersource');
		if (!$isLoaded) {
			JError :: raiseWarning('SOME_ERROR_CODE', 'JAuthUserSource::__construct: Could not load User Source libraries.');
		}
	}
	
	function doUserCreation($username) {
		// Do not create user if they exist already
		if(intval(JUserHelper::getUserId($username))) { return true; }
		// Load up User Source plugins
		$plugins = JPluginHelper :: getPlugin('usersource');
		foreach ($plugins as $plugin) {
			$className = 'plg' . $plugin->folder . $plugin->element;
			if (class_exists($className)) {
				$plugin = new $className ($this);
			} else {
				JError :: raiseWarning('SOME_ERROR_CODE', 'JAuthUserSource::doUserCreation: Could not load ' . $className);
				continue;
			}

			// Try to find user
			$user = new JUser();
			if($plugin->getUser($username,$user)) {
				//print_r($user);
				$user->save();
				return true;
				break;
			}
		}
		return false;		
	}
}
?>