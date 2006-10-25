<?php

/**
 * LDAP Single Sign In (Integrated Authentication) for Joomla! 1.0.x
 * 
 * This is a system mambot that provides synchronization services to
 * sync the LDAP password with the Joomla! one checking that it is valid.
 * After this occurs the normal system signin occurs transparently. 
 * 
 * PHP4
 * MySQL 4
 *  
 * Created on Sep 28, 2006
 * 
 * @package LDAP Tools
 * @subpackage SSI
 * @author Sam Moffatt <S.Moffatt@toowoomba.qld.gov.au>
 * @author Toowoomba City Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2006 Toowoomba City Council/Sam Moffatt 
 * @version SVN: $Id:$
 * @see Joomla!Forge Project: http://forge.joomla.org
 */

// no direct access
defined('_VALID_MOS') or die('Restricted access');

$_MAMBOTS->registerFunction('onAfterStart', 'botLDAPSSI');

/**
 * LDAP Single Sign In Procedure
 * @return bool status; these bots are not monitored like auth bots in 1.5
 */
function botLDAPSSI() {
	global $database, $option, $mainframe, $acl, $_MAMBOTS, $_LANG;
	if ($option != 'login') { // don't run
		return 0;
	}

	$username = stripslashes(strval(mosGetParam($_REQUEST, 'username', '')));
	$passwd = stripslashes(strval(mosGetParam($_REQUEST, 'passwd', '')));
	$password = md5($passwd);
	if ($username && $passwd) {
		// load mambot parameters
		$query = "SELECT params FROM #__mambots WHERE element = 'ldap.ssi' AND folder = 'system'";
		$database->setQuery($query);
		$params = $database->loadResult();
		$mambotParams = & new mosParameters($params);
		$ldap = null;	// bad c habbit
		if ($mambotParams->get('useglobal')) {
			$ldap = new ldapConnector();
		} else {
			$ldap = new ldapConnector($mambotParams);
		}

		if (!$ldap->connect()) {
			//echo "<h1>Failed to connect to LDAP server!</h1>";
			return 0;
		}

		$success = $ldap->bind($username, $passwd);
		// Success, sync or create
		if ($success) {
			$query = "SELECT `id`" .
			"\nFROM `#__users`" .
			"\nWHERE username=" . $database->Quote($username);

			$database->setQuery($query);
			$userId = intval($database->loadResult());
			if ($userId < 1) {
				// Create user 
				$user = new mosUser($database);
				$user->username = $username;
				$ldap->populateUser($user,$mambotParams->get('groupMap'));
				$user->id = 0;
				$user->password = md5($passwd);
				$row->registerDate 	= date( 'Y-m-d H:i:s' );
				$user->store();
			} else {
				// Synchronize their password            
				$query = "UPDATE `#__users` SET password = '" . md5($passwd) . "' WHERE username = '$username'";
				$database->setQuery($query);
				$database->Query();
			}
		}
		$ldap->close();
	}
	return true;
}
?>
