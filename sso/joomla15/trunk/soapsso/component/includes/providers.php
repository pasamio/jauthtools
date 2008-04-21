<?php
/**
* @version 	$Id: providers.php,v V1.1 12270 bytes, 2007-06-07 16:09:12 cero Exp $
* @package 	SSO
* @author	Tomo Cerovsek <Tomo.Cerovsek.fgg.uni-lj.si> 
* @author	Damjan Murn <Damjan.Murn.uni-lj.si>
* @copyright 	(C) 2007 SSO Team, UL FGG
* @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
* SSO was initiated during the EU CONNIE project
*/

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

/** SSO Provider Class */
require_once( "$mosConfig_absolute_path/administrator/components/$option/classes/ssoProvider.class.php" );
/** SSO Utilities Class */
require_once( "$mosConfig_absolute_path/administrator/components/$option/classes/ssoUtils.class.php" );
/** SSO HTML Drawing Class */
require_once( "$mosConfig_absolute_path/administrator/components/$option/includes/providers.html.php" );

$option = mosGetParam($_REQUEST,'option','com_sso');
$section = mosGetParam($_REQUEST,'section','providers');

switch ($task) {
	case 'add':
		addProvider();
		break;

	case 'edit':
		edit( $cid[0] );
		break;

	case 'editA':
        $providerId = mosGetParam($_REQUEST, 'providerId', '');
        edit($providerId);
        break;

	case 'save':
		saveRemoteProvider();
		break;

	case 'remove':
		ssoProvider::remove($cid);
		mosRedirect("index2.php?option=$option&section=$section");
		break;

	case 'cancel':
		cancel();
		break;

	case 'ping':
		$message = pingProviders($cid);
		show($message);
		break;

	case 'pingA':
	    $providerId = mosGetParam($_REQUEST, 'providerId', '');
		$message = ping($providerId);
		show($message);
		break;

	case 'performOperation':
		$message = performOperation();
		show($message);
		break;

	case 'publish':
		ssoProvider::publish($cid, 1);
		mosRedirect("index2.php?option=$option&section=$section");
		break;

	case 'unpublish':
		ssoProvider::publish($cid, 0);
		mosRedirect("index2.php?option=$option&section=$section");
		break;

	case 'configuration':
		editLocalProvider();
		break;

	case 'saveConf':
		saveLocalProvider();
		break;

	case 'updateProvider':
		updateProviderData();
		break;
	
	default:
		show();
		break;
}


/**
* List the records
* @param string The current GET/POST option
*/
function show($message='') {
	global $database, $mainframe, $mosConfig_list_limit, $option, $mosConfig_live_site;
	$section = mosGetParam($_REQUEST,'section','providers');
	

    $limit = $mainframe->getUserStateFromRequest("viewlistlimit", 'limit', $mosConfig_list_limit);
    $limitstart = $mainframe->getUserStateFromRequest("view{$option}{$section}limitstart", 'limitstart', 0);
	$search 	= $mainframe->getUserStateFromRequest( "search{$option}", 'search', '' );
	$search 	= $database->getEscaped( trim( strtolower( $search ) ) );

    // check myself node
	$myself = new ssoProvider($database);
	if (! $myself->loadMyself() || ! $myself->checkLocalProviderData()) {
		$message = "Please fill in the missing data about your portal.";
		mosRedirect("index2.php?option=$option&section=$section&task=configuration", $message);
	}

    $where = array();
	$where[] = "providerId <> 'LOCAL'";
    if ($search) {
        $where[] = "(providerId LIKE '%$search%' OR siteName LIKE '%$search%')";
    }

	// get the total number of records
	$query = "SELECT COUNT(*) FROM #__sso_providers WHERE " . implode(' AND ', $where);
    $database->setQuery($query);
    $total = $database->loadResult();

    if ($database->getErrorNum()) {
        echo $database->getErrorMsg();
        return;
    }

	require_once( $GLOBALS['mosConfig_absolute_path'] . '/administrator/includes/pageNavigation.php' );
	$pageNav = new mosPageNav( $total, $limitstart, $limit );

	// get the subset (based on limits) of required records
	$query = "SELECT * " .
	         "FROM #__sso_providers " .
	         "WHERE " . implode(' AND ', $where) . ' ' .
	         "ORDER BY siteName " .
	         "LIMIT $pageNav->limitstart,$pageNav->limit";
	$database->setQuery( $query );
	$providers = $database->loadObjectList();

	if ($database->getErrorNum()) {
		echo $database->getErrorMsg();
		return;
	}

	HTML_providers::show($providers, $pageNav, $search, $message);
}


function addProvider() {
	global $option, $mosConfig_live_site, $database;
	$section = mosGetParam($_REQUEST,'section','providers');
	
	$url = mosGetParam($_REQUEST , 'url' , '');
	$url = preg_replace('/(\/index\.php|\/)$/', '', $url);
	$wsdlUrl = "$url/index.php?option=$option&task=wsdl";
	
	$provider = new ssoProvider($database);
	if ($provider->load($url)) {
		$message = "Provider $url already exists.";
        mosRedirect("index2.php?option=$option&section=$section", $message);
	}
	
	if(!class_exists('SoapClient')) {
		$message = "SOAP is not installed! Install SOAP to continue";
		mosRedirect("index2.php?option=$option&section=$section",$message);
	}
    try {
		$client = new SoapClient($wsdlUrl);
        $response = $client->GetProviderInfo($mosConfig_live_site);
    }
    catch (SoapFault $exception) {
		if ((isset($client) && ! $client) || $exception->faultcode == 'WSDL') {
			$message = "Error: cannot load WSDL document from $wsdlUrl. Please check the URL $url which should belong to a website based on Joomla! CMS. " .
				"Also make sure that Joomla! Single Sign-On component is installed at $url. " .
				"Please ask the administrator of that site to download the component from " .
				"<a href='http://joomlacode.org/gf/project/jauthtools/'>http://joomlacode.org/gf/project/jauthtools/</a> and install it.";

			show($message);
			return;
		}
		$message = "Add provider failed: $exception->faultcode: $exception->faultstring";
		mosRedirect("index2.php?option=$option&section=$section", $message);
    }
	
	$provider = new ssoProvider($database);
	$provider->fromStdClass($response);
	$provider->abbreviation = ssoUtils::generateHash($provider->providerId);
	$provider->status = 'UNREGISTERED';
	$provider->published = '1';

	if ($provider->providerId == $mosConfig_live_site) {
		$message = "You cannot add yourself to the providers list.";
        mosRedirect("index2.php?option=$option&section=$section", $message);
	}
	
	if ( ! $provider->insert()) {
		echo "<b>Failed to add provider $url</b>: " . $provider->getError();
		return;
	}
	$message = "Provider $provider->providerId has been added successfully.";
	mosRedirect("index2.php?option=$option&section=$section", $message);
}


function updateProviderData() {
	global $option, $mosConfig_live_site, $database;
	$section = mosGetParam($_REQUEST,'section','providers');
	
	$providerId = mosGetParam($_REQUEST , 'providerId' , '');

	$provider = new ssoProvider($database);
	if ( ! $provider->load($providerId)) {
		echo "Provider $providerId doesn't exist.";
		return;
	}
	
    try {
		$client = @new SoapClient("$providerId/index.php?option=$option&task=wsdl");
        $response = @$client->GetProviderInfo($mosConfig_live_site);
    }
    catch (SoapFault $exception) {
		$message = "Update failed: $exception->faultcode: $exception->faultstring";
		mosRedirect("index2.php?option=$option&section=$section&task=editA&providerId=" . urlencode($providerId), $message);
    }
	
	$provider->fromStdClass($response);

	if ( ! $provider->update()) {
		echo $database->getErrorMsg();
		return;
	}
	$message = "Provider's data has been updated successfully.";
	mosRedirect("index2.php?option=$option&section=$section&task=editA&providerId=" . urlencode($providerId), $message);
}


function edit( $providerId ) {
	global $database, $option;
	$section = mosGetParam($_REQUEST,'section','providers');
	

	$provider = new ssoProvider($database);
	if (! $provider->load($providerId)) {
		echo "Error: the provider $providerId doesn't exist.";
		return;
	}

    HTML_providers::edit($provider);
}

function editLocalProvider() {
	global $database, $mosConfig_live_site, $mosConfig_sitename;

	$provider = new ssoProvider($database);
	if (! $provider->loadMyself()) {
		$provider->init_record();
		$provider->providerId = 'LOCAL';
		$provider->siteName = $mosConfig_sitename;
		$provider->ipAddress = $_SERVER['SERVER_ADDR'];
	}

    HTML_providers::editLocalProvider($provider);
}


/**
* Saves the record from an edit form submit
* @param string The current GET/POST option
*/
function saveRemoteProvider() {
	global $database, $option;
	$section = mosGetParam($_REQUEST,'section','providers');
	
	$provider = new ssoProvider($database);
	$provider->saveRemoteProvider();
	mosRedirect("index2.php?option=$option&section=$section");
}

function saveLocalProvider() {
	global $database, $option;
	$section = mosGetParam($_REQUEST,'section','providers');
	
	$provider = new ssoProvider($database);
	$provider->saveLocalProvider();
	mosRedirect("index2.php?option=$option&section=$section");
}


/**
* Cancels an edit operation
* @param string The current GET/POST option
*/
function cancel() {
	global $database, $option;
	$section = mosGetParam($_REQUEST,'section','providers');
	
    mosRedirect("index2.php?option=$option&section=$section");
}


function operationsSelectList($provider){
	$operations = array();

	switch($provider->status){

		case 'REGISTERED':
			$operations[] = mosHTML::makeOption( '0', 'Select' );
			$operations[] = mosHTML::makeOption( 'Unregister', 'Unregister' );
			$operations[] = mosHTML::makeOption( 'Deny', 'Deny' );
			break;
		case 'REG_REQ_SENT':
			$operations[] = mosHTML::makeOption( '0', 'Select' );
			$operations[] = mosHTML::makeOption( 'CancelRegReq', 'Cancel' );
			break;
		case 'REG_REQ_RECV':
			$operations[] = mosHTML::makeOption( '0', 'Select' );
			$operations[] = mosHTML::makeOption( 'Approve', 'Approve' );
			$operations[] = mosHTML::makeOption( 'Deny', 'Deny' );
			break;
		case 'DENIAL_SENT':
			$operations[] = mosHTML::makeOption( '0', 'Select' );
			$operations[] = mosHTML::makeOption( 'Approve', 'Allow' );
			break;
		case 'DENIAL_RECV':
			break;
		case 'UNREGISTERED':
			$operations[] = mosHTML::makeOption( '0', 'Select' );
			$operations[] = mosHTML::makeOption( 'Apply', 'Apply for registration' );
			break;
		default:
			;
	} // switch
    return $operations;
}


function pingProviders($cid){
    $message = array();
    foreach($cid as $providerId){
        $message[] = ping($providerId);
    }
    return implode('<br/>', $message);
}

function ping($providerId){
    global $option;

	try {
		$client = @new SoapClient("$providerId/index.php?option=$option&task=wsdl");
        $response = @$client->Ping();
    }
    catch (SoapFault $exception) {
		return "Provider $providerId is offline.";
    }

    return "Provider $providerId is online.";
}


function performOperation(){
    global $option, $database, $mosConfig_live_site;
    $section = mosGetParam($_REQUEST,'section','providers');
    
    $providerId = mosGetParam($_REQUEST, 'providerId', '');
    $operation = mosGetParam($_REQUEST, "operation", '');

	$provider = new ssoProvider($database);
	if (! $provider->load($providerId)) {
		echo "Error: the provider $providerId doesn't exist.";
		return;
	}
	
    try {
		$client = @new SoapClient("$providerId/index.php?option=$option&task=wsdl");
        $response = @$client->ManageRegistration($mosConfig_live_site, $operation, $provider->status);
    }
    catch (SoapFault $exception) {
		return "Operation failed: $exception->faultcode: $exception->faultstring";
    }

	
	$newStatus = '';
	switch ($operation) {
		case 'Apply':
			switch ($response['NewStatus']) {
				case 'REG_REQ_RECV':
					$newStatus = 'REG_REQ_SENT';
					break;
				case 'REGISTERED':
					$newStatus = 'REGISTERED';
					break;
				case 'DENIAL_SENT':
					$newStatus = 'DENIAL_RECV';
					break;
				default:
					break;
			}
			break;

		case 'Approve':
			switch ($response['NewStatus']) {
				case 'REGISTERED':
					$newStatus = 'REGISTERED';
					break;
				default:
					break;
			}
			break;

		case 'Deny':
			switch ($response['NewStatus']) {
				case 'DENIAL_RECV':
					$newStatus = 'DENIAL_SENT';
					break;
				default:
					break;
			}
			break;

		case 'CancelRegReq':
			switch ($response['NewStatus']) {
				case 'UNREGISTERED':
					$newStatus = 'UNREGISTERED';
					break;
				default:
					break;
			}
			break;

		case 'Unregister':
			switch ($response['NewStatus']) {
				case 'UNREGISTERED':
					$newStatus = 'UNREGISTERED';
					break;
				default:
					break;
			}
			break;

		default:
			break;
	}

	if ($newStatus && ! $provider->setStatus($newStatus)) {
		return $database->getErrorMsg();
	}
	return "Response from $provider->siteName: " . $response['Message'];
}

?>
