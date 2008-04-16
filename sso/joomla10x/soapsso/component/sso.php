<?php
/**
 * Joomla! SOAP SSO Project
 * @author 	Sam Moffatt <pasamio@gmail.com>
 * @author	Tomo Cerovsek <Tomo.Cerovsek.fgg.uni-lj.si> 
 * @author	Damjan Murn <Damjan.Murn.uni-lj.si>
 * @package SSO
 */
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

require_once( $mainframe->getPath( 'front_html' ) );
require_once("$mosConfig_absolute_path/administrator/components/$option/classes/ssoProvider.class.php");
require_once( "$mosConfig_absolute_path/administrator/components/$option/classes/ssoUtils.class.php" );
require_once( "$mosConfig_absolute_path/administrator/components/$option/classes/j10_sso.php" );
require_once( "$mosConfig_absolute_path/administrator/components/$option/classes/j15_sso.php" );

if (isset($_SERVER['HTTP_SOAPACTION'])) {
	handleSOAPRequest();
}

// include language file
if (is_file("$mosConfig_absolute_path/components/$option/language/$mosConfig_lang.php")) {
	include_once ("$mosConfig_absolute_path/components/$option/language/$mosConfig_lang.php");
} else {
	include_once ("$mosConfig_absolute_path/components/$option/language/english.php");
}

switch ($task) {
	case 'idplogin':
		idplogin();
		break;
	case 'login':
		loginUserUsingPost();
		idplogin();
		break;
	case 'splogin':
		splogin();
		break;
	case 'logout':
		logout();
		break;
	case 'wsdl':
		wsdl();
		break;
	case 'checkOnline':
		checkOnline();
		break;
	default:
		showProvidersList();
		break;
}

function idplogin() {
	global $database, $mosConfig_live_site, $option, $_VERSION, $mosConfig_sitename, $my;

	$spId = mosGetParam( $_REQUEST, 'sp', '' );
	if ( ! $spId) {
		echo _SSO_ERROR . ' ' . _SSO_FAILURE_REQUEST;
		return;
	}

	// check if the user is already logged in. If he is not show the login page.
	// get session variable
	global $mainframe;
	$session =& $mainframe->_session;

	if ( ! $my->id) {
		showLoginPage();
		return;
	}

	// check if local provider ($mosConfig_live_site) really is user's identity provider
    $query = "SELECT ssoIdentityProvider AS identityProvider " .
			"FROM #__sso_users " .
			"WHERE `id` = '".$my->id."'";
	$database->setQuery($query);
	$userIdP = $database->loadResult(); // user's identity provider
	if ($userIdP && $userIdP != $mosConfig_live_site) {
		// redirect user to his identity provider
        mosRedirect("$userIdP/index.php?option=$option&task=idplogin&sp=" . urlencode($spId));
	}
	
	// from here user is logged in and local provider ($mosConfig_live_site) is his identity provider

	// check if the service provider where the user wants to go is local provider
    if ($spId == $mosConfig_live_site) {
		mosRedirect("index.php");
	}
	
	// check if the Service Provider is registered at this provider
	$sp = new ssoProvider($database);

	if ( ! $sp->load($spId) || $sp->status != 'REGISTERED' || ! $sp->published) {
		echo _SSO_ERROR . ' ';
		printf(_SSO_FAILURE_SP_NOT_REGISTERED, $spId, $mosConfig_sitename);
		return;
	}

	// delete expired handles
	$query = "DELETE FROM `#__sso_handles` WHERE `time` < DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
	$database->setQuery( $query );
	$database->query();
	
	// create handle for the user
	$handle = makePassword(128);
	$userIP = $_SERVER['REMOTE_ADDR'];
	$query = "INSERT INTO `#__sso_handles` " .
	         "(`handle`, `spId`, `username`, `userIP`) " .
	         "VALUES ('$handle', '$spId', '". $my->username."', '$userIP')";
	$database->setQuery( $query );
	if (! $database->query()) {
		echo _SSO_ERROR . ' ' . _SSO_DATABASE_ERROR;
		return;
	}

	// redirect user back to the service provider
	while (@ob_end_clean());
	ob_start();
	ssoHTML::redirect($sp, $handle);
	exit;
}


function showLoginPage() {
	global $database, $mosConfig_live_site, $mosConfig_absolute_path, $mainframe;

	$menu = new mosMenu( $database );
	$params =& new mosParameters( $menu->params );

	$params->def( 'page_title', 1 );
	$params->def( 'header_login', _SSO_LOGIN );
	$params->def( 'pageclass_sfx', '' );
	$params->def( 'back_button', $mainframe->getCfg( 'back_button' ) );
	$params->def( 'login', $mosConfig_live_site );
	$params->def( 'login_message', 0 );
	$params->def( 'description_login', 1 );
	$params->def( 'description_login_text', _SSO_LOGIN_DESCRIPTION );
	$params->def( 'image_login', 'key.jpg' );
	$params->def( 'image_login_align', 'right' );
	$params->def( 'registration', $mainframe->getCfg( 'allowUserRegistration' ) );

	$image_login = '';
	if ( $params->get( 'image_login' ) <> -1 ) {
		$image = $mosConfig_live_site .'/images/stories/'. $params->get( 'image_login' );
		$image_login = '<img src="'. $image  .'" align="'. $params->get( 'image_login_align' ) .'" hspace="10" alt="" />';
	}
	
	ssoHTML::showLoginPage( $params, $image_login );
}

function makePassword($n) {
	$salt = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	$len = strlen($salt);
	$password="";
	mt_srand(10000000*(double)microtime());
	for ($i = 0; $i < $n; $i++) {
		$password .= $salt[mt_rand(0,$len - 1)];
	}
	return $password;
}

function splogin() {
	global $database, $mosConfig_live_site, $mosConfig_sitename, $option, $_VERSION, $mainframe, $my;

	$idpId = mosGetParam( $_REQUEST, 'idpId', '' );
	$handle = mosGetParam( $_REQUEST, 'handle', '' );
	if ( ! $idpId || ! $handle) {
		echo _SSO_ERROR . ' ' . _SSO_FAILURE_REQUEST;
		return;
	}

	// check if the user is already logged in.
	// get session variable
	$session =& $mainframe->_session;

	if ( $my->id) {
		$mainframe->logout();
	}

	$idp = new ssoProvider($database);
	if ( ! $idp->load($idpId) || $idp->status != 'REGISTERED' || ! $idp->published) {
		echo _SSO_ERROR . ' ';
		printf(_SSO_FAILURE_IDP_NOT_REGISTERED, $idpId, $mosConfig_sitename);
		return;
	}

	// get user's profile from his Identity Provider
    try {
		$client = @new SoapClient("$idpId/index.php?option=$option&task=wsdl");
        $response = @$client->GetUserAccount($handle, $mosConfig_live_site, $_SERVER['REMOTE_ADDR']);
    }
    catch (SoapFault $exception) {
        echo _SSO_ERROR . ' ' . $exception->faultstring;
        return;
    }
	
	$userAccount = $response['UserAccount'];

	if (! $userAccount->name || ! $userAccount->username || ! $userAccount->email) {
		echo _SSO_ERROR . ' ';
		printf(_SSO_FAILURE_RESPONSE, $idpId);
        return;
	}
	
	$userAccount->username = addslashes(substr($userAccount->username, 0, 25));
	$userAccount->name = addslashes(substr($userAccount->name, 0, 50));
	$userAccount->email = addslashes(substr($userAccount->email, 0, 100));
	
	// check if the user is registered
	$database->setQuery( "SELECT a.username, a.password, a.name, a.email FROM #__users AS a LEFT JOIN #__sso_users AS b ON a.id = b.id WHERE b.ssoOrigUsername='$userAccount->username' AND b.ssoIdentityProvider='$idpId'" );
	$database->loadObject($row);
	if ($database->getErrorNum()) {
		echo _SSO_ERROR . ' ' . _SSO_DATABASE_ERROR;
		return;
	}

	if ( ! $row ) {
		// user is not registered
		$row = create_account($userAccount, $idp);
		if ($row === false) {
			return;
		}
	}
	else {
		// user is already registered
        // update user's account if necessary
        if ($row->name != $userAccount->name || $row->email != $userAccount->email) {
			$database->setQuery( "UPDATE `#__users` SET `name`='$userAccount->name', `email`='$userAccount->email' WHERE `id` = (SELECT `id` FROM #__sso_users WHERE `ssoOrigUsername`='$userAccount->username' AND `ssoIdentityProvider`='$idpId'" );
			$database->query();
		}
	}
	if ( loginUser($row->username, $row->password) ) {
		mosRedirect( "index.php" );
	} else {
		echo _SSO_ERROR . ' ' . sprintf(_SSO_FAILURE_LOGIN_FAILED, $row->username);
		return;
	}
}


function create_account(&$userAccount, &$idp) {
	global $database, $mainframe, $option, $mosConfig_live_site, $mosConfig_sitename, $mosConfig_absolute_path;

	// check if the email is already registered
	$database->setQuery( "SELECT a.username, a.id, b.ssoOrigUsername, b.ssoIdentityProvider FROM #__users AS a LEFT JOIN #__sso_users AS b ON a.id = b.id WHERE a.email='$userAccount->email'" );
	if ( $database->loadObject( $existingAccount ) ) {
		// user's email is already registered
		if ( $existingAccount->ssoIdentityProvider ) {
			// user already has an account at some other idp
			$idpLink = "<a href='$existingAccount->ssoIdentityProvider'>$existingAccount->ssoIdentityProvider</a>";
			$loginLink = "$existingAccount->ssoIdentityProvider/index.php?option=$option&task=idplogin&sp=" . urlencode($mosConfig_live_site);
			echo _SSO_ERROR . ' ';
			printf(_SSO_MAIL_ALREADY_REGISTERED1, $userAccount->email, $mosConfig_sitename, $existingAccount->ssoOrigUsername, $idpLink, $loginLink);
			return false;
		} else {
			if($userAccount->username != $existingAccount->username || !$idp->trusted) {
				// user already has a local account
				// but the usernames don't match or their identity provider isn't trusted
				echo _SSO_ERROR . ' ';
				printf(_SSO_MAIL_ALREADY_REGISTERED2, $userAccount->email, $mosConfig_sitename, $existingAccount->username);
				return false;
			} else {
				// we found a user and we are trusted, log them into their local account
				$user = new mosUser($database);
				$user->load($existingAccount->id);
				return $user;
			}
		}
	}

	// save registration
	global $acl, $mosConfig_allowUserRegistration;

	if ($mosConfig_allowUserRegistration == "0") {
		echo _SSO_ERROR . ' ' . _SSO_REGISTRATION_NOT_ALLOWED;
		return false;
	}

    $username = substr($userAccount->username, 0, 20) . '@' . $idp->abbreviation;
	
	// check if the username is already registered
    // it is possible that more than one username exist at the same Identity Provider with the same first 20 characters
    // maximum length of the username is 25 characters
	$database->setQuery( "SELECT username FROM #__users WHERE username='$username'" );
	if ( $database->loadResult() ) {
		// username is already registered
        require_once("$mosConfig_absolute_path/administrator/components/$option/classes/ssoUtils.class.php");
        $hash = ssoUtils::generateHash($userAccount->username);
		$username = substr($userAccount->username, 0, 15) . '_' . $hash . '@' . $idp->abbreviation;
	}
    

	$row = new mosUser( $database );
	$row->id = 0;
	$row->name = $userAccount->name;
	$row->username = $username;
	$row->email = $userAccount->email;
	$row->usertype = 'Registered';
	$row->gid = $acl->get_group_id('Registered','ARO');
	$row->block = "0";
	$row->password = md5( makePassword(30) );
	$row->registerDate = date("Y-m-d H:i:s");
	//$row->ssoIdentityProvider = $idp->providerId;
	//$row->ssoOrigUsername = $userAccount->username;

	if ( ! $row->check() ) {
		echo _SSO_ERROR . ' ';
		printf(_SSO_FAILURE_CREATE_ACCOUNT, $username);
		echo ' ' . $row->getError();
		return false;
	}

	if ( ! $row->store() ) {
		echo _SSO_ERROR . ' ';
		printf(_SSO_FAILURE_CREATE_ACCOUNT, $username);
		echo ' ' . $row->getError();
		return false;
	}
	$row->checkin();
	$database->setQuery("INSERT INTO #__sso_users VALUES(".$row->id.",'".$idp->providerId."','".$userAccount->username."')");
	$database->Query();
	
	return $row;
}


function loginUser($username, $encodedPassword) {
	global $_VERSION;
	if($_VERSION->RELEASE == '1.5') {
		return doJ15SSO($username);	
	} else {
		return doJ10SSO($username);
	}
}

function loginUserUsingPost() {
	global $mainframe, $my;
	$mainframe->login();
	$my->load($mainframe->_session->userid); // reload $my
}

function logout() {
	global $my, $mainframe;
	if ( $my->id ) { // user is logged in to Joomla!
		$mainframe->logout();
	}
	mosRedirect( "index.php" );
}

function wsdl() {
    global $mosConfig_absolute_path, $mosConfig_live_site, $option;
    
	while (@ob_end_clean());
    ob_start();

    header('Content-Type: text/xml');
    $wsdl = file_get_contents("$mosConfig_absolute_path/administrator/components/$option/includes/ssoService.wsdl");
    echo preg_replace('/%SITE_URL%/', $mosConfig_live_site, $wsdl);
    exit;
}

function handleSOAPRequest() {
	global $mosConfig_live_site, $mosConfig_absolute_path, $option;

    while (@ob_end_clean());
    ob_start();

	require_once("$mosConfig_absolute_path/administrator/components/$option/classes/ssoService.class.php");
	$server = new SoapServer("$mosConfig_live_site/index.php?option=$option&task=wsdl");
    $server->setClass("ssoService");
	$server->handle(file_get_contents('php://input'));
	exit();
}

function showProvidersList() {
	global $database, $mosConfig_live_site, $mosConfig_sitename, $my;
	$query = "SELECT * " .
	         "FROM #__sso_providers " .
	         "WHERE published='1' AND providerId<>'LOCAL' " .
	         "ORDER BY siteName ";
	$database->setQuery( $query );
	$providers = $database->loadObjectList();

	if ( $my->id ) {
		$database->setQuery( "SELECT a.name, b.ssoIdentityProvider AS identityProvider FROM #__users AS a LEFT JOIN #__sso_users AS b ON a.id = b.id WHERE a.id='$my->id'" );
		if ( ! $database->loadObject($user) ) {
			echo $database->getQuery();
			echo _SSO_ERROR . ' ' . _SSO_DATABASE_ERROR;
			return;
		}
		
		$idp = new ssoProvider($database);
		if ($user->identityProvider && $user->identityProvider != $mosConfig_live_site) { // user comes from remote idp
			if ( ! $idp->load($user->identityProvider) || $idp->status != 'REGISTERED' || ! $idp->published) {
				echo _SSO_ERROR . ' ';
				printf(_SSO_FAILURE_IDP_NOT_REGISTERED, $user->identityProvider, $mosConfig_sitename);
				return;
			}
		}
		else { // user has a local account
			$idp->loadMyself();
		}
	} else {
		$user = null;
		$idp = null;
	}

	ssoHTML::showProvidersList($providers, $user, $idp);
}

function checkOnline(){
    global $option, $database, $mosConfig_live_site, $mosConfig_absolute_path;

    while (@ob_end_clean());
    ob_start();
    $url = mosGetParam( $_REQUEST, 'url', '' );

	$handle = @fopen($url, "r");
	if ( ! $handle) {
		header("Location: $mosConfig_live_site/components/$option/images/offline.gif");
		exit;
	}
	fclose($handle);
	header("Location: $mosConfig_live_site/components/$option/images/online.gif");
	exit;
}

