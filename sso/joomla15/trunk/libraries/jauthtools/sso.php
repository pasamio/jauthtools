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
 * @author Sam Moffatt <sam.moffatt@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Department
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Sam Moffatt
 * @version SVN: $Id:$
 * @see JoomlaCode Project: http://joomlacode.org/gf/project/jauthtools/
 */

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
				$plugin = new $className ($this);
			} else {
				JError :: raiseWarning('SOME_ERROR_CODE', 'JAuthSSOAuthentication::doSSOAuth: Could not load ' . $className);
				continue;
			}

			// Try to authenticate remote user
			$username = $plugin->detectRemoteUser();

			// If authentication is successful break out of the loop
			if ($username != '') {
				if($autocreate) {
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
			
			$options = Array();
			$app =& JFactory::getApplication();
			if($app->isAdmin()) {
				// The minimum group
				$options['group'] = 'Public Backend';
			}
			
			//Make sure users are not autoregistered
			$options['autoregister'] = false;
			
			// Import the user plugin group
			JPluginHelper::importPlugin('user');

			// OK, the credentials are authenticated.  Lets fire the onLogin event
			$results = $this->triggerEvent('onLoginUser', array($result, $options));
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
}
