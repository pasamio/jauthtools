<?php

/**
 * LDAP Library
 * 
 * This bot is the basis of the LDAP Library 
 * 
 * PHP4
 * MySQL 4
 *  
 * Created on Oct 3, 2006
 * 
 * @package LDAP Tools
 * @subpackage Library
 * @author Sam Moffatt <S.Moffatt@toowoomba.qld.gov.au>
 * @author Toowoomba City Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2006 Toowoomba City Council/Developer Name 
 * @version SVN: $Id:$
 * @see Joomla!Forge Project: http://forge.joomla.org/sf/sfmain/do/viewProject/projects.ldap_tools
 */

class ldapConnector {
	/** @var string */
	var $host = null;
	/** @var int */
	var $port = null;
	/** @var string */
	var $base_dn = null;
	/** @var string */
	var $users_dn = null;
	/** @var string */
	var $search_string = null;
	/** @var boolean */
	var $use_ldapV3 = null;
	/** @var boolean */
	var $no_referrals = null;
	/** @var boolean */
	var $negotiate_tls = null;

	/** @var string */
	var $username = null;
	/** @var string */
	var $password = null;

	/** @var mixed */
	var $_resource = null;
	/** @var string */
	var $_dn = null;

	/**
	 * Constructor
	 * @param object An object of configuration variables
	 */
	function ldapConnector($configObj = null) {
		if (is_object($configObj)) {
			$vars = get_class_vars(get_class($this));
			foreach (array_keys($vars) as $var) {
				if (substr($var, 0, 1) != '_') {
					if ($param = $configObj->get($var)) {
						$this-> $var = $param;
					}
				}
			}
		} else {
			// Not an object, attempt auto configuration
			global $database;
			$query = "SELECT params FROM #__mambots WHERE element = 'joomla.ldap' AND folder = 'system'";
			$database->setQuery($query);
			$params = $database->loadResult();
			$mambotParams = & new mosParameters($params);
			$vars = get_class_vars(get_class($this));
			foreach (array_keys($vars) as $var) {
				if (substr($var, 0, 1) != '_') {
					if ($param = $mambotParams->get($var)) {
						$this-> $var = $param;
					}
				}
			}
		}
	}

	/**
	 * @return boolean True if successful
	 */
	function connect() {
		if ($this->host == '') {
			return false;
		}
		$this->_resource = @ ldap_connect($this->host, $this->port);
		if ($this->_resource) {
			if ($this->use_ldapV3) {
				if (!ldap_set_option($this->_resource, LDAP_OPT_PROTOCOL_VERSION, 3)) {
					return false;
					echo "<script> alert(\" failed to set LDAP protocol V3\"); </script>\n";
				}
			}
			if (!ldap_set_option($this->_resource, LDAP_OPT_REFERRALS, intval($this->no_referrals))) {
				return false;
				echo "<script> alert(\" failed to set LDAP_OPT_REFERRALS option\"); </script>\n";
			}
			if ($this->negotiate_tls) {
				if (!ldap_start_tls($this->_resource)) {
					return false;
					echo "<script> alert(\" ldap_start_tls failed\"); </script>\n";
				}
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Close the connection
	 */
	function close() {
		@ ldap_close($this->_resource);
	}

	/**
	 * Sets the DN with some template replacements
	 * @param string The username
	 */
	function setDN($username) {
		if ($this->users_dn == '') {
			$this->_dn = $username;
		} else {
			$this->_dn = str_replace('[username]', $username, $this->users_dn);
		}
	}

	/**
	 * @return string The current dn
	 */
	function getDN() {
		return $this->_dn;
	}

	/**
	 * Binds to the LDAP directory
	 * @param string The username
	 * @param string The password
	 */
	function bind($username = null, $password = null) {
		if (is_null($username)) {
			$username = $this->username;
		}
		if (is_null($password)) {
			$username = $this->password;
		}
		$this->setDN($username);
		$bindResult = @ ldap_bind($this->_resource, $this->getDN(), $password);

		return $bindResult;
	}

	/**
	 * Perform an LDAP search
	 */
	function search($filters, $dnoverride = null) {
		$attributes = array ();
		if ($dnoverride) {
			$dn = $dnoverride;
		} else {
			$dn = $this->base_dn;
		}
		//echo '<p>DN looks like '. $dn . '</p>';
		$resource = $this->_resource;

		foreach ($filters as $search_filter) {
			$search_result = ldap_search($resource, $dn, $search_filter);
			if (($count = ldap_count_entries($resource, $search_result)) > 0) {
				for ($i = 0; $i < $count; $i++) {
					$attributes[$i] = Array ();
					if (!$i) {
						$firstentry = ldap_first_entry($resource, $search_result);
					} else {
						$firstentry = ldap_next_entry($resource, $firstentry);
					}
					$attributes_array = ldap_get_attributes($resource, $firstentry); // load user-specified attributes
					// ldap returns an array of arrays, fit this into attributes result array
					foreach ($attributes_array as $ki => $ai) {
						if (is_array($ai)) {
							$subcount = $ai['count'];
							$attributes[$i][$ki] = Array ();
							for ($k = 0; $k < $subcount; $k++) {
								$attributes[$i][$ki][$k] = $ai[$k];
							}
						} /*else {
													//$attributes[$i][$ki]=$ai;
												}*/

					}
					//					if ($this->users_dn == '') {
					$attributes[$i]['dn'] = ldap_get_dn($resource, $firstentry);
					//					} else {
					//						$attributes[$i]['dn'] = $dn;
					//					}
				} //*/
			}
		}
		return $attributes;
	}

	/**
	 * Converts a dot notation IP address to net address
	 * @param string
	 * @return string
	 */
	function ipToNetAddress($ip) {
		$parts = explode('.', $ip);
		$address = '1#';

		foreach ($parts as $int) {
			$tmp = dechex($int);
			if (strlen($tmp) != 2) {
				$tmp = '0' . $tmp;
			}
			$address .= '\\' . $tmp;
		}
		return $address;
	}
	
	/**
	 * Converts a dot notation IP address to a TCP net address
	 */
	function ipToTCPNetAddress($ip) {
		$parts = explode('.', $ip);
		$address = '9#';
		$address .= '\\0'. dechex('4');
		$address .= '\\'. dechex('99');
		
		foreach ($parts as $int) {
			$tmp = dechex($int);
			if (strlen($tmp) != 2) {
				$tmp = '0' . $tmp;
			}
			$address .= '\\' . $tmp;
		}
		return $address;
	}

	/**
	 * extract readable network address from the LDAP encoded networkAddress attribute.
	 * @author Jay Burrell, Systems & Networks, Mississippi State University
	 *  Novell Docs, see: http://developer.novell.com/ndk/doc/ndslib/schm_enu/data/sdk5624.html#sdk5624
	 *  for Address types: http://developer.novell.com/ndk/doc/ndslib/index.html?page=/ndk/doc/ndslib/schm_enu/data/sdk4170.html
	 *  LDAP Format, String: 
	 *     taggedData = uint32String "#" octetstring
	 *     byte 0 = uint32String = Address Type: 0= IPX Address; 1 = IP Address
	 *     byte 1 = char = "#" - separator
	 *     byte 2+ = octetstring - the ordinal value of the address
	 *   Note: with eDirectory 8.6.2, the IP address (type 1) returns
	 * 		correctly, however, an IPX address does not seem to.  eDir 8.7 may
	 *		correct this.
	 */
	function LDAPNetAddr($networkaddress) {

		$addr = "";
		$addrtype = intval(substr($networkaddress, 0, 1));
		$networkaddress = substr($networkaddress, 2); // throw away bytes 0 and 1 which should be the addrtype and the "#" separator
		$addrtypes = array (
			'IPX',
			'IP',
			'SDLC',
			'Token Ring',
			'OSI',
			'AppleTalk',
			'NetBEUI',
			'Socket',
			'UDP',
			'TCP',
			'UDP6',
			'TCP6',
			'Reserved (12)',
			'URL',
			'Count'
		);
		$len = strlen($networkaddress);
		if ($len > 0) {
			for ($i = 0; $i < $len; $i += 1) {
				$byte = substr($networkaddress, $i, 1);
				$addr .= ord($byte);
				//echo '<pre>Byte: '.$byte.'</pre><br>';
				//echo '<pre>Ord: '. ord($byte). '</pre><br>';
				if ($addrtype == 1) { // dot separate IP addresses...
					$addr .= ".";
				}
			}
			if ($addrtype == 1) { // strip last period from end of $addr
				$addr = substr($addr, 0, strlen($addr) - 1);
			}
		} else {
			$addr .= "address not available.";
		}
		return Array('protocol'=>$addrtypes[$addrtype], 'address'=>$addr);
	}

	/**
	 * Populates a mosUser class with name and email from LDAP
	 * @param mosUser user object with username set to pull from LDAP
	 * @param string map INI string mapping LDAP group to Joomla Group ID and Type
	 */
	function populateUser(& $user, $map = null, $ad = false) {
		// Grab user details
		if(!$ad) {
			$search_filters = array (
				'(cn=' . $user->username . ')'
			);
		} else {
			// Active Directory
			$search_filters = array (
				'(SAMAccountName=' . $user->username . ')'
			);
		}
		$currentgrouppriority = 0;
		$user->id = 0;
		$attributes = $this->search($search_filters);
		$user->gid = 18;
		$user->usertype = 'Registered';
		$user->email = $user->username; // Set Defaults
		$user->name = $user->username; // Set Defaults
		if (isset ($attributes[0]['dn'])) {
			//$user->id = 1;
			$user->email = $attributes[0]['mail'][0];
			if(!$ad) {
				$user->name = $attributes[0]['fullName'][0];
			} else {
				$user->name = $attributes[0]['displayName'][0];
			}
			$user->block = intval($attributes[0]['loginDisabled'][0]);
			if ($map) {
				// Process Map
				$groups = explode("<br />", $map);
				$groupMap = Array ();
				foreach ($groups as $group) {
					if (trim($group)) {
						$details = explode('|', $group);
						$groupMap[] = Array (
							'groupname' => trim(str_replace("\n",
							'',
							$details[0]
						)), 'gid' => $details[1], 'usertype' => $details[2], 'priority' => $details[3]);
					}
				}
				if(isset($attributes[0]['groupMembership'])) {
					foreach ($attributes[0]['groupMembership'] as $group) {
						// Hi there :)
						foreach ($groupMap as $mappedgroup) {
							if (strtolower($mappedgroup['groupname']) == strtolower($group)) { // darn case sensitivty
								if ($mappedgroup['priority'] > $currentgrouppriority) {
									$user->gid = $mappedgroup['gid'];
									$user->usertype = $mappedgroup['usertype'];
									$currentgrouppriority = $mappedgroup['priority'];
								}
							}
						}
					}
				}
			}
		}
	}
}