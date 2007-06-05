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

if (!function_exists('addLogEntry')) {
	function addLogEntry($application, $type, $priority, $message) {
		if (defined('_JLOGGER_API')) {
			global $database;
			$logentry = new JLogEntry($database);
			$logentry->application = $application;
			$logentry->type = $type;
			$logentry->priority = $priority;
			$logentry->message = $message;
			$logentry->store() or die('Log entry save failed: ' . $message);
		}
	}
}

if (!function_exists('ldap_connect')) {
	addLogEntry('LDAP SSI Mambot', 'authentication', 'crit', 'PHP LDAP Library not detected');
} else
	if (!class_exists('ldapConnector')) {
		addLogEntry('LDAP SSI Mambot', 'authentication', 'crit', 'Joomla! LDAP Library not detected');
	} else {
		$_MAMBOTS->registerFunction('onAfterStart', 'botLDAPSSI');
	}

/**
 * Attempt to authenticate a user
 * @param object LDAP Connector
 * @param string auth_methord Authentication method to use
 * @param string users_dn The user dn to use
 * @param string username The username that we're interested in
 * @param string password The password that we're interested in checking
 * @param string ldap_uid LDAP User ID (used for authbind method)
 * @param string ldap_password LDAP Password (for above)
 */
function botLDAPSSI_AttemptLogin(& $ldap, $auth_method, $users_dn, $username, $password, $ldap_uid = '', $ldap_password = '') {
	$success = 0;
	switch ($auth_method) {
		case 'authenticated' :
		case 'anonymous' :
		case 'compare':
			// Need to do some work!
			if ($ldap->bind()) {
				// Comparison time
				$success = $ldap->compare(str_replace("[username]", $username, $users_dn, $ldap_password, $password));
			} else {
				addLogEntry('LDAP SSI Mambot', 'authentication', 'err', 'Prebind failed before compare. Note: MSAD requires an auth user for searches by default; check credentials.');
				return 0;
			}
			break;

		case 'authbind' :
		case 'search':
			// First bind as a search enabled account
			if ($ldap->bind()) {
				$userdetails = $ldap->simple_search($ldap_uid . '=' . $username, $users_dn);
				if (isset ($userdetails[0][$ldap_uid][0])) {
					$success = $ldap->bind($userdetails[0][dn], $password, 1);
				} else addLogEntry('LDAP SSI Mambot', 'authentication', 'err', 'Search for user ' . $username . ' failed in DN: '. $users_dn);
			} else {
				addLogEntry('LDAP SSI Mambot', 'authentication', 'err', 'Prebind failed before search and bind; check credentials!');
			}
			break;

		case 'bind' :
			// We just accept the result here
			$success = $ldap->bind($username, $password);
			break;			
	}
	return $success;
}

/**
 * LDAP Single Sign In Procedure
 * @return bool status; these bots are not monitored like auth bots in 1.5
 */
function botLDAPSSI() {
	global $database, $option, $mainframe, $acl, $_MAMBOTS, $_LANG;
	$task = mosGetParam($_GET, 'task', '');
	$submit = mosGetParam($_POST,'submit', '');
	if ($option != 'login' && // don't run
	 ($option != 'com_comprofiler' && $task != 'login') &&
	 ($submit != 'Login')) { // added community builder support
		return 0;
	}
	$success = 0; // Ensure this is zero
	$username = stripslashes(strval(mosGetParam($_REQUEST, 'username', '')));
	$passwd = stripslashes(strval(mosGetParam($_REQUEST, 'passwd', '')));
	$usrname = stripslashes(strval(mosGetParam($_REQUEST, 'usrname', '')));
	$pass = stripslashes(strval(mosGetParam($_REQUEST, 'pass', '')));
	$username = $username ? $username : $usrname;
	$passwd = $passwd ? $passwd : $pass;
	$password = & $passwd;

	if ($username && $passwd) {
		// load mambot parameters
		$query = "SELECT params FROM #__mambots WHERE element = 'ldap.ssi' AND folder = 'system'";
		$database->setQuery($query);
		$params = $database->loadResult();
		$mambotParams = & new mosParameters($params);
		$ldap = null; // bad c habbit
		if ($mambotParams->get('useglobal')) {
			$ldap = new ldapConnector();
		} else {
			$ldap = new ldapConnector($mambotParams);
		}

		addLogEntry('LDAP SSI Mambot', 'authentication', 'notice', '!!! Starting authentication procedure !!!');
		
		if (!$ldap->connect()) {
			//echo "<h1>Failed to connect to LDAP server!</h1>";
			addLogEntry('LDAP SSI Mambot', 'authentication', 'err', 'Failed to connect to LDAP Server');
			return 0;
		}
		$auth_method = $mambotParams->get('auth_method', 'bind');
		$user_dn_list = explode(';', $mambotParams->get('search_dn'));
		$ldap_uid = $mambotParams->get('ldap_uid');
		$ldap_password = $mambotParams->get('ldap_password');
		foreach ($user_dn_list as $user_dn) {
			$success = $success || botLDAPSSI_AttemptLogin($ldap, $auth_method, $user_dn, $username, $passwd, $ldap_uid, $ldap_password);
			if ($success)
				break;
		}
		
		if ($success) {
			$query = "SELECT `id`" .
			"\nFROM `#__users`" .
			"\nWHERE username=" . $database->Quote($username);
			$user = new mosUser($database);
			$database->setQuery($query);
			$userId = intval($database->loadResult());
			if ($userId < 1) {
				if (intval($mambotParams->get('autocreate'))) {
					// Create user 
					$user->username = $username;
					// bind/authbind we know who they are (minor optimization)
					if ($auth_method == 'bind' || $auth_method == 'authbind')
						$ldap->populateUser($user, $mambotParams->get('groupMap'), $ldap->getDN());
					else
						$ldap->populateUser($user, $mambotParams->get('groupMap'));
					$user->id = 0;
					$user->password = md5($passwd);
					$row->registerDate = date('Y-m-d H:i:s');
					if ($user->usertype == 'Public Frontend' && !$mambotParams->get('autocreateregistered')) {
						addLogEntry('LDAP SSI Mambot', 'authentication', 'notice', 'User creation halted for ' . $username . ' since they would only be registered');
						$ldap->close();
						return false;
					} else {
						$user->store(); // or die('Could not autocreate user:' . print_r($user, 1));
						if ($option == 'com_comprofiler' && $mambotParams->get('cbconfirm')) {
							$database->setQuery('INSERT INTO #__comprofiler (id, user_id, hits, message_number_sent, avatarapproved, approved, confirmed, banned, acceptedterms) VALUES (' . $user->id . ',' . $user->id . ',0,0,1,1,1,0,1)');
							$database->Query() or die($database->getErrorMsg());
						}
						addLogEntry('LDAP SSI Mambot', 'authentication', 'err', 'Autocreated user:' . print_r($user, 1));
					}
				}
			} else {
				if ($userId) {
					addLogEntry('LDAP SSI Mambot', 'authentication', 'notice', 'Updating user ' . $userId);
					//$user->load(intval($userId));
					$query = "UPDATE `#__users` SET password = '" . md5($passwd) . "' WHERE username = '$username'";
					$database->setQuery($query);
					$database->Query(); // or die($database->getErrorMsg());
				} else {
					$ldap->close();
					addLogEntry('LDAP SSI Mambot', 'authentication', 'notice', 'No user found?!?');
					//die('About to abort');
					return false;
				}
			}
		} else {
			// Extra check to to see if the user's password should be reset upon failure to bind.
			if ($mambotParams->get('forceldap')) {
				addLogEntry('LDAP SSI Mambot', 'authentication', 'notice', 'Resetting password for ' . $username . ' to enforce LDAP Authentication');
				$query = "UPDATE `#__users` SET password = '' WHERE username = '$username'";
				$database->setQuery($query);
				$database->Query();
			}
			addLogEntry('LDAP SSI Mambot', 'authentication', 'notice', 'LDAP Authentication failed for ' . $username);
			$ldap->close();
		}
		return true;
	} else {
		addLogEntry('LDAP SSI Mambot', 'authentication', 'notice', 'Warning, no username and password detected for SSI event!');
		return false; // No username and password
	}
}
?>
