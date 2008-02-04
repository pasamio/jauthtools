<?php
/**
* @version 	$Id: ssoService.class.php,v V1.1 12019 bytes, 2007-06-07 12:43:34 cero Exp $
* @package 	SSO
* @subpackage 	ssoService.class.php
* @author	<Tomo.Cerovsek.fgg.uni-lj.si> <Damjan.Murn.uni-lj.si>
* @developers	Tomo Cerovsek, Damjan Murn
* @copyright 	(C) 2007 SSO Team, UL FGG
* @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
* SSO was initiated during the EU CONNIE project
*/

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

require_once("$mosConfig_absolute_path/administrator/components/$option/classes/ssoProvider.class.php");
require_once("$mosConfig_absolute_path/administrator/components/$option/classes/ssoUtils.class.php");

class ssoService {

    function GetUserAccount($handle, $spId, $userIP) {
        global $database, $mosConfig_live_site, $my;
        
        ssoServiceUtils::checkHostname($spId);
        
        $handle = addslashes(substr($handle, 0, 128));
        $spId = addslashes(substr($spId, 0, 100));
        $userIP = addslashes(substr($userIP, 0, 15));
        
        // delete expired handles
        $query = "DELETE FROM `#__sso_handles` WHERE `time` < DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        $database->setQuery( $query );
        $database->query();

        $query = "SELECT username " .
                 "FROM `#__sso_handles` " .
                 "WHERE `handle` = '$handle' AND `spId` = '$spId' AND `userIP` = '$userIP'";
        $database->setQuery( $query );
        if ( ! ($username = $database->loadResult())) {
            throw new SoapFault("Client", 'Your session has expired. Please check your original site.');
        }

        $query = "SELECT username, name, email " .
                 "FROM `#__users` " .
                 "WHERE username='$username'";
        $database->setQuery( $query );
        if ( ! ($database->loadObject($userAccount))) {
            throw new SoapFault("Client", "The user $username does not exist.");
        }

        return array($mosConfig_live_site, $userAccount);
    }

    function GetProviderInfo($remoteProviderId) {
        global $database, $mosConfig_live_site;
        
        ssoServiceUtils::checkHostname($remoteProviderId);
        
        $myself = new ssoProvider($database);
        if ( ! $myself->loadMyself()) {
            throw new SoapFault('Server', 'SSO configuration error.');
        }
        $myself->providerId = $mosConfig_live_site;
        $myself->siteUrl = $mosConfig_live_site;

        return $myself->getPublicData();
    }
    
    
    function ManageRegistration($remoteProviderId, $operation, $status) {
        global $database, $mosConfig_live_site;    

        ssoServiceUtils::checkHostname($remoteProviderId);

        if ($remoteProviderId == $mosConfig_live_site) {
            throw new SoapFault('Client', 'You cannot register at yourself.');
        }

        $remoteProvider = new ssoProvider($database);
        $remoteProvider->load($remoteProviderId);

        switch ($operation) {
            case 'Apply':
                return ssoServiceUtils::applyForRegistration($remoteProviderId);
                break;
            case 'Approve':
                return ssoServiceUtils::registrationIsApproved($remoteProviderId);
                break;
            case 'Deny':
                return ssoServiceUtils::registrationIsDenied($remoteProviderId);
                break;
            case 'CancelRegReq':
                return ssoServiceUtils::cancelRegistrationRequest($remoteProviderId);
                break;
            case 'Unregister':
                return ssoServiceUtils::Unregister($remoteProviderId);
                break;
            default:
                throw new SoapFault('Client', 'Invalid operation.');
                break;
        }
    }
    
    function Ping() {
        return true;
    }
}

class ssoServiceUtils {

	function addProvider($remoteProviderId) {
		global $option, $mosConfig_live_site, $database;
		$section = mosGetParam($_REQUEST,'section','providers');
		
		try {
			$client = @new SoapClient("$remoteProviderId/index.php?option=$option&task=wsdl");
			$response = @$client->GetProviderInfo($mosConfig_live_site);
		}
		catch (SoapFault $exception) {
            throw new SoapFault('Server', "Failed to send SOAP request from $mosConfig_live_site to $remoteProviderId: $exception->faultstring");
		}
		
		$provider = new ssoProvider($database);
		$provider->fromStdClass($response);
        $provider->abbreviation = ssoUtils::generateHash($provider->providerId);
		$provider->status = 'UNREGISTERED';
		$provider->published = '1';
		
		if ( ! $provider->insert()) {
            throw new SoapFault('Server', "Error at $mosConfig_live_site: Failed to store the provider $remoteProviderId.");
		}
        return $provider;
	}


    // remote provider $remoteProvider wants to register at this node
    function applyForRegistration($remoteProviderId) {
        global $database, $mosConfig_live_site, $mosConfig_sitename;

        $remoteProvider = new ssoProvider($database);
        if (! $remoteProvider->load($remoteProviderId)) {
            $remoteProvider = ssoServiceUtils::addProvider($remoteProviderId);
        }

        switch ($remoteProvider->status) {
            case 'REG_REQ_RECV':
                $message = "Your registration request has been already received at the $mosConfig_sitename.";
                $newStatus = 'REG_REQ_RECV';
                break;
            case 'REGISTERED':
                $message = "You are already registered at the $mosConfig_sitename.";
                $newStatus = 'REGISTERED';
                break;
            case 'DENIAL_SENT':
                $message = "You are not allowed to register at the provider $mosConfig_sitename.";
                $newStatus = 'DENIAL_SENT';
                break;
            default:
                $message = "Your registration request has been received and is waiting for approval.";
                $newStatus = 'REG_REQ_RECV';
                break;
        }
        
        if ($newStatus && $remoteProvider->status != $newStatus) {
            if (! $remoteProvider->setStatus($newStatus)) {
                throw new SoapFault("Server", 'Database error.');
            }
        }
        return array($mosConfig_live_site, $newStatus, $message);
    }


    function registrationIsApproved($remoteProviderId) {
        global $database, $mosConfig_live_site, $mosConfig_sitename;

        $remoteProvider = new ssoProvider($database);
        if (! $remoteProvider->load($remoteProviderId)) {
            throw new SoapFault("Client", 'The provider $remoteProviderId is unknown at $mosConfig_sitename.');
        }

        switch ($remoteProvider->status) {
            case 'REG_REQ_SENT':
                $message = "You are now registered at the provider $mosConfig_sitename.";
                $newStatus = 'REGISTERED';
                break;
            case 'DENIAL_RECV':
                $message = "You are now registered at the provider $mosConfig_sitename.";
                $newStatus = 'REGISTERED';
                break;
            default:
                $message = "No registration request has been sent from the provider $mosConfig_sitename to the $remoteProvider->siteName.";
                $newStatus = '';
                break;
        }
        
        if ($newStatus && $remoteProvider->status != $newStatus) {
            if (! $remoteProvider->setStatus($newStatus)) {
                throw new SoapFault("Server", 'Database error.');
            }
        }
        return array($mosConfig_live_site, $newStatus, $message);
    }

    function registrationIsDenied($remoteProviderId) {
        global $database, $mosConfig_live_site, $mosConfig_sitename;

        $remoteProvider = new ssoProvider($database);
        if (! $remoteProvider->load($remoteProviderId)) {
            throw new SoapFault("Client", 'The provider $remoteProvider->siteName is unknown at $mosConfig_sitename.');
        }

        $message = "Your denial has been received at the $mosConfig_sitename.";
        $newStatus = 'DENIAL_RECV';

        if (! $remoteProvider->setStatus($newStatus)) {
            throw new SoapFault("Server", 'Database error.');
        }
        return array($mosConfig_live_site, $newStatus, $message);
    }

    function cancelRegistrationRequest($remoteProviderId) {
        // remote node wants to cancel its registration request at this node
        global $database, $mosConfig_live_site, $mosConfig_sitename;

        $remoteProvider = new ssoProvider($database);
        if (! $remoteProvider->load($remoteProviderId)) {
            throw new SoapFault("Client", 'The provider $remoteProvider->siteName is unknown at $mosConfig_sitename.');
        }

        switch ($remoteProvider->status) {
            case 'REG_REQ_RECV':
                $message = "Your registration request has been canceled.";
                $newStatus = 'UNREGISTERED';
                break;
            default:
                $message = "There is no pending registration request from you at the $mosConfig_sitename.";
                $newStatus = '';
                break;
        }
        
        if ($newStatus && $remoteProvider->status != $newStatus) {
            if (! $remoteProvider->setStatus($newStatus)) {
                throw new SoapFault("Server", 'Database error.');
            }
        }
        return array($mosConfig_live_site, $newStatus, $message);
    }

    function Unregister($remoteProviderId) {
        // remote node wants to cancel its registration request at this node
        global $database, $mosConfig_live_site, $mosConfig_sitename;

        $remoteProvider = new ssoProvider($database);
        if (! $remoteProvider->load($remoteProviderId)) {
            throw new SoapFault("Client", 'The provider $remoteProvider->siteName is unknown at $mosConfig_sitename.');
        }

        switch ($remoteProvider->status) {
            default:
                $message = "You have been unregistered from the provider $mosConfig_sitename.";
                $newStatus = 'UNREGISTERED';
                break;
        }
        
        if ($newStatus && $remoteProvider->status != $newStatus) {
            if (! $remoteProvider->setStatus($newStatus)) {
                throw new SoapFault("Server", 'Database error.');
            }
        }
        return array($mosConfig_live_site, $newStatus, $message);
    }

    function checkHostname($providerId) {
        global $mosConfig_sitename;
        
        $url = parse_url($providerId);
        if ( ! $url['host'] ) {
            throw new SoapFault("Client", "Invalid providerId.");
        }
        $hostname = $url['host'];

        if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $hostname)) { // $providerId contains IP address
            if ( $_SERVER['REMOTE_ADDR'] != $hostname) {
                throw new SoapFault("Client", "Remote address validation failed: '" . $_SERVER['REMOTE_ADDR'] . "' doesn't match '$hostname'.");
            }
            return true;
        }
        else {
            if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR']) { // request came from the same computer
                return true;
            }

            $remoteAddr = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            if ($remoteAddr == $_SERVER['REMOTE_ADDR']) {
                throw new SoapFault("Client", "Could not resolve IP address " . $_SERVER['REMOTE_ADDR'] . " into DNS name.");
            }
            if ( $remoteAddr != $hostname) {
                throw new SoapFault("Client", "Remote address validation failed: '$remoteAddr' doesn't match '$hostname'.");
            }
            return true;
        }
    }
}
?>
