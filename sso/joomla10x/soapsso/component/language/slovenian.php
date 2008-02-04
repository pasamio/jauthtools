<?php
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

@define('_SSO_COMP_NAME','Mambo/Joomla Single Sign-On');
@define('_SSO_LOGIN','Prijava');
@define('_SSO_LOGOUT','Odjava');
@define('_SSO_MAKE_SSO','Pojdi');
@define('_SSO_ERROR','Napaka pri prijavi:');
@define('_SSO_FAILURE_REQUEST','Nepravilna zahteva.');
@define('_SSO_FAILURE_RESPONSE','Nepravilen odgovor od Identity Provider-ja %s.');
@define('_SSO_MAIL_ALREADY_REGISTERED1',"Vaš e-naslov '%s' je že registriran pri %s. Račun z uporabniškim imenom '%s' imate pri Identity Provider-ju '%s'. Prosimo prijavite se s tem uporabniškim imenom pri vašem Identity Provider-ju. <a href='%s'>Prijava.</a>");
@define('_SSO_MAIL_ALREADY_REGISTERED2',"Vaš e-naslov '%s' je že registriran pri %s. Na tem portalu imate lokalni račun z uporabniškim imenom '%s'. Prosimo prijavite se s tem uporabniškim imenom.");
@define('_SSO_FAILURE_IDP_NOT_REGISTERED',"Vaš Identity Provider '%s' ni registriran pri %s.");
@define('_SSO_FAILURE_SP_NOT_REGISTERED',"Service Provider '%s' ni registriran pri %s.");
@define('_SSO_DATABASE_ERROR','Napaka pri dostopu do podatkovne baze.');
@define('_SSO_REGISTRATION_NOT_ALLOWED','Žal registracija novih uporabnikov ni dovoljena. Prosimo kontaktirajte skrbnika portala.');
@define('_SSO_LOGGED_IN_LOCAL','Prijavljeni ste kot %s.');
@define('_SSO_LOGGED_IN_REMOTE','Prijavljeni ste kot %s iz %s.');
@define('_SSO_YOU_CAN_SSO_TO','Prijavite se lahko na:');
@define('_SSO_PLEASE_SELECT','Prosimo izberite');
@define('_SSO_SELECT_YOUR_IDP','Če imate račun pri enem od naslednjih portalov, se lahko prijavite tam:');
@define('_SSO_NO_IDP_REGISTERED','Noben ponudnik ni registriran.');
@define('_SSO_NO_SP_REGISTERED','Noben ponudnik ni registriran.');
@define('_SSO_FAILURE_LOGIN_FAILED',"Prijava uporabnika '%s' ni uspela.");
@define('_SSO_FAILURE_CREATE_ACCOUNT',"Ustvarjanje računa za uporabnika '%s' ni uspelo.");
@define('_SSO_FAILURE_SESSION_EXPIRED','Vaša seja je potekla.');

// module
@define('_SSO_FAILURE_IDP_NOT_REGISTERED_SHORT',"Vaš <span title='%s'>Identity Provider</span> ni registriran pri %s.");

// login page
@define('_SSO_LOGIN_DESCRIPTION','Prosimo prijavite se');
@define('_SSO_USERNAME','Uporabniško ime');
@define('_SSO_PASSWORD','Geslo');
@define('_SSO_REMEMBER_ME','Zapomni se me');
@define('_SSO_PASSWORD_REMINDER','Ste pozabili geslo?');
@define('_SSO_NO_ACCOUNT_YET','Še nimate uporabniškega računa?');
@define('_SSO_CREATE_ONE','Ustvarite novega');

// frontend: providers list
@define('_SSO_PROVIDERS','Ponudniki');
@define('_SSO_STATUS','Status');
@define('_SSO_SITE_NAME','Ime portala');
@define('_SSO_COUNTRY','Država');
@define('_SSO_URL_','URL');
@define('_SSO_ONLINE','Online');
@define('_SSO_SSO','SSO');
@define('_SSO_NO_PROVIDERS_REGISTERED','Noben ponudnik ni registriran.');
@define('_SSO_LOGIN_THERE','Prijavi se tam');
@define('_SSO_GO_THERE','Prijavi me tja');
@define('_SSO_GO_TO_AND_LOGIN','Pojdi na %s is me prijavi kot %s iz %s');
@define('_SSO_LOGIN_USING','Prijavi me s pomočjo računa, ki ga imam pri %s');
@define('_SSO_REGISTERED','Registriran');
@define('_SSO_REG_REQ','Registracija čaka na potrditev');
@define('_SSO_REG_DENIED','Registracija zavrnjena');
@define('_SSO_UNREGISTERED','Neregistriran');
?>
