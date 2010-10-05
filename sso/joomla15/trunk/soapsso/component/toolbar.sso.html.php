<?php
/**
* @version 	$Id: toolbar.sso.html.php,v V1.1 1471 bytes, 2007-06-07 12:43:34 cero Exp $
* @package 	SSO
* @author	Tomo Cerovsek <Tomo.Cerovsek.fgg.uni-lj.si> 
* @author	Damjan Murn <Damjan.Murn.uni-lj.si>
* @copyright 	(C) 2009 SSO Team, UL FGG
* @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
* SSO was initiated during the EU CONNIE project
*/

defined('_VALID_MOS') or die('Direct access to this location is not allowed.');

/**
 * Toolbar file SSO
 * @package SSO
 */
class TOOLBAR_sso {

    function show_providers_menu() {
        mosMenuBar::startTable();
        mosMenuBar::addNew('add', 'Add');
        mosMenuBar::editListX();
        mosMenuBar::deleteList();
        mosMenuBar::publishList();
        mosMenuBar::unpublishList();
		mosMenuBar::custom('ping', 'move.png', 'move_f2.png', 'Ping', true);
		mosMenuBar::custom('refresh', 'restore.png', 'restore_f2.png', 'Refresh', false);
        mosMenuBar::endTable();
    }

    function edit_provider_menu() {
        mosMenuBar::startTable();
		mosMenuBar::custom('updateProvider', 'restore.png', 'restore_f2.png', 'Refresh', false);
		mosMenuBar::save();
		mosMenuBar::cancel( 'cancel', 'Close' );
        mosMenuBar::endTable();
    }

    function configuration_menu() {
        mosMenuBar::startTable();
        mosMenuBar::save('saveConf', 'Save');
		mosMenuBar::cancel( 'cancel', 'Close' );
        mosMenuBar::endTable();
    }

}

?>
