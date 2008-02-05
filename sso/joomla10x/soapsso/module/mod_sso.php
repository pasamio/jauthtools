<?php
/**
* @version 	$Id: mod_sso.php,v V1.1 4766 bytes, 2007-06-07 12:51:40 cero Exp $
* @package 	SSO
* @subpackage 	mod_sso.php
* @author	<Tomo.Cerovsek.fgg.uni-lj.si> <Damjan.Murn.uni-lj.si>
* @developers	Tomo Cerovsek, Damjan Murn
* @copyright 	(C) 2007 SSO Team, UL FGG
* @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
* SSO was initiated during the EU CONNIE project
*/

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

require_once("$mosConfig_absolute_path/administrator/components/com_sso/classes/ssoProvider.class.php");

// include language file
if (is_file("$mosConfig_absolute_path/components/com_sso/language/$mosConfig_lang.php")) {
	include_once ("$mosConfig_absolute_path/components/com_sso/language/$mosConfig_lang.php");
} else {
	include_once ("$mosConfig_absolute_path/components/com_sso/language/english.php");
}

if ( $my->id ) { // user is logged in to Joomla!
	$database->setQuery( "SELECT a.name, b.ssoIdentityProvider AS identityProvider FROM #__users AS a LEFT JOIN #__sso_users AS b ON a.id = b.id WHERE a.id='$my->id'" );	
	if ( ! $database->loadObject($user) ) {
		echo _SSO_ERROR . ' ' . _SSO_DATABASE_ERROR ;
		return;
	}

    $idp = new ssoProvider($database);
	if ($user->identityProvider && $user->identityProvider != $mosConfig_live_site) { // user comes from remote idp
		if ( ! $idp->load($user->identityProvider) || $idp->status != 'REGISTERED' || ! $idp->published) {
			echo _SSO_ERROR . ' ';
			printf(_SSO_FAILURE_IDP_NOT_REGISTERED_SHORT, $user->identityProvider, $mosConfig_sitename);
			return;
		}

		printf('<b>' . _SSO_LOGGED_IN_REMOTE . '</b>', $user->name, $idp->siteName);
	}
	else { // the user's account is local
		$idp->providerId = $mosConfig_live_site;
		printf('<b>' . _SSO_LOGGED_IN_LOCAL . '</b>', $user->name);
	}
	?>
	<br />
	<a href='index.php?option=com_sso&task=logout'><?php echo _SSO_LOGOUT ?></a>
	<br />
	<?php

	// read Service Providers from database to which the user can make sso
	$query = "SELECT providerId, siteName " .
			"FROM #__sso_providers " .
	        "WHERE published='1' AND status='REGISTERED' AND providerId<>'LOCAL' " .
			"ORDER BY siteName";
	$database->setQuery($query);
	$sps = $database->loadObjectList();

	if (! $sps) {
		echo _SSO_NO_SP_REGISTERED;
	}
	else if (count($sps) > 5) {
		echo _SSO_YOU_CAN_SSO_TO;
		?>
		<form name="form_mod_sso">
		<select name="sp" class="inputbox">
		<option value=""><?php echo _SSO_PLEASE_SELECT ?></option>
		<?php
		foreach ($sps as $sp) {
			?>
			<option value="<?php echo urlencode($sp->providerId) ?>" title="<?php echo $sp->siteName ?>"><?php echo substr($sp->siteName, 0, 16) ?></option>
			<?php
		}
		?>
		</select>
		<button class="button" onclick="javascript:if (form_mod_sso.sp.value) document.location.href='<?php echo $idp->providerId ?>/index.php?option=com_sso&task=idplogin&sp=' + form_mod_sso.sp.value; return false;"><?php echo _SSO_MAKE_SSO ?></button>
		</form>
		<?php
	}
	else {
		echo _SSO_YOU_CAN_SSO_TO;
		?>
		<ul>
		<?php
		foreach ($sps as $sp) {
			?>
			<li><a href="<?php echo $idp->providerId ?>/index.php?option=com_sso&task=idplogin&sp=<?php echo urlencode($sp->providerId) ?>" title="<?php echo $sp->siteName ?>"><?php echo substr($sp->siteName, 0, 16) ?></a></li>
			<?php
		}
		?>
		</ul>
		<?php
	}
}

else { // user is not logged in to Joomla!
	$query = "SELECT providerId, siteName " .
			"FROM `#__sso_providers` " .
			"WHERE published='1' AND providerId<>'LOCAL' AND status='REGISTERED' " .
			"ORDER BY siteName";
	$database->setQuery( $query );
	$idps = $database->loadObjectList();
	if ($database->getErrorNum()) {
		echo _SSO_ERROR . ' ' . _SSO_DATABASE_ERROR;
		return;
	}
	
	if (! $idps) {
		echo _SSO_NO_IDP_REGISTERED;
		return;
	}
	else if (count($idps) > 5) {
		?>
		<b><?php echo _SSO_SELECT_YOUR_IDP ?></b>
		<form name="form_mod_sso">
		<select name="idp" class="inputbox">
		<option value=""><?php echo _SSO_PLEASE_SELECT ?></option>
		<?php
		foreach ($idps as $idp) {
			?>
			<option value="<?php echo $idp->providerId ?>" title="<?php echo $idp->siteName ?>"><?php echo substr($idp->siteName, 0, 16) ?></option>
			<?php
		}
		?>
		</select>
		<button class="button" onclick="javascript:if (form_mod_sso.idp.value) document.location.href=form_mod_sso.idp.value + '/index.php?option=com_sso&task=idplogin&sp=<?php echo urlencode($mosConfig_live_site) ?>'; return false;"><?php echo _SSO_LOGIN ?></button>
		</form>
		<?php
	}
	else {
		?>
		<b><?php echo _SSO_SELECT_YOUR_IDP ?></b>
		<ul>
		<?php
		foreach ($idps as $idp) {
			?>
			<li><a href="<?php echo $idp->providerId ?>/index.php?option=com_sso&task=idplogin&sp=<?php echo urlencode($mosConfig_live_site) ?>" title="<?php echo $idp->siteName ?>"><?php echo substr($idp->siteName, 0, 16) ?></a></li>
			<?php
		}
		?>
		</ul>
		<?php
	}
}
?>
