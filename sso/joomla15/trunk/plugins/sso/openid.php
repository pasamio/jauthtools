<?php
jimport('joomla.plugin.plugin');

class plgSSOOpenid extends JPlugin {
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
	function plgSSOOpenid(& $subject, $params) {
		parent :: __construct($subject, $params);
	}

	function getSSOPluginType() {
		return 'C';
	}

	function detectRemoteUser() {
		$mainframe = & JFactory :: getApplication();

		$this->_detectRandom();
		jimport('jauthtools.openid.consumer');
		jimport('joomla.filesystem.folder');

		// Create and/or start using the data store
		$store_path = JPATH_ROOT . '/tmp/_sso_openid_store';
		if (!JFolder :: exists($store_path) && !JFolder :: create($store_path)) {
			return false;
		}
		// Create store object
		$store = new Auth_OpenID_FileStore($store_path);
		$session = & JFactory :: getSession();
		// Create a consumer object
		$consumer = new Auth_OpenID_Consumer($store);
		$remote_user = JRequest :: getVar('remote_username', '');

		if (!isset ($_SESSION['_openid_consumer_last_token'])) {
			// Begin the OpenID authentication process.
			if (!$auth_request = $consumer->begin($remote_user)) {
				return false;
			}
			$policies = $this->_getPolicies();
			$pape_request = new Auth_OpenID_PAPE_Request($policies);
			if ($pape_request) {
				$auth_request->addExtension($pape_request);
			}
			$result = JComponentHelper :: getComponent('com_ssohelper', true);

			if ($result->enabled) {
				$link = JURI :: base() . '/index.php?option=com_ssomanager&task=delegate&plugin=openid';
			} else {
				$link = JURI :: base();
			}
			$session->set('return_url', $link);
			$trust_url = JURI :: base();
			if ($auth_request->shouldSendRedirect()) {
				$redirect_url = $auth_request->redirectURL($trust_url, $link);

				if (Auth_OpenID :: isFailure($redirect_url)) {
					return false;
				} else {
					// Send redirect.
					$mainframe->redirect($redirect_url);
					return false;
				}
			} else {
				// Generate form markup and render it.
				$form_id = 'openid_message';
				$form_html = $auth_request->htmlMarkup($trust_url, $link, false, array (
					'id' => $form_id
				));
				// Display an error if the form markup couldn't be generated;
				// otherwise, render the HTML.
				if (Auth_OpenID :: isFailure($form_html)) {
					return false;
				} else {
					JResponse :: setBody($form_html);
					echo JResponse :: toString($mainframe->getCfg('gzip'));
					$mainframe->close();
					return false;
				}
			}
		}
		$response = $consumer->complete($session->get('return_url'));
		switch ($response->status) {
			case Auth_OpenID_SUCCESS :
				$sreg_resp = Auth_OpenID_SRegResponse :: fromSuccessResponse($response);

				$sreg = $sreg_resp->contents();

				$userdetails = new stdClass();
				$userdetails->username = $response->getDisplayIdentifier();

				if (!isset ($sreg['email'])) {
					$userdetails->email = str_replace(array (
						'http://',
						'https://'
					), '', $userdetails->username);
					$userdetails->email = str_replace('/', '-', $userdetails->email);
					$userdetails->email .= '@openid.';
				} else {
					$userdetails->email = $sreg['email'];
				}
				$userdetails->name = isset ($sreg['fullname']) ? $sreg['fullname'] : $userdetails->username;
				$userdetails->language = isset ($sreg['language']) ? $sreg['language'] : '';
				$userdetails->timezone = isset ($sreg['timezone']) ? $sreg['timezone'] : '';

				$session = & JFactory :: getSession();
				$sessiondetails = & $session->get('UserSourceDetails', Array ());
				$sessiondetails[] = $userdetails;
				$session->set('UserSourceDetails', $sessiondetails);
				return $userdetails->username;
				break;
			default :
				return false;
				break;
		}

	}

	function getForm() {
		return '<form method="post" action="' . JURI :: base() . '">' . 'Requested Username: ' . '<input type="text" name="remote_username" value="" />' . '<input type="submit" value="Login" /></form>';
	}

	function _getPolicies() {
		$policy_uris = array ();
		if ($this->params->get('phishing-resistant', 0)) {
			$policy_uris[] = 'http://schemas.openid.net/pape/policies/2007/06/phishing-resistant';
		}

		if ($this->params->get('multi-factor', 0)) {
			$policy_uris[] = 'http://schemas.openid.net/pape/policies/2007/06/multi-factor';
		}

		if ($this->params->get('multi-factor-physical', 0)) {
			$policy_uris[] = 'http://schemas.openid.net/pape/policies/2007/06/multi-factor-physical';
		}
		return $policy_uris;
	}

	function _detectRandom() {
		if (!defined('Auth_OpenID_RAND_SOURCE')) {
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				define('Auth_OpenID_RAND_SOURCE', null);
			} else {
				$f = @ fopen('/dev/urandom', 'r');
				if ($f !== false) {
					define('Auth_OpenID_RAND_SOURCE', '/dev/urandom');
					fclose($f);
				} else {
					$f = @ fopen('/dev/random', 'r');
					if ($f !== false) {
						define('Auth_OpenID_RAND_SOURCE', '/dev/urandom');
						fclose($f);
					} else {
						define('Auth_OpenID_RAND_SOURCE', null);
					}
				}
			}
		}
	}
}