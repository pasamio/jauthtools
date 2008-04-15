<?php
/**
* @version 	$Id: install.sso.php,v V1.1 1918 bytes, 2007-06-14 09:12:34 cero Exp $
* @package 	SSO
* @subpackage 	install.sso.php
* @author	Tomo Cerovsek <Tomo.Cerovsek.fgg.uni-lj.si> 
* @author	Damjan Murn <Damjan.Murn.uni-lj.si>
* @copyright 	(C) 2007 SSO Team, UL FGG
* @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
* SSO was initiated during the EU CONNIE project
*/

/**
 * Joomla! installation hook
 */
function com_install() {
	global $database;

	/*$fields = $database->getTableFields(array('#__users'));
	if ( ! array_key_exists('ssoIdentityProvider', $fields['#__users'])) {
		$database->setQuery("ALTER TABLE #__users ADD ssoIdentityProvider VARCHAR(100) NOT NULL default '';");
		$database->query() or die($database->stderr());
	}
	if ( ! array_key_exists('ssoOrigUsername', $fields['#__users'])) {
		$database->setQuery("ALTER TABLE #__users ADD ssoOrigUsername VARCHAR(25) NOT NULL default '';");
		$database->query() or die($database->stderr());
	}*/

	?>
	<div align="left">
	<table width="50%">
	<tr>
	<td>
	<code>Installation: <font color="green">successful</font></code><br />
	<h2>
	Thank you for installing Joomla! Single Sign-On!</h2>
	Copyright (C) 2007 Tomo Cerovsek & Damjan Murn <br />
	Distributed under the terms of the GNU General Public License <br />

	This software may be used without warranty provided and these statements are left intact.<br />
	The source is available at <a href="http://joomlacode.org/gf/project/sso/">http://joomlacode.org/gf/project/sso/</a>
	<br /><br />
	To complete the installation please go to the
	<a href="index2.php?option=com_sso&section=providers&task=configuration">Configuration page</a>
	and enter required information.
	<br /><br />
	The administration of Joomla! Single Sign-On component can be found under the menu Components -> Joomla! Single Sign-On.
	<br /><br />
	Please note that this component requires PHP5 to operate. If you do not have PHP5 then this component will not operate.
	Additionally this is the first release in 1.0.x/1.5(Legacy) for this component, there might still be issues. 
	</td>
	</tr>
	</table>
	</div>
	<?php
}
?>