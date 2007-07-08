<?php

/**
 * HTTP Auth Mambot
 * 
 * This file interconnects with  
 * 
 * PHP4/5
 *  
 * Created on Apr 18, 2007
 * 
 * @package Joomla! Authentication Tools
 * @author Sam Moffatt <pasamio@gmail.com>
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2007 Sam Moffatt 
 * @version SVN: $Id:$
 * @see JoomlaCode Project: http://joomlacode.org/gf/project/jauthtools/
 */

/** ensure this file is being included by a parent file */
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
			$logentry->store() or die('Log entry save failed');
		}
	}
}

if (!function_exists('ldap_connect')) {
	addLogEntry('HTTP SSO Mambot', 'authentication', 'crit', 'PHP LDAP Library not detected');
} else
	if (!class_exists('ldapConnector')) {
		addLogEntry('HTTP SSO Mambot', 'authentication', 'crit', 'Joomla! LDAP Library not detected');
	} else {
		$_MAMBOTS->registerFunction('onAfterStart', 'botDoHTTPSSOLogin');
	}

/**
 * Initiates a login 
 *
 * Initiates a login for Joomla! Uses the remote user.
 * Single Sign On System.
 */
function botDoHTTPSSOLogin() {
	global $database, $mainframe, $acl, $_MAMBOTS, $_LANG;
	// load mambot parameters
	$query = "SELECT params FROM #__mambots WHERE element = 'httpsso' AND folder = 'system'";
	$database->setQuery($query);
	$params = $database->loadResult();
	$mambotParams = & new mosParameters($params);
	$remoteuser = $mambotParams->get('userkey');
	$username = mosGetParam($_SERVER, $remoteuser, null);
	if ($username != NULL) {
		// Has a remote_user field set, get the username and attempt to sign them in.
		$replacement = $mambotParams->get('username_replacement');
		foreach (explode('|', $mambotParams->get('username_replacement')) as $replace) {
			$username = str_replace($replace, '', $username);
		}

		//load user bot group
		$query = 'SELECT id FROM #__users WHERE username=' . $database->Quote($username);
		$database->setQuery($query);
		$userId = intval($database->loadResult());
		$user = new mosUser($database);
		if ($userId < 1) {
			if ($mambotParams->get('useglobal',1)) {
				$ldap = new ldapConnector();
			} else {
				$ldap = new ldapConnector($mambotParams);
			}
			$ldap->connect();
			$ldap->bind();
			if (intval($mambotParams->get('autocreate'))) {
				// Create user 
				$user->username = $username;
				$ldap->populateUser($user, $mambotParams->get('groupMap'));
				$user->id = 0;
				$row->registerDate = date('Y-m-d H:i:s');
				if ($user->usertype == 'Public Frontend' && !$mambotParams->get('autocreateregistered')) {
					addLogEntry('HTTP SSO Mambot', 'authentication', 'notice', 'User creation halted for ' . $username . ' since they would only be registered');
					//$ldap->close();
					return false;
				} else {
					if (!$user->store()) {
						addLogEntry('HTTP SSO Mambot', 'authentication', 'err', 'Could not autocreate user:' . print_r($user, 1));
					}
				}
			}
		} else
			if ($userId) {
				$user->load(intval($userId));
			} else {
				//$ldap->close();
				return false;
			}

		// check to see if user is blocked from logging in (ignored)
		if ($user->block == 1) {
			//$ldap->close();
			addLogEntry('HTTP SSO Mambot', 'authentication','notice', 'User '. $user->username .' attempted to login whilst blocked');
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
		$query = "SELECT id, name, username, password, usertype, block, gid" . "\n FROM #__users" . "\n WHERE id = $userid";
		$row = null;
		$database->setQuery($query);
		$database->loadObject($row);
		$lifetime = time() + 365 * 24 * 60 * 60;
		$remCookieName = mosMainFrame :: remCookieName_User();
		$remCookieValue = mosMainFrame :: remCookieValue_User($row->username) . mosMainFrame :: remCookieValue_Pass($row->password) . $row->id;
		setcookie($remCookieName, $remCookieValue, $lifetime, '/');
		$session->store();
		// update user visit data
		$currentDate = date("Y-m-d\TH:i:s");

		$query = "UPDATE #__users" . "\n SET lastvisitDate = " . $database->Quote($currentDate) . "\n WHERE id = " . (int) $session->userid;
		$database->setQuery($query);
		$database->Query();

		mosCache :: cleanCache();
		return true;
	} else {
		return false;
	}
}
?>