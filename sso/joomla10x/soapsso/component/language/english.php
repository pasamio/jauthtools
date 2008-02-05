<?php
/**
* @version 	$Id: english.php,v V1.1 3579 bytes, 2007-06-08 09:51:30 cero Exp $
* @package 	SSO
* @subpackage 	english.php
* @author	<Tomo.Cerovsek.fgg.uni-lj.si> <Damjan.Murn.uni-lj.si>
* @developers	Tomo Cerovsek, Damjan Murn
* @copyright 	(C) 2007 SSO Team, UL FGG
* @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
* SSO was initiated during the EU CONNIE project
*/

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

@define('_SSO_COMP_NAME','Joomla! Single Sign-On');
@define('_SSO_LOGIN','Login');
@define('_SSO_LOGOUT','Logout');
@define('_SSO_MAKE_SSO','Go');
@define('_SSO_ERROR','Single Sign-On error:');
@define('_SSO_FAILURE_REQUEST','Invalid request.');
@define('_SSO_FAILURE_RESPONSE','Invalid response from the Identity Provider %s.');
@define('_SSO_MAIL_ALREADY_REGISTERED1', "Your email '%s' is already registered at %s. You have an account with the username '%s' at the Identity Provider '%s'. Please login with that account at your Identity Provider. <a href='%s'>Login.</a>");
@define('_SSO_MAIL_ALREADY_REGISTERED2',"Your email '%s' is already registered at %s. You have a local account with the username '%s'. Please login with that account.");
@define('_SSO_FAILURE_IDP_NOT_REGISTERED',"Your Identity Provider '%s' is not registered at %s.");
@define('_SSO_FAILURE_SP_NOT_REGISTERED',"The Service Provider '%s' is not registered at %s.");
@define('_SSO_DATABASE_ERROR','Database error.');
@define('_SSO_REGISTRATION_NOT_ALLOWED','Sorry. Registration of new users is not allowed. Please contact the site administrator.');
@define('_SSO_LOGGED_IN_LOCAL','You are logged in as %s.');
@define('_SSO_LOGGED_IN_REMOTE','You are logged in as %s from %s.');
@define('_SSO_YOU_CAN_SSO_TO','You can SSO to:');
@define('_SSO_PLEASE_SELECT','Please select');
@define('_SSO_SELECT_YOUR_IDP','If you have an account at one of the following portals you can login there:');
@define('_SSO_NO_IDP_REGISTERED','No providers are registered.');
@define('_SSO_NO_SP_REGISTERED','No providers are registered.');
@define('_SSO_FAILURE_LOGIN_FAILED',"Login failed for user '%s'.");
@define('_SSO_FAILURE_CREATE_ACCOUNT',"Failed to create an account for the user '%s'.");
@define('_SSO_FAILURE_SESSION_EXPIRED','Your session has expired.');

// module
@define('_SSO_FAILURE_IDP_NOT_REGISTERED_SHORT',"Your <span title='%s'>Identity Provider</span> is not registered at %s.");

// login page
@define('_SSO_LOGIN_DESCRIPTION','Please login');
@define('_SSO_USERNAME','Username');
@define('_SSO_PASSWORD','Password');
@define('_SSO_REMEMBER_ME','Remember me');
@define('_SSO_PASSWORD_REMINDER','Password Reminder');
@define('_SSO_NO_ACCOUNT_YET','No account yet?');
@define('_SSO_CREATE_ONE','Create one');

// frontend: providers list
@define('_SSO_PROVIDERS','Providers');
@define('_SSO_SITE_NAME','Site Name');
@define('_SSO_STATUS','Status');
@define('_SSO_COUNTRY','Country');
@define('_SSO_URL_','URL');
@define('_SSO_ONLINE','Online');
@define('_SSO_SSO','SSO');
@define('_SSO_NO_PROVIDERS_REGISTERED','No providers are registered.');
@define('_SSO_LOGIN_THERE','Login there');
@define('_SSO_GO_THERE','Login there');
@define('_SSO_GO_TO_AND_LOGIN','Use account at %s to login into the site %s');
@define('_SSO_LOGIN_USING','Login using an account you have at %s');
@define('_SSO_REGISTERED','Registered');
@define('_SSO_REG_REQ','Registration pending');
@define('_SSO_REG_DENIED','Registration denied');
@define('_SSO_UNREGISTERED','Unregistered');
?>