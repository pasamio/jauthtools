<?php
/**
* @version		$Id: joomla.php 7180 2007-04-23 16:51:53Z jinx $
* @package		JAuthTools
* @copyright	Copyright (C) 2005 - 2007 Open Source Matters. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.event.plugin');

/**
 * Joomla! Base XML-RPC Plugin
 *
 * @author Sam Moffatt <sam.moffatt@joomla.org>
 * @package XML-RPC
 * @since 1.5
 */
class plgXMLRPCRemoteJoomla extends JPlugin
{

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
	function plgXMLRPCRemoteJoomla(& $subject) {
		parent::__construct($subject);
	}

	/**
	 * Get available web services for this plugin
	 *
	 * @access	public
	 * @return	array	Array of web service descriptors
	 * @since	1.5
	 */
	function onGetWebServices()
	{
		global $xmlrpcString;

		// Initialize variables
		$services = array();

		// Site search service
		$services['joomla.remoteAuth'] = array(
			'function' => 'plgXMLRPCRemoteJoomlaAuthServices::remoteAuth',
			'docstring' => 'Authorizes a user.',
			'signature' => array(array($xmlrpcString, $xmlrpcString, $xmlrpcString))
			);

		return $services;
	}
}

/**
 * XML RPC Authentication Host
 * @package JAuthTools
 * @subpackage SSO
 */
class plgXMLRPCRemoteJoomlaAuthServices
{
	/**
	 * Remote User Authentication
	 * This fucntion authenticates a local user
	 */
	function remoteAuth($username, $password) {
		// Set this here so that we prevent recursion later
		define('_REMOTE_AUTH', 1);
		$result = '';
		jimport( 'joomla.user.authentication');
		$auth = & JAuthentication::getInstance();
		$result = $auth->authenticate(Array('username'=>$username,'password'=>$password), Array());
		$data = array();
		$data['email'] 		= $result->email;
		$data['username'] 	= $result->username;
		$data['status'] 	= $result->status;
		$data['fullname'] 	= $result->fullname;
		return php_xmlrpc_encode($data);
	}
}
