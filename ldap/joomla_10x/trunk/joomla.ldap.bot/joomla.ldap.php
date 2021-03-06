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
 * @author Sam Moffatt <sam.moffatt@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Sam Moffatt
 * @version SVN: $Id:$
 * @see Joomla!Forge Project: http://forge.joomla.org/sf/sfmain/do/viewProject/projects.ldap_tools
 */

if (!function_exists('ldap_connect')) {
	die('LDAP Not Enabled - Please install LDAP in your PHP instance to continue.');
}

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
			$logentry->store() or die('Log entry save failed: ' . $message);
		}
	}
}

/**
 * LDAP Connector Class
 * @package LDAP Tools
 * @subpackage Connector
 */
class ldapConnector {
	/** @var string Hostname of LDAP server
	    @access public */
	var $host = null;
	/** @var string Alternate Hostname of an LDAP server (for redundancy)
	 * @access public */
	var $alternatehost = null;
	/** @var bool Authorization Method to use
	    @access public */
	var $auth_method = null;
	/** @var int Port of LDAP server
	    @access public */
	var $port = null;
	/** @var int Alternate Port of LDAP server
	    @access public */
	var $alternateport = null;
	/** @var string Base DN (e.g. o=MyDir)
	    @access public */
	var $base_dn = null;
	/** @var string User DN (e.g. cn=Users,o=MyDir)
	    @access public */
	var $users_dn = null;
	/** @var string Search String
	    @access public */
	var $search_string = null;
	/** @var boolean Use LDAP Version 3
	    @access public */
	var $use_ldapV3 = null;
	/** @var boolean No referrals (server transfers)
	    @access public */
	var $no_referrals = null;
	/** @var boolean Negotiate TLS (encrypted communications)
	    @access public */
	var $negotiate_tls = null;

	/** @var string Username to connect to server
	    @access public */
	var $username = null;
	/** @var string Password to connect to server
	    @access public */
	var $password = null;

	/** @var mixed LDAP Resource Identifier
	    @access private */
	var $_resource = null;
	/** @var string Current DN
	    @access private */
	var $_dn = null;

	/** @var string LDAP Map Full Name
	    @access private */
	var $ldap_fullname = '';
	/** @var string LDAP Map Email
	    @access private */
	var $ldap_email = '';
	/** @var string LDAP Map User ID
	    @access private */
	var $ldap_uid = '';
	/** @var string LDAP Map Password
	    @access private */
	var $ldap_password = '';
	/** @var string LDAP Map Blocked
	 * @access private */
	var $ldap_blocked = 'loginDisabled';
	/** @var string LDAP Map Group Name
		@access private */
	var $ldap_groupname = '';
	/**@var string LDAP Map Group Member	
	 * @access private */
	var $ldap_groupmember = '';
	/** @var string Autocreate default group
		@access private */
	var $defaultgroup = 'registered';
	/** @var string Bind Method */
	var $bind_method = '';
	/** @var int Bind Result */
	var $bind_result = 0;
	
	/** @var bool use_iconv Use iconv */
	var $use_iconv = 0;
	/** @var string iconv_from Original encoding */
	var $iconv_from = 'ISO8559-1';
	/** @var string iconv_to New encoding */
	var $iconv_to = 'UTF-8';
	
	/** @var string Group Map */
	var $groupMap = null;
	
	/** @var object source Source of the configuration mambot (the name in #__mambots) */
	var $source = null;	
	
	/** @var object params Param Object */
	var $params = null;
	
	/**
	 * Constructor
	 * @param object An object of configuration variables
	 * @access public
	 */
	function ldapConnector($configObj = null) {
		$this->search_string = 'cn=[search]'; // Default Search String
		if (is_object($configObj)) {
			$vars = get_class_vars(get_class($this));
			foreach (array_keys($vars) as $var) {
				if (substr($var, 0, 1) != '_') {
					if ($param = $configObj->get($var)) {
						$this-> $var = $param;
					}
				}
			}
			$this->params = $configObj;
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
			$this->params = $mambotParams;
		}
	}
	
	function convert($string) {
		if(function_exists('iconv') && $this->use_iconv) {
			return iconv($this->iconv_to, $this->iconv_from, $string);
		} else return $string;
	}

	/**
	 * Connect to server
	 * @return boolean True if successful
	 * @access public
	 */
	function connect($usealt=false,$applyfailover=true) {
		if ($this->host == '') {
			return false;
		}
		if(!$usealt) {
			$this->_resource = @ ldap_connect($this->host, $this->port);
		} else {
			$this->_resource = @ ldap_connect($this->alternatehost, $this->alternateport);
		}
		if ($this->_resource) {
			if ($this->use_ldapV3) {
				if (!ldap_set_option($this->_resource, LDAP_OPT_PROTOCOL_VERSION, 3)) {
					return false;
				}
			}
			if (!ldap_set_option($this->_resource, LDAP_OPT_REFERRALS, intval($this->no_referrals))) {
				return false;
			}
			if ($this->negotiate_tls) {
				if (!ldap_start_tls($this->_resource)) {
					return false;
				}
			}
			return true;
		} else {
			if($this->alternatehost && !$usealt) {
				if($this->connect($usealt)) {
					// we failed to connect to the first server
					// but the second did, enact failover
					if($applyfailover) $this->_enactFailover($this->alternatehost, $this->host);
					return true;
				} else {
					return false;
				}
			} else return false;
		}
	}

	/**
	 * Close the connection
	 * @access public
	 */
	function close() {
		@ ldap_close($this->_resource);
	}

	/**
	 * Sets the DN with some template replacements
	 * @param string The username
	 * @access public
	 */
	function setDN($username, $nosub = 0) {
		if ($this->users_dn == '' || $nosub) {
			$this->_dn = $username;
		} else {
			$this->_dn = str_replace('[username]', $username, $this->users_dn);
		}
	}

	/**
	 * @return string The current dn
	 * @access public
	 */
	function getDN() {
		return $this->_dn;
	}

	/**
	 * Anonymously Binds to LDAP Directory
	 */
	function anonymous_bind() {
		$this->bind_result = @ ldap_bind($this->_resource);
		if(!$this->bind_result && ldap_errno($this->_resource) == -1) {
			// we failed to connect to the server, attempt failover
			if($this->connect(true,false)) {
				// rebind with the new server if this fails then it doesn't matter'
				$this->bind_result = @ ldap_bind($this->_resource);
				if($this->bind_result) {
					// we bound successfully
					$this->_enactFailover($this->alternatehost, $this->host);
				}
			}
		}
		// return whatever the final result was
		return $this->bind_result;
	}

	/**
	 * Binds to the LDAP directory
	 * @param string The username
	 * @param string The password
	 * @return boolean Result
	 * @access public
	 */
	function bind($username = null, $password = null, $nosub = 0) {
		switch ($this->bind_method) {
			case 'anonymous' :
				$bindResult = $this->anonymous_bind(); // use the anonymous bind
				break;
			default :
				if (is_null($username)) {
					$username = $this->username;
				}
				if (is_null($password)) {
					$password = $this->password;
				}

				$this->setDN($username, $nosub);
				$this->bind_result =  @ldap_bind($this->_resource, $this->getDN(), $password);
				if(!$this->bind_result && ldap_errno($this->_resource) == -1) {
					// we failed to connect to the server, attempt failover
					if($this->connect(true,false)) {
						// rebind with the new server if this fails then it doesn't matter'
						$this->bind_result = @ ldap_bind($this->_resource, $this->getDN(), $password);
						if($this->bind_result) {
							// we bound successfully, this server must be good
							$this->_enactFailover($this->alternatehost, $this->host);
						}
					}
				}
		}
		return $this->bind_result;
	}

	/**
	 * Perform an LDAP search using semicolon seperated search strings
	 * @param string search string of search values
	 */
	function simple_search($search,$dnlist=null) {
		$results = explode(';', $search);
		foreach ($results as $key => $result) {
			$results[$key] = '(' . $result . ')';
		}
		return $this->search($results,$dnlist);
	}

	/**
	 * Hidden function to actually do the work
	 */
	function _search($filter, $dn, & $attributes) {
		$resource = $this->_resource;
		addLogEntry('Joomla! LDAP Library Mambot', 'search', 'debug', 'Searching for '. $filter .' in DN '. $dn);
		$search_result = ldap_search($resource, $dn, $filter);
		addLogEntry('Joomla! LDAP Library Mambot', 'search', 'debug', 'Got search result of '. print_r($search_result,1));
		addLogEntry('LDAP Library', 'search', 'debug', 'Search for '. $filter . ' in '. $dn . ' returned ' . $search_result . ' with '. ldap_count_entries($resource, $search_result) .' results');
		if ($search_result && ($count = ldap_count_entries($resource, $search_result)) > 0) {
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
					}
				}
				$attributes[$i]['dn'] = ldap_get_dn($resource, $firstentry);
			}
		}
	}
	
	/**
	 * Perform an LDAP search
	 * @param array Search Filters (array of strings)
	 * @param string DN Override
	 * @return array Multidimensional array of results
	 * @access public
	 */
	function search($filters, $dnoverride = null) {
		$result = array ();
		if ($dnoverride) {
			$dn = $dnoverride;
		} else {
			$dn = $this->base_dn;
		}

		foreach ($filters as $search_filter) {
			$dn_list = explode(';', $dn);
			foreach ($dn_list as $dn) {
				$this->_search($search_filter, $dn, $result);				
			}
		}
		return $result;
	}

	/**
	 * Compare an entry and return a true or false result
	 * @param string dn The DN which contains the attribute you want to compare
	 * @param string attribute The attribute whose value you want to compare
	 * @param string value The value you want to check against the LDAP attribute
	 * @return mixed result of comparison (true, false, -1 on error)
	 */
	function compare($dn, $attribute, $value) {
		return ldap_compare($this->_resource, $dn, $attribute, $value);
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
	  * extract readable network address from the LDAP encoded networkAddress attribute.
	  * @author Jay Burrell, Systems & Networks, Mississippi State University
	  * Please keep this document block and author attribution in place.
	  *  Novell Docs, see: http://developer.novell.com/ndk/doc/ndslib/schm_enu/data/sdk5624.html#sdk5624
	  *  for Address types: http://developer.novell.com/ndk/doc/ndslib/index.html?page=/ndk/doc/ndslib/schm_enu/data/sdk4170.html
	  *  LDAP Format, String:
	  *     taggedData = uint32String "#" octetstring
	  *     byte 0 = uint32String = Address Type: 0= IPX Address; 1 = IP Address
	  *     byte 1 = char = "#" - separator
	  *     byte 2+ = octetstring - the ordinal value of the address
	  *   Note: with eDirectory 8.6.2, the IP address (type 1) returns
	  *                 correctly, however, an IPX address does not seem to.  eDir 8.7 may
	  *                correct this.
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
				if ($addrtype == 1 || $addrtype == 9) { // dot separate IP addresses...
					$addr .= ".";
				}
			}
			switch ($addrtype) {
				case 1 : // strip last period from end of $addr for ip
					$addr = substr($addr, 0, strlen($addr) - 1);
					break;
				case 9 : // splice out first two array elements and rejoin for tcp
					$addr = substr($addr, 0, strlen($addr) - 1);
					$addr = implode('.', array_slice(explode('.', $addr), 2));
					break;
			}

		} else {
			$addr .= "address not available.";
		}
		return Array (
			'protocol' => $addrtypes[$addrtype],
			'address' => $addr
		);
	}

	/**
	 * Populates a mosUser class with name and email from LDAP
	 * @param mosUser user object with username set to pull from LDAP
	 * @param string map INI string mapping LDAP group to Joomla Group ID and Type
	 * @param string dn DN String to use to search
	 * @param bool ad Is this an AD search
	 */
	function populateUser(& $user, $map = null, $dn=null, $ad = false) {
		// Check that the map has a length otherwise use the default one
		if(!strlen($map)) {
			$map = $this->groupMap;
		}
		// Grab user details
		$currentgrouppriority = 0;
		$user->id = 0;
		$userdetails = $this->simple_search(str_replace("[search]", $user->username, $this->search_string),$dn);
		addLogEntry('LDAP Library', 'User Autocreation', 'debug', 'Populating user with '. print_r($userdetails,1));
		$user->gid = 29;
		$user->usertype = 'Public Frontend';

		if(strlen($this->defaultgroup)) {
			switch($this->defaultgroup) {
				case 'registered':
					$user->gid = 18;
					$user->usertype = 'Registered';
					break;
				case 'author':
					$user->gid = 19;
					$user->usertype = 'Author';
					break;
				case 'editor':
					$user->gid = 20;
					$user->usertype = 'Editor';
					break;
			}
		} // Note: default case taken care of already!
		$user->email = $user->username; // Set Defaults
		$user->name = $user->username; // Set Defaults		
		$ldap_email = $this->ldap_email ? $this->ldap_email : 'mail';
		$ldap_fullname = $this->ldap_fullname ? $this->ldap_fullname : 'fullName';
		$groupMembership = $this->ldap_groupname ? $this->ldap_groupname : 'groupMembership';
		$ldap_block = $this->ldap_blocked ? $this->ldap_blocked : 'loginDisabled';

		if (isset ($userdetails[0]['dn']) && isset ($userdetails[0][$ldap_email][0])) {
			$user->email = $this->convert($userdetails[0][$ldap_email][0]);
			if (isset ($userdetails[0][$ldap_fullname][0])) {
				$user->name = $this->convert($userdetails[0][$ldap_fullname][0]);
			} else {
				$user->name = $this->convert($user->username);
			}

			$user->block = intval($userdetails[0][$ldap_block][0]);
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
						)), 'gid' => $details[1], 'usertype' => $details[2], 'priority' => intval($details[3]));
					}
				}

				// add group memberships for active directory
				// Also if no groups found maybe group membership attribute
				// is not auto.  Lets look in all groups given to see if user
				// is in these group.  Use the group attribute as the name of the
				// group member attribute.
				if($ad || !isset($userdetails[0][$groupMembership])) {
					$groupMemberships = Array();
					$cnt = 0;
				    if(!$ad) {
                        // since we are bound as the user, we have to bind as
                        // admin in order to search the groups and their attributes
                        $ldap_bind_uid = $this->params->get('username');
                        $ldap_bind_password = $this->params->get('password');
                        $this->bind($ldap_bind_uid , $ldap_bind_password , 1);
                     }			
                     		
					foreach ($groupMap as $groupMapEntry) {
						$group = $groupMapEntry['groupname'];
						$groupdetails = $this->simple_search($group, $dn);
						$groupMembers = isset($groupdetails[0][$this->ldap_groupmember]) ? $groupdetails[0][$this->ldap_groupmember] : Array();
						
						foreach ($groupMembers as $groupMember) {
							if($groupMember == $userdetails[0]['dn']) {
								$groupMemberships[$cnt++] = $group;
							}
						}
					}
					if($cnt > 0) {
						$userdetails[0][$groupMembership] = $groupMemberships;
					}
				}
				
				if (isset ($userdetails[0][$groupMembership])) {
					foreach ($userdetails[0][$groupMembership] as $group) {
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
		} else {
			//die('Bailing?'.$userdetails[0]['dn']);
		}
	}
	
	/**
	 * Return the params used by this instance
	 * @return object param object
	 */
	function getParams() {
		return $this->params;
	}
	
	/**
	 * Handle failover
	 * @param string new primary server
	 * @param string new alternate server
	 */
	function _enactFailover($primary, $secondary) {
		global $database;
		if(is_object($database) && $this->source) {
			$database->setQuery('SELECT params FROM #__mambots WHERE folder = "system" AND element = "'. $this->source .'"');
			$params = $database->loadResult();
			if($params) {
				$regexs = Array('/^host=[0-9.A-Za-z ]*/','/^alternatehost=[0-9.A-Za-z ]*/');
				$replacement = Array('host='.$primary,'alternatehost='.$secondary);
				$params = implode("\n",preg_replace($regexs, $replacement, explode("\n",$params)));
				$database->setQuery('UPDATE #__mambots SET params = "'. $database->getEscaped($params).'"');
				if($database->Query()) {
					addLogEntry('LDAP Library', 'Failover', 'notice', 'Enacting failover to new primary: '. $primary .'; Old server: '. $secondary);
				} else {
					addLogEntry('LDAP Library', 'Failover', 'error', 'Failed to apply failover: '. $database->getErrorMsg());
				}
			} else {
				addLogEntry('LDAP Library', 'Failover', 'error', 'Failed to find source configuration: '. $this->source);
			}
		} else {
			echo "Failing over to $primary from $secondary not supported without database\n";
		}
	}
}
