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
 * @author Sam Moffatt <sam.moffatt@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Sam Moffatt 
 * @version SVN: $Id:$
 * @see Joomla!Forge Project: http://forge.joomla.org/sf/sfmain/do/viewProject/projects.ldap_tools
 */

/** ensure this file is being included by a parent file */
// no direct access
defined('_VALID_MOS') or die('Restricted access');

if (!function_exists('addLogEntry')) {
	/**
	 * Add a log entry to the supported logging system
	 * @package JCMU
	 * @ignore
	 */
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
		if ($mambotParams->get('useglobal',1)) {
			$ldap = new ldapConnector();
			$mambotParams = $ldap->getParams();
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

				// Step 5: Check demotion system
				// Check if we can demote users, if yes continue regardless
				// If not, we need to ensure that the user is a higher position
				if($mambotParams->get('demoteuser',1) || // we can demote our user
					$acl->is_group_child_of($tmp->usertype, $row->usertype, 'ARO') || // our user is a child of our old group (not a demotion)
					($acl->is_group_child_of($row->usertype, 'Public Frontend', 'ARO') && $acl->is_group_child_of($tmp->usertype, 'Public Backend')) // user was in the front end and is now in the back end
					) {
					// Step 6: Check their group membership
					// - We create a new user object and tell LDAP to populate it
					// - Then we check the usertype against the one in the current session
					// - If its the same no worries, if its different we change values ;)
					// fudge the group stuff
					if ($tmp->id && ($tmp->usertype != $row->usertype) || ($row->usertype != $mainframe->_session->usertype)) {
						// Update the database
						$tmp->id = $row->id;
						$tmp->store();
						// Get new ACL details
						$grp = $acl->getAroGroup($row->id);
						$gid = 1;
						if ($acl->is_group_child_of($grp->name, 'Registered', 'ARO') || $acl->is_group_child_of($grp->name, 'Public Backend', 'ARO')) {
							// fudge Authors, Editors, Publishers and Super Administrators into the Special Group
							$gid = 2;
						}
						// Update session
						$row->usertype = $grp->name;
						$mainframe->_session->usertype = $tmp->usertype;
						$mainframe->_session->gid = intval($gid);
						$mainframe->_session->update();
						addLogEntry('LDAP Sync Mambot', 'synchronisation', 'notice', 'Updated user "'.$tmp->username.'" with new group: '. $tmp->usertype);
					} else {
						addLogEntry('LDAP Sync Mambot', 'synchronisation', 'notice', 'No change in user groups for "'.$tmp->username.'" not updating user');
					}
				} else {
						addLogEntry('LDAP Sync Mambot', 'synchronisation', 'debug', 'Demotion is disabled; User "'.$tmp->username.'" has been skipped.');
				}
			}
		}
	} //else die('No userid'); // else we don't care ;)
}
?>