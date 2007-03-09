<?php
/**
* @version $Id: kerberos.login.php,v 1.1 2005/08/17 13:37:38 pasamio Exp $
* @package Mambo
* @copyright (C) Samuel Moffatt/Toowoomba City Council
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* Mambo is Free Software
*/

/** ensure this file is being included by a parent file */
defined('_VALID_MOS') or die('Direct Access to this location is not allowed.');

$_MAMBOTS->registerFunction('onAfterStart', 'botDoKerberosLogin');

if(!function_exists('addLogEntry')) {
	function addLogEntry($application, $type, $priority, $message) {
		if(defined('_JLOGGER_API')) {
			global $database;
			$logentry = new JLogEntry($database);
			$logentry->application = $application;
			$logentry->type 		= $type;
			$logentry->priority 	= $priority;
			$logentry->message 	= $message;
			$logentry->store() or die('Log entry save failed');
		}
	}
}

if(!function_exists('ldap_connect')) {
	addLogEntry('Kerberos SSO Mambot', 'authentication','crit','PHP LDAP Library not detected');
} else if(!class_exists('ldapConnector')) {
	addLogEntry('Kerberos SSO Mambot', 'authentication','crit','Joomla! LDAP Library not detected');
} else {
	$_MAMBOTS->registerFunction('onAfterStart', 'botDoKerberosLogin');
}

/**
 * Initiates a Kerberos login
 *
 * Initiates a Kerberos login for Mambo. Work has been done by Apache already.
 */
function botDoKerberosLogin() {
	global $database, $mainframe, $acl, $_MAMBOTS, $_LANG;

	$username = mosGetParam($_SERVER, "REMOTE_USER", null);
	if ($username != NULL) {
			// Has a remote_user field set, get the username and attempt to sign them in.
			$parts = split('@', $username);
			$username = $parts[0];
			// load mambot parameters
			$query = "SELECT params FROM #__mambots WHERE element = 'kerberos.sso' AND folder = 'system'";
			$database->setQuery($query);
			$params = $database->loadResult();
			$mambotParams =& new mosParameters( $params );

			//load user bot group
			$query = 'SELECT id FROM #__users WHERE username=' . $database->Quote($username);
			$database->setQuery($query);
			$userId = intval($database->loadResult());
			$user = new mosUser($database);
			if ($userId < 1) {
				$ldap = new ldapConnector();
				$ldap->connect();
				$ldap->bind();
				if(intval($mambotParams->get('autocreate'))) {
					// Create user 
					$user->username = $username;
					$ldap->populateUser($user, $mambotParams->get('groupMap'));
					$user->id = 0;
					$row->registerDate 	= date( 'Y-m-d H:i:s' );
					if($user->usertype == 'Public Frontend' && !$mambotParams->get('autocreateregistered')) {
						addLogEntry('Kerberos SSO Mambot', 'authentication', 'notice', 'User creation halted for '. $username .' since they would only be registered');
						//$ldap->close();
						return false;
					} else {
						if(!$user->store()) {
							addLogEntry('Kerberos SSO Mambot', 'authentication', 'err', 'Could not autocreate user:'. print_r($user,1));
						}
					}
				}
			} else if($userId) {
				$user->load(intval($userId));
			} else {
				//$ldap->close();
				return false;
			}
				
			// check to see if user is blocked from logging in (ignored)
			if ($user->block == 1) {
				//$ldap->close();
				return false;
			}
			// fudge the group stuff
			$grp = $acl->getAroGroup($user->id);
			$user->gid = 1;

			if ($acl->is_group_child_of($grp->name, 'Registered', 'ARO') || $acl->is_group_child_of($grp->name, 'Public Backend', 'ARO')) {
				// fudge Authors, Editors, Publishers and Super Administrators into the Special Group
				$user->gid = 2;
			}
			$user->usertype = $grp->name;

			$session = & $mainframe->_session;
			$session->guest = 0;
			$session->username = $user->username;
			$session->userid = intval($user->id);
			$session->usertype = $user->usertype;
			$session->gid = intval($user->gid);
			$userid = $user->id;
			// Persistence
			$query = "SELECT id, name, username, password, usertype, block, gid"
			. "\n FROM #__users"
			. "\n WHERE id = $userid"
			;
			$row = null;
			$database->setQuery( $query );
			$database->loadObject($row);
			$lifetime               = time() + 365*24*60*60;
			$remCookieName  = mosMainFrame::remCookieName_User();
			$remCookieValue = mosMainFrame::remCookieValue_User( $row->username ) . mosMainFrame::remCookieValue_Pass( $row->password ) . $row->id;
			setcookie( $remCookieName, $remCookieValue, $lifetime, '/' );
			$session->store();
			// update user visit data
			$currentDate = date("Y-m-d\TH:i:s");

			$query = "UPDATE #__users"
			. "\n SET lastvisitDate = ". $database->Quote( $currentDate )
			. "\n WHERE id = " . (int) $session->userid
			;
			$database->setQuery($query);
			$database->Query();
			
			mosCache :: cleanCache();
			return true;
	} else {
		return false;
	}
}
?>
