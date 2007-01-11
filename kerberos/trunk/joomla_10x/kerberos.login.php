<?php
/**
* @version $Id: kerberos.login.php,v 1.1 2005/08/17 13:37:38 pasamio Exp $
* @package Mambo
* @copyright (C) Samuel Moffatt/Toowoomba City Council
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* Mambo is Free Software
*/

/** ensure this file is being included by a parent file */
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

$_MAMBOTS->registerFunction( 'onMainframe', 'botDoKerberosLogin' );

/**
 * Initiates a Kerberos login
 *
 * Initiates a Kerberos login for Mambo. Work has been done by Apache already.
 */
function botDoKerberosLogin() {
	global $database, $mainframe, $acl, $_MAMBOTS, $_LANG;;
	$username = mosGetParam($_SERVER,"REMOTE_USER",null);
	if($username != NULL) {
		//echo($username);
		// Has a remote_user field set, get the username and attempt to sign them in.
		$parts = split('@',$username);
		$username = $parts[0];

	        //load user bot group
                $query = 'SELECT id FROM #__users WHERE username=' . $database->Quote( $username );
                $database->setQuery( $query );
		$result = $database->loadResult();
                if ($result > 0) {
                        $user = new mosUser( $database );
                        $user->load( intval( $result ) );
			
                        // check to see if user is blocked from logging in (ignored)
                        if ($user->block == 1) {
				return false;
                        }
                        // fudge the group stuff
                        $grp            = $acl->getAroGroup( $user->id );
                        $row->gid       = 1;
                                                                                                 
                        if ( $acl->is_group_child_of( $grp->name, 'Registered', 'ARO' ) || $acl->is_group_child_of( $grp->name, 'Public Backend', 'ARO' )) {
                        	// fudge Authors, Editors, Publishers and Super Administrators into the Special Group
	                        $user->gid = 2;
                        }
                        $user->usertype = $grp->name;
                                                                                                    
                        // access control check
                        $client = $this->_isAdmin ? 'administrator' : 'site';
                        if ( !$acl->acl_check( 'login', $client, 'users', $user->usertype ) ) {
                               return false;
                        }
//                       	echo '<pre>Mainframe: ';
//			print_r($mainframe); die();                                                                            
                        $session =& $mainframe->_session;
                        $session->guest         = 0;
                        $session->username      = $user->username;
                        $session->userid        = intval( $user->id );
                        $session->usertype      = $user->usertype;
                        $session->gid           = intval( $user->gid );
                                                                                                  
                        $session->store();
                                                                                                    
                        $user->setLastVisit();
                                                                                                  
                        $remember = trim( mosGetParam( $_POST, 'remember', '' ) );
                        if ($remember == 'yes') {
                        	$session->remember( $user->username, $user->password );
			}
                }
                                                                                                    
	        //mosCache::cleanCache('com_content');
        	mosCache::cleanCache();
	        return true;
	} else {
		return false;
	}
}
?>
