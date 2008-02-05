<?php
/**
* @version 	$Id: sso.html.php,v V1.1 8049 bytes, 2007-06-08 10:03:46 cero Exp $
* @package 	SSO
* @subpackage 	sso.html.php
* @author	<Tomo.Cerovsek.fgg.uni-lj.si> <Damjan.Murn.uni-lj.si>
* @developers	Tomo Cerovsek, Damjan Murn
* @copyright 	(C) 2007 SSO Team, UL FGG
* @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
* SSO was initiated during the EU CONNIE project
*/

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

class ssoHTML {

	function redirect(&$sp, $handle) {
		global $option, $mosConfig_live_site;
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Authentication Request Processed</title>
</head>

<body onload="document.form_sso_redirect.submit()">
<h1>Authentication Request Processed</h1>
<p>You are automatically being redirected to the requested site <?php echo $sp->siteName ?>...

<form name="form_sso_redirect"  action="<?php echo $sp->providerId ?>/index.php?option=<?php echo $option ?>&task=splogin" method="post">
<input type="hidden" name="idpId" value="<?php echo $mosConfig_live_site ?>" />
<input type="hidden" name="handle" value="<?php echo $handle ?>" />
<noscript>
<input type="submit" value="Continue" />
</noscript>
</form>
</body>
</html>
		<?php
	}


	function showLoginPage ( &$params, $image ) {
		/**
		* @version $Id: login.html.php,v 1.6 2005/01/15 06:49:01 stingrey Exp $
		* @package Joomla
		* @subpackage Users
		* @copyright (C) 2000 - 2005 Miro International Pty Ltd
		* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
		* Joomla! is Free Software
		*/
		global $option, $mosConfig_lang;
		?>
		<form action="index.php?option=<?php echo $option ?>&task=login" method="post" name="login" id="login">
		<table width="100%" border="0" align="center" cellpadding="4" cellspacing="0" class="contentpane<?php echo $params->get( 'pageclass_sfx' ); ?>">
		<tr>
			<td colspan="2">
			<?php 
			if ( $params->get( 'page_title' ) ) {
				?>
				<div class="componentheading<?php echo $params->get( 'pageclass_sfx' ); ?>">
				<?php echo $params->get( 'header_login' ); ?>
				</div>
				<?php
			}
			?>
			<div>
			<?php echo $image; ?>
			<?php
			if ( $params->get( 'description_login' ) ) {
				 ?>
				<?php echo $params->get( 'description_login_text' ); ?>
				<br/><br/>
				<?php
			}
			?>
			</div>
			</td>
		</tr>
		<tr>
			<td align="center" width="50%"> 
				<br />
				<table>
				<tr>
					<td align="center">
					<?php echo _SSO_USERNAME; ?>
					<br /> 
					</td>
					<td align="center">
					<?php echo _SSO_PASSWORD; ?>
					<br /> 
					</td>
				</tr>
				<tr>
					<td align="center">
					<input name="username" type="text" class="inputbox" size="20" />
					</td>
					<td align="center">
					<input name="passwd" type="password" class="inputbox" size="20" />
					</td>
				</tr>
				<tr>
					<td align="center" colspan="2">
					<br/>				
					<?php echo _SSO_REMEMBER_ME; ?>
					<input type="checkbox" name="remember" class="inputbox" value="yes" /> 
					<br/>
					<a href="<?php echo sefRelToAbs( 'index.php?option=com_registration&amp;task=lostPassword' ); ?>">
					<?php echo _SSO_PASSWORD_REMINDER; ?>
					</a>
					<?php
					if ( $params->get( 'registration' ) ) {
						?>
						<br/>
						<?php echo _SSO_NO_ACCOUNT_YET; ?>
						<a href="<?php echo sefRelToAbs( 'index.php?option=com_registration&amp;task=register' ); ?>">
						<?php echo _SSO_CREATE_ONE;?>
						</a>
						<?php
					}
					?>
					<br/><br/><br/>
					</td>
				</tr>
				</table>
			</td>
			<td>
			<div align="center">
			<input type="submit" name="submit" class="button" value="<?php echo _SSO_LOGIN; ?>" />
			</div>

			</td>			
		</tr>
		<tr>
			<td colspan="2"> 
			<noscript>
			<?php echo '!Warning! Javascript must be enabled for proper operation.'; ?>
			</noscript>
			</td>
		</tr>
		</table>
		<?php
		// displays back button
		mosHTML::BackButton ( $params );
		?>

		<input type="hidden" name="sp" value="<?php echo mosGetParam( $_REQUEST, 'sp', '' ) ?>" />
		<?php
		global $_VERSION;
		if ($_VERSION->PRODUCT == 'Joomla!') {
			?>
			<input type="hidden" name="<?php echo josSpoofValue(1) ?>" value="1" />
			<?php
		}
		?>
		</form>
		<?php  
  	}

	function showProvidersList(&$providers, $user, $idp) {
		global $option, $mosConfig_live_site;
		?>

		<div class="componentheading"><?php echo _SSO_COMP_NAME, ': ', _SSO_PROVIDERS ?></div>
		<?php
		if ( ! $providers) {
			echo "<br />", _SSO_NO_PROVIDERS_REGISTERED;
			return;
		}
		?>
		<hr size="1" />
		<table cellspacing="5" cellpadding="1">
		<tr>
			<th align="left"><?php echo _SSO_STATUS ?></th>
			<th align="left"><?php echo _SSO_SITE_NAME ?></th>
			<th align="left"><?php echo _SSO_COUNTRY ?></th>
			<th align="left"><?php echo _SSO_URL_ ?></th>
			<th align="center"><?php echo _SSO_ONLINE ?></th>
			<th align="left"><?php echo _SSO_SSO ?></th>
		</tr>
		<tr>
			<td colspan="6"><hr size="1" /></td>
		</tr>

		<?php
		foreach ($providers as $provider) {
			$rnd = rand();
			$checkOnlineLink = "index.php?option=$option&task=checkOnline&url=" . urlencode($provider->siteUrl) . "&rand=$rnd";
			if ($provider->status == 'REGISTERED') {
				$statusImage = "green_jm.png";
			}
			else if ($provider->status == 'REG_REQ_SENT' || $provider->status == 'REG_REQ_RECV') {
				$statusImage = "orange_jm.png";
			}
			else if ($provider->status == 'DENIAL_SENT' || $provider->status == 'DENIAL_RECV') {
				$statusImage = "red_jm.png";
			}
			else {
				$statusImage = 'blank_jm.png';
			}
			
			?>
			<tr>
				<td align="center">
					<?php if ($statusImage) { ?>
					<img src="<?php echo "$mosConfig_live_site/components/$option/images/$statusImage" ?>" width="24" height="24" title="<?php echo ssoProvider::getStatusMessage($provider->status) ?>" align="absmiddle" />
					<?php } ?>
				</td>
				<td align="left"><b><?php echo $provider->siteName ?></b></td>
				<td align="left"><?php echo $provider->country ?></td>
				<td align="left"><a href="<?php echo $provider->siteUrl ?>"><?php echo ssoUtils::cutString($provider->siteUrl, 80) ?></a></td>
				<td align="center"><img src="<?php echo $checkOnlineLink ?>" /></td>
				<td align="left"><?php if ($provider->status == 'REGISTERED') echo ssoHTML::printSSOToLink($provider, $user, $idp) ?></td>
			</tr>
			<?php
		}
		?>
	</table>
	<hr size="1" />
	<p>
	<img src="<?php echo "$mosConfig_live_site/components/$option/images/green_jm.png" ?>" width="24" height="24" align="absmiddle" hspace="5" vspace="2" /> <?php echo _SSO_REGISTERED ?> <br />
	<img src="<?php echo "$mosConfig_live_site/components/$option/images/orange_jm.png" ?>" width="24" height="24" align="absmiddle" hspace="5" vspace="2" /> <?php echo _SSO_REG_REQ ?> <br />
	<img src="<?php echo "$mosConfig_live_site/components/$option/images/red_jm.png" ?>" width="24" height="24" align="absmiddle" hspace="5" vspace="2" /> <?php echo _SSO_REG_DENIED ?> <br />
	<img src="<?php echo "$mosConfig_live_site/components/$option/images/blank_jm.png" ?>" width="24" height="24" align="absmiddle" hspace="5" vspace="2" /> <?php echo _SSO_UNREGISTERED ?> <br />
	</p>
	<?php
	}
	
	function printSSOToLink($provider, $user, $idp) {
		global $option, $my, $mosConfig_live_site;
		if ( $my->id ) { // user is logged in
			?>
			<a href="<?php echo $idp->providerId ?>/index.php?option=<?php echo $option ?>&task=idplogin&sp=<?php echo urlencode($provider->providerId) ?>" title="<?php printf(_SSO_GO_TO_AND_LOGIN, $idp->siteName, $provider->siteName) ?>"><?php echo _SSO_GO_THERE ?></a>
			<?php
		} else { // user is not logged in
			?>
			<a href="<?php echo $provider->providerId ?>/index.php?option=<?php echo $option ?>&task=idplogin&sp=<?php echo urlencode($mosConfig_live_site) ?>" title="<?php printf(_SSO_LOGIN_USING, $provider->siteName) ?>"><?php echo _SSO_LOGIN_THERE ?></a>
			<?php
		}
	}
}
?>
