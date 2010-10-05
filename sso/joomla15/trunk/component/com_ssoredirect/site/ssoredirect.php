<?php
defined('_JEXEC') or die('nachos libre!');
$params =& JComponentHelper::getParams('com_ssoredirect');
$landingpage = $params->get('landingpage', 'index.php');
$autocreate = $params->get('autocreate', false);
$user =& JFactory::getUser();
$oldid = $user->id;
jimport('jauthtools.sso');
$sso = new JAuthSSOAuthentication();
$sso->doSSOAuth($autocreate);
if($oldid != $user->id) {
       jimport('jauthtools.token');
       $dbo =& JFactory::getDBO();
       $token = new JAuthToolsToken($dbo);
       $token->set('username', $user->username);
       $token->set('expiry', time() + 3600); // now + 1 hr (60 * 60)
       $token->set('logins', 1);
       $token->set('landingpage', $landingpage);
       $token->store();
       $session =& JFactory::getSession();
       $session->destroy();
       $app =& JFactory::getApplication();
       $app->redirect($token->generateLoginUrl());
} else {
       $app =& JFactory::getApplication();
       $app->redirect('index.php', JText::_('Invalid SSO Request'));
}
