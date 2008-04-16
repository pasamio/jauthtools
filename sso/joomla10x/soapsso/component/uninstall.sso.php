<?php
/**
* @version 	$Id: uninstall.sso.php,v V1.1 1657 bytes, 2007-06-07 12:43:34 cero Exp $
* @package 	SSO
* @author	Tomo Cerovsek <Tomo.Cerovsek.fgg.uni-lj.si> 
* @author	Damjan Murn <Damjan.Murn.uni-lj.si>
* @copyright 	(C) 2007 SSO Team, UL FGG
* @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
* SSO was initiated during the EU CONNIE project
*/

/**
 * Joomla! Uninstall Hook
 */
function com_uninstall() {
    global $database;

    // delete remote user accounts
    $query = "SELECT `providerId` FROM `#__sso_providers` WHERE `providerId` <> 'LOCAL'";
    $database->setQuery($query);
    $providers = $database->loadObjectList();
    if ( ! is_array($providers)) {
        $providers = array();
    }
	
	$providersList = '';
	foreach ($providers as $provider) {
		$providersList .= "'" . $provider->providerId . "',";
	}
	$providersList = rtrim($providersList, ",");

	if ($providersList) {
		$userObj = new mosUser( $database );

		$query = "SELECT `id` FROM `#__sso_users` WHERE `ssoIdentityProvider` IN ($providersList)";
		$database->setQuery($query);
		$users = $database->loadObjectList();
		
		foreach ($users as $user) {
			$userObj->delete( $user->id );
		}
	}


    /*$database->setQuery("ALTER TABLE `#__users` DROP COLUMN `ssoIdentityProvider`;");
    $database->query();

    $database->setQuery("ALTER TABLE `#__users` DROP COLUMN `ssoOrigUsername`;");
    $database->query();*/

    $database->setQuery("DROP TABLE IF EXISTS `#__sso_users`;");
    $database->Query();
    
    $database->setQuery("DROP TABLE IF EXISTS `#__sso_providers`;");
    $database->query();

    $database->setQuery("DROP TABLE IF EXISTS `#__sso_handles`;");
    $database->query();
}

?>