<?php
/**
* @version 	$Id: toolbar.sso.php,v V1.1 877 bytes, 2007-06-07 12:43:34 cero Exp $
* @package 	SSO
* @author	Tomo Cerovsek <Tomo.Cerovsek.fgg.uni-lj.si> 
* @author	Damjan Murn <Damjan.Murn.uni-lj.si>
* @copyright 	(C) 2009 SSO Team, UL FGG
* @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
* SSO was initiated during the EU CONNIE project
*/

defined('_VALID_MOS') or die('Direct access to this location is not allowed.');

/** Load the Toolbar HTML File */
require_once($mainframe->getPath('toolbar_html'));

$section = mosGetParam($_REQUEST, 'section', 'providers');

switch ($section) {
	case "providers" : {
		switch ($task) {
			case 'edit':
			case 'editA':
				TOOLBAR_sso::edit_provider_menu();
				break;
			case 'configuration':
				TOOLBAR_sso::configuration_menu();
				break;

			default :
				TOOLBAR_sso::show_providers_menu();
		}
	}
	break;
}
?>
