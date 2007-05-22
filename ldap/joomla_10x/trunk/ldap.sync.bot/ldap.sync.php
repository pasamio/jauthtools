<?php

/**
 * LDAP Synchronziation Bot
 * 
 * This bot will reset any group membership to match that in the LDAP directory 
 * 
 * PHP4
 * MySQL 4
 *  
 * Created on Oct 3, 2006
 * 
 * @package LDAP Tools
 * @subpackage Sync
 * @author Sam Moffatt <S.Moffatt@toowoomba.qld.gov.au>
 * @author Toowoomba City Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2006 Toowoomba City Council/Developer Name 
 * @version SVN: $Id:$
 * @see Joomla!Forge Project: http://forge.joomla.org/sf/sfmain/do/viewProject/projects.ldap_tools
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
	addLogEntry('LDAP Sync Mambot', 'authentication', 'crit', 'PHP LDAP Library not detected');
} else
	if (!class_exists('ldapConnector')) {
		addLogEntry('LDAP Sync Mambot', 'authentication', 'crit', 'Joomla! LDAP Library not detected');
	} else {
		$_MAMBOTS->registerFunction('onAfterStart', 'botDoLdapSync');
	}

/**
 * Initiates a LDAP Sync
 *
 * Initiates a LDAP Synchronization. A user is checked and the system redirects if required
 */
function botDoLdapSync() {
	global $my, $database, $mainframe, $acl;
	// Do stuff :D
	// Step 1: Check if they're logged in
	//print_r($mainframe->_session);
	if ($mainframe->_session->userid) {
		// Step 2: Connect to LDAP
		// load mambot parameters
		$query = "SELECT params FROM #__mambots WHERE element = 'ldap.sync' AND folder = 'system'";
		$database->setQuery($query);
		$params = $database->loadResult();
		$mambotParams = & new mosParameters($params);
		$ldap = null; // bad c habbit
		if ($mambotParams->get('useglobal')) {
			$ldap = new ldapConnector();
		} else {
			$ldap = new ldapConnector($mambotParams);
		}

		if ($ldap->connect()) {
			if ($ldap->bind()) { // Anonymous bind
				// Set up some variables we'll need shortly
				// Main user
				$row = new mosUser($database);
				// Dummy user
				$tmp = new mosUser($database);
				// Set the username...
				$row->load($mainframe->_session->userid);
				$tmp->load($mainframe->_session->userid);

				// Step 3: Find out their LDAP info
				$ldap->populateUser($tmp, $mambotParams->get('groupMap'));
				$tmp->id = $mainframe->_session->userid; // rewrite userid since ldap removes it
				// Step 4: Check if they're not blocked and ensure they're blocked
				if ($tmp->block == 1) {
					$row->block = 1;
					$row->update();
					mosErrorAlert(_LOGIN_BLOCKED);
				}

				// Step 5: Check they're group membership
				// - We create a new user object and tell LDAP to populate it
				// - Then we check the usertype against the one in the current session
				// - If its the same no worries, if its different we change values ;)
				// fudge the group stuff
				if ($tmp->id && ($tmp->usertype != $row->usertype) || ($row->usertype != $mainframe->_session->usertype)) {
					//	echo 'resetting';
					$grp = $acl->getAroGroup($row->id);
					$row->gid = 1;
					if ($acl->is_group_child_of($grp->name, 'Registered', 'ARO') || $acl->is_group_child_of($grp->name, 'Public Backend', 'ARO')) {
						// fudge Authors, Editors, Publishers and Super Administrators into the Special Group
						$row->gid = 2;
					}
					$row->usertype = $grp->name;
					$mainframe->_session->usertype = $row->usertype;
					$mainframe->_session->gid = intval($row->gid);
					$mainframe->_session->update();

					$tmp->id = $row->id;
					$tmp->store();
				} else {
					/*echo '<pre>';
					print_R($tmp);
					print_R($row);
					print_R($mainframe->_session); die();*/
				}
			}
		}
	} //else die('No userid'); // else we don't care ;)
}
?>