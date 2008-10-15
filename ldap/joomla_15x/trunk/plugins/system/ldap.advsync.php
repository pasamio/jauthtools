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
 * @author Sam Moffatt <sam.moffatt@sammoffatt.com.au>
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Sam Moffatt 
 * @version SVN: $Id:$
 * @link http://sammoffatt.com.au/jauthtools JAuthTools Homepage
 */

/** ensure this file is being included by a parent file */
// no direct access
defined('_VALID_MOS') or die('Restricted access');

if (!function_exists('addLogEntry')) {
	/**
	 * Add a log entry to the supported logging system
	 * @package JLogger
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
	addLogEntry('LDAP Adv Sync Mambot', 'configuration', 'crit', 'PHP LDAP Library not detected');
} else
	if (!class_exists('ldapConnector')) {
		addLogEntry('LDAP Adv Sync Mambot', 'configuration', 'crit', 'Joomla! LDAP Library not detected');
	} else {
		$_MAMBOTS->registerFunction('onAfterStart', 'botDoLdapAdvSync');
	}

/**
 * Initiates a LDAP Sync
 *
 * Initiates a LDAP Synchronization. A user is checked and the system redirects if required
 */
function botDoLdapAdvSync() {
	global $my, $database, $mainframe, $acl;
	$option = mosGetParam($_REQUEST, 'option','');
	$task = mosGetParam($_REQUEST, 'task', '');
	$submit = mosGetParam($_REQUEST,'submit', '');
	
	// Configuration: Load mambot parameters
	$query = "SELECT params FROM #__mambots WHERE element = 'ldap.advsync' AND folder = 'system'";
	$database->setQuery($query);
	$params = $database->loadResult();
	$mambotParams = & new mosParameters($params);
	$ldap = null; // bad c habbit
	if ($mambotParams->get('useglobal',1)) { // If we use global set this
		$ldap = new ldapConnector();
		$mambotParams = $ldap->getParams();
		$ldap->source = 'joomla.ldap';
	} else {
		$ldap = new ldapConnector($mambotParams);
		$ldap->source = 'ldap.advsync';
	}
	
	// Valid our basic params
	$table = $mambotParams->get('externaltable');
	$uidfield = $mambotParams->get('uidfield');
	$pkeyfield = $mambotParams->get('pkeyfield');
	$syncmap = $mambotParams->get('syncmap');
	
	if(!$table || !$uidfield || !$syncmap) {
		addLogEntry('LDAP Adv Sync Mambot', 'configuration', 'error', 'Missing configuration settings');
		return 0;
	}

	// Step 1: Discover username and user ID
	$username = stripslashes(strval(mosGetParam($_REQUEST, 'username', '')));
	$usrname = stripslashes(strval(mosGetParam($_REQUEST, 'usrname', '')));
	$username = $username ? $username : $usrname;
	
	$loginonly = $mambotParams->get('syncloginonly',0);
	
	if($loginonly) {
		if 	($option != 'login' && // check for login
 			($option != 'com_comprofiler' && $task != 'login') &&
			($submit != 'Login')) { // added community builder support
				return 0; // no login detected so bail
		}
	}
	
	$cuid = 0;
	if ($mainframe->_session->userid) { // check the session
		$cuid = $mainframe->_session->userid; 		// the user is logged in so use that
		$username = $mainframe->_session->username; // pull their username out
	} else if($username) {
		// we need to find them
		$database->setQuery('SELECT id FROM #__users WHERE username = "'. $username .'"');
		$cuid = $database->loadResult();
	} // final else is covered by the next if
	
	if(!$cuid) {
		addLogEntry('LDAP Adv Sync Mambot', 'detection', 'error', 'Failed to find user to synchronise');
		return 0;
	}
	
	
	
	// Step 2: Lookup our table to validate it exists as well as core columns
	$database->setQuery('SHOW COLUMNS FROM '. $table);
	$fields = $database->loadAssocList();
	if(!count($fields)) {
		addLogEntry('LDAP Adv Sync Mambot', 'configuration', 'error', stripslashes($database->getErrorMsg()));
		return 0;
	}
	$tablefields = Array();
	foreach($fields as $field) {
		$tablefields[] = $field['Field'];
	}
	
	if(!in_array($uidfield, $tablefields)) {
		addLogEntry('LDAP Adv Sync Mambot', 'configuration', 'error', 'UID Field "'. $uidfield .'" is not in table "'. $table.'"');
		return 0;
	}
	
	
	// Step 3: Connect to LDAP to find info
	if ($ldap->connect()) {
		if ($ldap->bind()) { // Anonymous bind
			$userdetails = $ldap->simple_search(str_replace("[search]", $username, $mambotParams->get('search_string')));
			if(!count($userdetails)) {
				addLogEntry('LDAP Adv Sync Mambot', 'synchronisation', 'error', 'Could not find user "'. $username.'" in LDAP directory');
				return 0;
			}
			$userdetails = $userdetails[0]; // remove a layer since we only want one result
			//print_r($userdetails);
			// Set up some variables we'll need shortly
			// Main user
			$uid = $mainframe->_session->userid;
			$rows = explode('<br />',$syncmap);
			$dbtable = new mosDBTable($table, $pkeyfield, $database);
			$dbtable->$uidfield = $cuid;
			if($pkeyfield && $pkeyfield != $uidfield) {
				$database->setQuery('SELECT '. $pkeyfield .' FROM '. $table .' WHERE '. $uidfield .' = '. $cuid);
				$key = $database->loadResult();
				if($key) {
					$dbtable->$pkeyfield = $key;
				} else {
					$dbtable->$pkeyfield = 0;
				}
			}
			foreach($rows as $row) {
				if(!strlen(trim($row))) continue; // skip empty line
				$parts = explode(':', $row); // look at the parts
				// and clean them up
				for($i = 0; $i < count($parts); $i++) $parts[$i] = trim($parts[$i]);
				if(in_array($parts[0], $tablefields)) {
					$fieldname = $parts[0];
					$ldap = 0; // find value in ldap
					$index = 0;
					$value = '';
					$pos = strpos($parts[1],'[');
					if($pos !== FALSE) {
						if($pos) {
							$ldap = 1;
							$value = substr($parts[1],0,$pos);
							$index = intval(substr($parts[1],$pos+1, strpos($parts[1],']')-$pos-1));
						} else {
							$value = trim($parts[1], '[]');
						}
					} else {
						$ldap = 1;
						$value = $parts[1];
					}
					if($ldap) {
						if(isset($userdetails[$value])) {
							if(isset($userdetails[$value][$index])) {
								$dbtable->$fieldname = $userdetails[$value][$index];
							} else if(isset($userdetails[$value][0])) {
								$dbtable->$fieldname = $userdetails[$value][0];
							} else {
								$dbtable->$fieldname = $userdetails[$value];
							}
						} else {
							addLogEntry('LDAP Adv Sync Mambot', 'parser', 'notice', 'Ignoring field mapping for "'. $fieldname .'": cannot find field "'. $value .'" in LDAP');
						} 
					} else {
						$dbtable->$fieldname = $value;
					}
				} else {
					addLogEntry('LDAP Adv Sync Mambot', 'parser', 'notice', 'Ignoring field mapping for "'. $parts[0].'": cannot find field in table');
				}
			}
			$result = $dbtable->store();
			if($result) 	addLogEntry('LDAP Adv Sync Mambot', 'synchronisation', 'notice', 'Updated user "'.$username.'"');
			else addLogEntry('LDAP Adv Sync Mambot', 'synchronisation', 'error', 'Failed to update user "'.$username.'": '. $table->getErrorMsg());
			return 0;
		}
	}
}
?>