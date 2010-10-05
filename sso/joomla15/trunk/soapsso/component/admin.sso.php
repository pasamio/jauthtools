<?php
/**
* @version 	$Id: admin.sso.php,v V1.1 916 bytes, 2007-06-07 12:43:34 cero Exp $
* @package 	SSO
* @author	Tomo Cerovsek <Tomo.Cerovsek.fgg.uni-lj.si> 
* @author	Damjan Murn <Damjan.Murn.uni-lj.si>
* @copyright 	(C) 2009 SSO Team, UL FGG
* @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
* SSO was initiated during the EU CONNIE project
*/

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

// ensure user has access to this function
if (!($acl -> acl_check('administration', 'edit', 'users', $my -> usertype, 'components', 'all') | $acl -> acl_check('administration', 'edit', 'users', $my -> usertype, 'components', 'com_sso'))){
	mosRedirect('index2.php', _DML_NOT_AUTH);
}

$cid = mosGetParam($_POST, 'cid', array());
$section = mosGetParam($_REQUEST, 'section', 'providers');
if ($section){
	/** Load section file */
	include_once (dirname(__FILE__) . "/includes/$section.php");
} else echo "Failed to detect section";
?>
