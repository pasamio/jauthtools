<?php
/**
* @version		$Id: example.php 7180 2007-04-23 16:51:53Z jinx $
* @package		Joomla
* @subpackage	JFramework
* @copyright	Copyright (C) 2005 - 2007 Open Source Matters. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.event.plugin');


/**
 * Remote Joomla Plugin
 *
 * @author Sam Moffatt <sam.moffatt@joomla.org>
 * @package		Joomla
 * @subpackage	JFramework
 * @since 1.5
 */
class plgAuthenticationRemoteJoomla extends JPlugin
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
	function plgAuthenticationRemoteJoomla(& $subject) {
		parent::__construct($subject);
	}

	/**
	 * This method should handle any authentication and report back to the subject
	 *
	 * @access	public
	 * @param	string	$username	Username for authentication
	 * @param	string	$password	Password for authentication
	 * @param	object	$response	Authentication response object
	 * @return	boolean
	 * @since 1.5
	 */
	function onAuthenticate( $username, $password, &$response )
	{
		/*
		 * Here you would do whatever you need for an authentication routine with the credentials
		 *
		 * In this example the mixed variable $return would be set to false
		 * if the authentication routine fails or an integer userid of the authenticated
		 * user if the routine passes
		 */
		// Prevent recursion
		if(defined('_REMOTE_AUTH')) { $response->status = JAUTHENTICATE_STATUS_FAILURE; $response->message = "Remote Auth already active"; return false; }
		// Params
		$suffix = '';//'.localdomain';
		// Grab details from the username
		// Example: pasamio@localhost/~pasamio/workspace/joomla_trunk
		//echo 'Attempting with '. print_r($username,1) .'<br>';
		$response->username = trim($username,'/');		// Chop off last / where relevant
		$parts = explode('@', $username);
		$username = $parts[0];
		if(count($parts) > 1) {
			// There should be at least one part, or there should be two or more parts => 2
			$parts = explode('/',$parts[1]);
			$host = array_shift($parts); // get rid of the first element
			$path = implode('/',$parts); // And put it back together
		} else {
			$response->status = JAUTHTENTICATE_STATUS_FAILURE;
			return false;
		}
		// Create XML RPC Object and Connect to server
		jimport('phpxmlrpc.xmlrpc');
		
		$client=new xmlrpc_client($path.'/xmlrpc/index.php', $host.$suffix);
		// Create message and ask for auth
		//$client->return_type = 'xmlrpcvals';
		$client->setDebug(1);
		$msg =& new xmlrpcmsg('joomla.remoteAuth');
		$username =& new xmlrpcval($username, 'string');
		$password =& new xmlrpcval($password, 'string');
		$msg->addparam($username);
		$msg->addparam($password);		
		$result =& $client->send($msg, 0, '');
		if ($result->faultcode()) { $response->status = JAUTHENTICATE_STATUS_FAILURE; return false;}
//		echo print_r(php_xmlrpc_decode($result->value())); die();
//		print_r($result); die();
		$data = php_xmlrpc_decode($result->value());
		$response->email = $data['email'];
		$response->fullname = $data['fullname'];
		switch($result->errno) {
			case 5:
				// Wrong URL (.htm not .php ?)
			case 3:
				// Wrong param count, shouldn't happen
			case 1:
				// Unknown method, not enabled
				break;
			case 0:
				
				break;
			
		}
		// Return result
		$response->status 	= JAUTHENTICATE_STATUS_SUCCESS;
//		print_r($response); die();
	}
}

?>
