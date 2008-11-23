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
 * @package JAuthTools
 * @author Sam Moffatt <sam.moffatt@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Department
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Sam Moffatt 
 * @version SVN: $Id:$
 * @see JoomlaCode Project: http://joomlacode.org/gf/project/jauthtools/
 */

jimport('joomla.user.helper');
jimport('joomla.utilities.string');
jimport('joomla.base.observable');

/**
 * User Source Provider
 * @package JAuthTools
 * @subpackage User-Source
 */
class JAuthUserSource extends JObservable {

	/** @var options array options */
	var $_options;

	/**
	 * Constructor
	 *
	 * @access protected
	 */
	function __construct($options=Array()) {
		// Import User Source Library Files
		$isLoaded = JPluginHelper :: importPlugin('usersource');
		if (!$isLoaded) {
			JError :: raiseWarning('SOME_ERROR_CODE', 'JAuthUserSource::__construct: Could not load User Source plugins.');
		}
		$this->_options = $options;
	}
	
	function doUserCreation($username) {
		// Do not create user if they exist already
		if(intval(JUserHelper::getUserId($username))) { 
			return true; 
		}

		// Load up User Source plugins
		$plugins = JPluginHelper :: getPlugin('usersource');
		foreach ($plugins as $plugin) {
			$className = 'plg' . $plugin->type . $plugin->name;
			if (class_exists($className)) {
				$plugin = new $className ($this, $plugin);
			} else {
				JError :: raiseWarning('SOME_ERROR_CODE', 'JAuthUserSource::doUserCreation: Could not load ' . $className);
				continue;
			}

		// Try to find user
		if($user = $this->discoverUser($username)) {
			$my =& JFactory::getUser(); // get who we are now
				$oldgid = $my->get('gid');
				$my->set('gid', 25); 		// and fake things to by pass security
				$result = $user->save(); 	// save us, now the db is up
				$my->get('gid', $oldgid);	// set back to old value 
				return $result;
				break;
			}
		return false;		
	}
	
	function doUserSynchronization($username) {
		// Load up User Source plugins
		$plugins = JPluginHelper :: getPlugin('usersource');
		foreach ($plugins as $plugin) {
			$className = 'plg' . $plugin->type . $plugin->name;
			if (class_exists($className)) {
				$plugin = new $className ($this, $this->_options);
			} else {
				JError :: raiseWarning('SOME_ERROR_CODE', 'JAuthUserSource::doUserSynchronization: Could not load ' . $className);
				continue;
			}
			// Fire a user sync event
			if($user = $plugin->doUserSync($username)) {
				// if we succeeded then lets bail out
				// means the first system gets priority
				// and no other system will overwrite the values
				// but first we need to save our user
				$my =& JFactory::getUser(); // get who we are now
				// by default we demote users
				if(isset($options['demoteuser']) && !$options['demoteuser']) {
					// reset the gid
					if($my->get('gid') < $user->get('gid')) $user->set('gid',$my->get('gid')); 
				}
				$oldgid = $my->get('gid');	// grab the current gid
				$my->set('gid', 25); 		// and fake things to by pass security
				$result = $user->save(); 	// save us, now the db is up
				$my->get('gid', $oldgid);	// set back to old value
				return true;				// thats all folks
				break;
			}
		}
		return false;
	}
	
	function discoverUser($username) {
		// Load up User Source plugins
		$plugins = JPluginHelper :: getPlugin('usersource');
		foreach ($plugins as $plugin) {
			$className = 'plg' . $plugin->type . $plugin->name;
			if (class_exists($className)) {
				$plugin = new $className ($this);
			} else {
				JError :: raiseWarning('SOME_ERROR_CODE', 'JAuthUserSource::discoverUser: Could not load ' . $className);
				continue;
}
			// Try to find user
			$user = new JUser();
			if($plugin->getUser($username,$user)) {
				return $user; //return the first user we find
				break;
			}
		}
	}
	
	function discoverUsers($username) {
		// Load up User Source plugins
		$users = Array();
		$plugins = JPluginHelper :: getPlugin('usersource');
		foreach ($plugins as $plugin) {
			$className = 'plg' . $plugin->type . $plugin->name;
			if (class_exists($className)) {
				$plugin = new $className ($this);
			} else {
				JError :: raiseWarning('SOME_ERROR_CODE', 'JAuthUserSource::discoverUsers: Could not load ' . $className);
				continue;
			}
			// Try to find user
			$user = new JUser();
			if($plugin->getUser($username,$user)) {
				$users[$plugin->name] = clone($user); // clone the user before putting them into the array
			}
		}
		return $users;			
	}
}
