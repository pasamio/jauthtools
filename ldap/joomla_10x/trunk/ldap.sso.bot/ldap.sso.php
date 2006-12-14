<?php
/**
 * LDAP Single Signon Bot
 * 
 * This bot will sign a user on using information provided by a Novell eDirectory  
 * 
 * PHP4
 * MySQL 4
 *  
 * Created on Oct 3, 2006
 * 
 * @package LDAP Tools
 * @subpackage SSO
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

$_MAMBOTS->registerFunction('onAfterStart', 'botDoLdapSSOLogin');

/**
 * Initiates a LDAP login
 *
 * Initiates a LDAP login for Joomla! Uses the IP and eDirectory to verify the user.
 * Single Sign On System.
 */
function botDoLdapSSOLogin() {
	global $database, $mainframe, $acl, $_MAMBOTS, $_LANG;

	if ($mainframe->isAdmin()) {
		return false;
	} // Don't SSO backend

	// load mambot parameters
	$query = "SELECT params FROM #__mambots WHERE element = 'ldap.sso' AND folder = 'system'";
	$database->setQuery($query);
	$params = $database->loadResult();
	$mambotParams =& new mosParameters( $params );
	if ($mambotParams->get('useglobal')) {
		$ldap = new ldapConnector();
	} else {
		$ldap = new ldapConnector($mambotParams);
	}

	if (!$ldap->connect()) {
		//die('LDAP Connect failed');
		return 0;
	}

	$success = $ldap->bind();
	//	echo $success;

	$ip = mosGetParam($_SERVER, 'REMOTE_ADDR', null);
	$na = $ldap->ipToNetAddress($ip);
	$na2 = $ldap->ipToTCPNetAddress($ip);
	
	// just a test, please leave
	$search_filters = array (
		"(|(networkAddress=$na2)(networkAddress=$na))"
		//"(networkAddress=$na2)"
	);	
	
	$attributes = $ldap->search($search_filters);
	/*print_r($attributes);
	print_r($search_filters);
	die();*/
	//$ldap->close();	
	if (isset ($attributes[0]['dn'])) {
		$dnsplit = explode(',', $attributes[0]['dn']);
		$uidsplit = explode('=', $dnsplit[0]);
		$username = $uidsplit[1];

		if ($username != NULL) {
			// Has a remote_user field set, get the username and attempt to sign them in.
			$parts = split('@', $username);
			$username = $parts[0];

			//load user bot group
			$query = 'SELECT id FROM #__users WHERE username=' . $database->Quote($username);
			$database->setQuery($query);
			$result = intval($database->loadResult());
			$user = new mosUser($database);
			$user->username = $username;
			if (!$result &&  $mambotParams->get('autocreate')) {
				$ldap->populateUser($user,$mambotParams->get('groupMap'));
				$row->registerDate 	= date( 'Y-m-d H:i:s' );
				if($user->usertype == 'Registered' && !$mambotParams->get('autocreateregistered')) {
					$ldap->close();
					return false;
				} else {
					$user->store() or die('Could not autocreate user:'. print_r($user,1)  );
				}
			} else if($result) {
				$user->load(intval($result));
			} else {
				$ldap->close();
				return false;
			}
				
			// check to see if user is blocked from logging in (ignored)
			if ($user->block == 1) {
				$ldap->close();
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
			if (!$database->query()) {
				die($database->stderr(true));
			}	

			mosCache :: cleanCache();
			$ldap->close();
			return true;
		} else {
			$ldap->close();
			return false;
		}
	} else {
		$ldap->close();
		return false;
	}
}
?>
