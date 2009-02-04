<?php

/** ensure this file is being included by a parent file */
// no direct access
defined('_JEXEC') or die('Restricted access');

// Special mutable version of JTable that isn't as evil as the JObject derived on
jimport('jauthtools.mutabletable');

if(function_exists('addLogEntry')) {
	function addLogEntry($dummy1, $dummy2, $dummy3, $dummy4) { }
}

class plgUserAdvSync extends JPlugin {

	function plgUserAdvSync($subject, $params) {

	}

	function onLoginUser($user, $options) {
		//global $my, $database, $mainframe, $acl;

		$database =& JFactory::getDBO();
		$mainframe =& JFactory::getApplication();

		// Configuration: Load mambot parameters
		$ldap = null; // bad c habbit
		/*
		if ($this->params->get('useglobal',1)) { // If we use global set this
		$ldap = new ldapConnector();
		$this->params = $ldap->getParams();
		$ldap->source = 'joomla.ldap';
		} else {
		$ldap = new ldapConnector($this->params);
		$ldap->source = 'ldap.advsync';
		}*/

		// Valid our basic params
		$table = $this->params->get('externaltable');
		$uidfield = $this->params->get('uidfield');
		$pkeyfield = $this->params->get('pkeyfield');
		$syncmap = $this->params->get('syncmap');

		if(!$table || !$uidfield || !$syncmap) {
			addLogEntry('LDAP Adv Sync Mambot', 'configuration', 'error', 'Missing configuration settings');
			return 0;
		}

		// Step 1: Discover username and user ID
		$username = stripslashes(strval(mosGetParam($_REQUEST, 'username', '')));
		$usrname = stripslashes(strval(mosGetParam($_REQUEST, 'usrname', '')));
		$username = $username ? $username : $usrname;

		$loginonly = $this->params->get('syncloginonly',0);

		$tmpUser =& JUser::getInstance();
		$cuid = $tmpUser->get('id');
		/*
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
		 */



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
				$userdetails = $ldap->simple_search(str_replace("[search]", $username, $this->params->get('search_string')));
				if(!count($userdetails)) {
					addLogEntry('LDAP Adv Sync Mambot', 'synchronisation', 'error', 'Could not find user "'. $username.'" in LDAP directory');
					return 0;
				}
				$userdetails = $userdetails[0]; // remove a layer since we only want one result
				//print_r($userdetails);
				// Set up some variables we'll need shortly
				// Main user
				//$uid = $mainframe->_session->userid;
				$uid = $tmpUser->get('id');
				$rows = explode('<br />',$syncmap);
				$dbtable = new MutableJTable($table, $pkeyfield, $database);
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
}