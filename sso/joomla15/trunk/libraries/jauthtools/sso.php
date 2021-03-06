<?php
/**
 * JAuthTools: SSO Authentication System
 *
 * This file handles SSO based Authentication
 *
 * PHP4/5
 *
 * Created on Apr 17, 2007
 *
 * @package JAuthTools
 * @author Sam Moffatt <pasamio@gmail.com>
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2009 Sam Moffatt
 * @version SVN: $Id:$
 * @see JoomlaCode Project: http://joomlacode.org/gf/project/jauthtools/
 */

defined('JPATH_BASE') or die('sos');
jimport('joomla.base.observable');

/**
 * SSO Auth Handler
 * @package JAuthTools
 * @subpackage SSO
 */
class JAuthSSOAuthentication extends JObservable {
	/**
	 * Constructor
	 *
	 * @access protected
	 */
	function __construct() {
		// Import SSO Library Files
		$isLoaded = JPluginHelper :: importPlugin('sso');
		if (!$isLoaded) {
			JError :: raiseWarning('SOME_ERROR_CODE', 'JAuthSSOAuthentication::__construct: Could not load SSO plugins.');
		}
	}

	function doSSOAuth($autocreate=false) {
		// Load up SSO plugins
		$plugins = JPluginHelper :: getPlugin('sso');
		foreach ($plugins as $plugin) {
			$className = 'plg' . $plugin->type . $plugin->name;
			if (class_exists($className)) {
				$plugin = new $className ($this, (array)$plugin);
			} else {
				JError :: raiseWarning('SOME_ERROR_CODE', 'JAuthSSOAuthentication::doSSOAuth: Could not load ' . $className);
				continue;
			}

			// Try to authenticate remote user
			$username = $plugin->detectRemoteUser();
			
			// If authentication is successful break out of the loop
			if ($username != '') {
				if($autocreate) {
					jimport('jauthtools.usersource');
					$usersource = new JAuthUserSource();
					$usersource->doUserCreation($username);
				}
				$this->doSSOSessionSetup($username);
				break;
			}
		}
	}

	function doSSOSessionSetup($username) {
		// Get Database and find user
		$database = JFactory::getDBO();
		$query = 'SELECT * FROM #__users WHERE username=' . $database->Quote($username);
		$database->setQuery($query);
		$result = $database->loadAssocList();
		// If the user already exists, create their session; don't create users
		if (count($result)) {
			$result = $result[0];
			$options = Array();
			$app =& JFactory::getApplication();
			if($app->isAdmin()) {
				// The minimum group
				$options['group'] = 'Public Backend';
			}
				
			//Make sure users are not autoregistered
			$options['autoregister'] = false;
				
			// fake the type for plugins that rely on this
			$result['type'] = 'sso';

			// Import the user plugin group
			JPluginHelper::importPlugin('user');
				
			$dispatcher =& JDispatcher::getInstance();
				
			// Log out the existing user if someone is logged into this client
			$user =& JFactory::getUser();
			if($user->id) {
				// Build the credentials array
				$parameters['username'] = $user->get('username');
				$parameters['id']       = $user->get('id');
				$dispatcher->trigger('onLogoutUser', Array($parameters, Array('clientid'=>Array($app->getClientId()))));
			}
			// OK, the credentials are authenticated.  Lets fire the onLogin event
			$results = $dispatcher->trigger('onLoginUser', array($result, $options));
				
			if (!in_array(false, $results, true)) {
				// Set the remember me cookie if enabled
				if (isset($options['remember']) && $options['remember'])
				{
					jimport('joomla.utilities.simplecrypt');
					jimport('joomla.utilities.utility');
						
					//Create the encryption key, apply extra hardening using the user agent string
					$key = JUtility::getHash(@$_SERVER['HTTP_USER_AGENT']);
						
					$crypt = new JSimpleCrypt($key);
					$rcookie = $crypt->encrypt(serialize($credentials));
					$lifetime = time() + 365*24*60*60;
					setcookie( JUtility::getHash('JLOGIN_REMEMBER'), $rcookie, $lifetime, '/' );
				}
				return true;
			}
			$this->triggerEvent('onLoginFailure', array($result));
			return false;
		}
	}

	// TODO: This should probably go into a helper
	function getSSOXMLData($filename) {
		$xml =& JFactory::getXMLParser('Simple');
		if(!$xml->loadFile($filename)) {
			unset($xml);
			return false;
		}
		$sso =& $xml->document->getElementByPath('sso');
		$data = Array();

		$element =& $sso->type[0];
		$data['type'] = $element ? $element->data() : 'A'; // type A plugins are the default

		$element =& $sso->key[0];
		$data['key'] = $element ? $element->data() : ''; // default to blank key


		$element =& $sso->valid_states[0];
		if($element) {
			$data['state_map'] = isset($element->state) ? JAuthSSOAuthentication::_processStateMap($element) : Array(); // default to blank array
			$data['default_state'] = $element->attributes('default');
		} else {
			$data['state_map'] = Array();
			$data['default_state'] = 0;
		}

		$element =& $sso->operations[0];
		$data['operations'] = $element && isset($element->operation) ? JAuthSSOAuthentication::_processOperations($element) : Array(); // default to blank array
		return $data;
	}

	function _processOperations($element) {
		$list = Array();
		foreach($element->operation as $operation) {
			$list[$operation->attributes('name')] = $operation->attributes('label');
		}
		return $list;
	}

	function _processStateMap($element) {
		$map = Array();
		foreach($element->state as $state) {
			$index = $state->attributes('value');
			$map[$index] = Array();
			if(!isset($state->operation)) continue;
			foreach($state->operation as $operation) {
				$map[$index][] = $operation->attributes('name');
			}
		}
		return $map;
	}

	function &getProvider($provider = '') {
		$providers =& JAuthSSOAuthentication::_loadProviders();
		if($provider) {
			$results = Array();
			$ip = count($providers);
			for($i = 0; $i < $ip; $i++) {
				if($providers[$i]->type == $provider) {
					$results[] = $providers[$i];
				}
			}
			return $results;
		} else {
			return $providers;
		}
	}
	
	function &_loadProviders() {
		static $plugins;

		if (isset($plugins)) {
			return $plugins;
		}

		$db             =& JFactory::getDBO();
		$query = 'SELECT element AS type, sp.*'
		. ' FROM #__sso_providers sp '
		. ' RIGHT JOIN #__plugins p ON p.id = sp.plugin_id'
		. ' WHERE sp.published >= 1 AND p.published >= 1'
		. ' ORDER BY ordering';

		$db->setQuery( $query );

		$plugins = $db->loadObjectList();
		return $plugins;
	}
	
	function getBaseUrl($prefer_component=true, $plugin='',$task='delegate') {
		if($prefer_component && JComponentHelper::getComponent('com_ssomanager', true)) {
			$url = JURI::base().'index.php?option=com_ssomanager';
			
			// if we have a component, use it
			if(!empty($plugin)) {
				$url .= '&plugin='.$plugin;
			}
			if(!empty($task))
			{
				$url .= '&task='. $task;
			}
			else
			{
				$url .= '&task=delegate';
			}
			return urlencode($url);	
		} else {
			// hope that the plugin is active or a module
			return urlencode(JURI::base());
		}
	}
}
