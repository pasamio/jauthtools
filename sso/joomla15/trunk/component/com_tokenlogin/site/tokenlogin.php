<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Nov 27, 2008
 * 
 * @package package_name
 * @author Your Name <author@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Developer Name 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */

$key = JRequest::getVar('logintoken', '');
// aW5kZXgucGhw is 'index.php' in base64
$redirect = base64_decode(JRequest::getVar('redirect', 'aW5kZXgucGhw'));
$app =& JFactory::getApplication();
if(!$key) {
	echo '<p>There is no spoon</p>';
	$app->redirect('index.php');
} else {
	jimport('jauthtools.token');
	$token = JAuthToolsToken::validateToken($key);
	if(!$token) {
		$app->redirect($redirect,JText::_('Invalid Token'));
	}
	
	$username = $token->username;
	
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
			if($token->landingpage) {
				$app->redirect($token->landingpage);
			} else {
				$app->redirect($redirect);
			}
		} else {
			$this->triggerEvent('onLoginFailure', array($result));
			$app->redirect($redirect, JText::_('Login failed'));
		}
	} else {
		$app->redirect($redirect,JText::_('Invalid username'));
	}
}