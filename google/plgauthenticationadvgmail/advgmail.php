<?php

/**
 * @version		$Id: gmail.php 11376 2008-12-31 00:14:24Z eddieajau $
 * @package		Joomla
 * @subpackage	JFramework
 * @copyright	Copyright (C) 2005 - 2008 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License, see LICENSE.php
  */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

/**
 * GMail Authentication Plugin
 *
 * @package		Joomla
 * @subpackage	JFramework
 * @since 1.5
 */
class plgAuthenticationAdvGMail extends JPlugin {
	/**
	 * This method should handle any authentication and report back to the subject
	 *
	 * @access	public
	 * @param   array 	$credentials Array holding the user credentials
	 * @param 	array   $options	 Array of extra options
	 * @param	object	$response	Authentication response object
	 * @return	boolean
	 * @since 1.5
	 */
	function onAuthenticate($credentials, $options, & $response) {
		$message = '';
		$success = 0;
		$use_contexts = $this->params->get('use_contexts',0);
		
		// check if we have curl or not
		if (function_exists('curl_init')) {
			// check if we have a username and password
			if (strlen($credentials['username']) && strlen($credentials['password'])) {
				$blacklist = explode(',', $this->params->get('user_blacklist', ''));
				// check if the username isn't blacklisted
				if (!in_array($credentials['username'], $blacklist)) {
					$suffix = $this->params->get('suffix', '');
					$applysuffix = $this->params->get('applysuffix', 0);
					// check if we're using contexts or not, contexts override suffixes
					if($use_contexts) {
						// lets do the context thing then
						$context = JRequest::getInt('context',-1);
						if($context > -1) {
							jimport('jauthtools.helper');
							$contexts = JAuthToolsHelper::getContexts();
							if(array_key_exists($context, $contexts)) {
								$offset = strpos($credentials['username'], '@');
								if ($offset && $applysuffix == 2) {
									// if we already have an @, get rid of it
									$credentials['username'] = substr($credentials['username'], 0, $offset);
								}
								// add the selected context
								$credentials['username'] .= '@'. $contexts[$context];
							}
						}
					} else {
						// check if we want to do suffix stuff, typically for Google Apps for Your Domain
						if ($suffix && $applysuffix) {
							$offset = strpos($credentials['username'], '@');
							if ($offset && $applysuffix == 2) {
								// if we already have an @, get rid of it and replace it
								$credentials['username'] = substr($credentials['username'], 0, $offset);
							}
							// apply the suffix
							$credentials['username'] .= '@' . $suffix;
						}						
					}
					
					$curl = curl_init('https://mail.google.com/mail/feed/atom');
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					//curl_setopt($curl, CURLOPT_HEADER, 1);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->params->get('verifypeer', 1));
					curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
					curl_setopt($curl, CURLOPT_USERPWD, $credentials['username'] . ':' . $credentials['password']);
					$result = curl_exec($curl);
					$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

					switch ($code) {
						case 200 :
							$message = 'Access Granted';
							$success = 1;
							break;
						case 401 :
							$message = 'Access Denied';
							break;
						default :
							$message = 'Result unknown, access denied.';
							break;
					}
				} else {
					// the username is black listed
					$message = 'User is blacklisted';
				}
			} else {
				$message = 'Username or password blank';
			}
		} else {
			$message = 'curl isn\'t insalled';
		}

		if ($success) {
			$response->status = JAUTHENTICATE_STATUS_SUCCESS;
			$response->error_message = '';
			if (strpos($credentials['username'], '@') === FALSE) {
				if ($suffix) { // if there is a suffix then we want to apply it
					$response->email = $credentials['username'] . '@' . $suffix;
				} else { // if there isn't a suffix just use the default gmail one
					$response->email = $credentials['username'] . '@gmail.com';
				}
			} else { // the username looks like an email address (probably is) so use that
				$response->email = $credentials['username'];
			}
			$response->fullname = $credentials['username'];
			$response->username = $credentials['username'];
		} else {
			$response->status = JAUTHENTICATE_STATUS_FAILURE;
			$response->error_message = 'Failed to authenticate: ' . $message;
		}
	}
}
