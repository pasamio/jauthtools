<?php
/**
 * SSO JAuthTools SimpleSSO Plugin 
 * 
 * This file handles Simple SSO 
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
jimport('jauthtools.sso'); // should be included already

/**
 * SSO SimpleSSO
 * Attempts to match a user based on a key which is valid with SimpleSSO
 */
class plgSSOSimpleSSO extends JPlugin {
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
	function plgSSOSimpleSSO(& $subject, $params) {
		parent :: __construct($subject, $params);
	}

	function detectRemoteUser() {
		$providers = JAuthSSOAuthentication::getProvider('simplesso');
		foreach($providers as $provider) {
			return $this->_detectUser($provider);
		}
		
		return false;
	}
	
	function _detectUser($instance) {
		$key = JRequest::getVar('authkey','');
		$params = new JParameter($instance->params);
 	 	$supplier = $params->getValue('supplier',''); 
		$suffix = $params->getValue('suffix','');

		// grab the file; check the supplier and key are set to something
		if(function_exists('curl_init') && $supplier && $key)
		{
			$url = $supplier.'/?token='.$key;
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);				
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			$result = curl_exec($curl);
			$xml =& JFactory::getXMLParser('Simple');
			$xml->loadString($result);
			$rootAttr = $xml->document->attributes();
			if(isset($rootAttr['type']) && $rootAttr['type'] == 'user') {
				$children = $xml->document->children();
				foreach($children as $child) {
					if($child->name() == 'user') {
						$userattr = $child->attributes();
						$userdetails = new stdClass();
						$userdetails->username = str_replace($suffix,'',$userattr['username']);
						$userdetails->name = $userattr['name'];
						$userdetails->email = $userattr['email'];
						
						$session =& JFactory::getSession();
						$sessiondetails =& $session->get('UserSourceDetails',Array());
						$sessiondetails[] = $userdetails;
						$session->set('UserSourceDetails', $sessiondetails);
						return $userdetails->username;
					}	
				}
			}
		}
	}
	
	function getSPLink($instance) {
		$instance_params = new JParameter($instance->params);
		$params = clone($this->params); // take a copy of this to prevent the instance overloading the default
		$params->merge($instance_params); // merge over the new params
		$supplier = $params->get('supplier');
		
		$base = JAuthSSOAuthentication::getBaseURL($params->get('prefer_component',true),'simplesso');
		if(strpos($supplier, '?')) {
			$supplier .= '&landingpage='. $base;
		} else {
			$supplier .= '?landingpage='. $base;
		}
		return $supplier;
	}
}
