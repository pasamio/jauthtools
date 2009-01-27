<?php
/**
 * @version 	$Id: mod_sso.php,v V1.1 4766 bytes, 2007-06-07 12:51:40 cero Exp $
 * @package 	SSO
 * @author	Sam Moffatt <sam.moffatt@gmail.com>
 * @copyright 	(C) 2009 Sam Moffatt
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

jimport('jauthtools.sso');

 
jimport('jauthtools.sso');
jimport('jauthtools.usersource');

if(!function_exists('getLinkFromItemID')) {
	function getLinkFromItemID($itemid) {
		$menu =& JSite::getMenu();
		$tmp = $menu->getItem($itemid);
		switch ($tmp->type)
		{
			case 'url' :
				if ((strpos($tmp->link, 'index.php?') === 0) && (strpos($tmp->link, 'Itemid=') === false)) {
					$url = $tmp->link.'&amp;Itemid='.$tmp->id;
				} else {
					$url = $tmp->link;
				}
				break;

			default :
				$router = JSite::getRouter();
				$url = $router->getMode() == JROUTER_MODE_SEF ? 'index.php?Itemid='.$tmp->id : $tmp->link.'&Itemid='.$tmp->id;
				break;
		}
		$url = str_replace('&amp;','&', $url); // get rid of any ampersands
		$url = JRoute::_($url);
		$url = str_replace('&amp;','&', $url); // get rid of any ampersands again
		return $url;
	}
}

$ip_blacklist = $params->get('ip_blacklist','');
$list = explode("\n", $ip_blacklist);
if(in_array($_SERVER['REMOTE_ADDR'],$list)) {
	return false;
}	


$user =& JFactory::getUser();
if(!$params->get('override',0)) {
	if($user->id) return false;
}

$before = $user->id;
$sso = new JAuthSSOAuthentication();
$sso->doSSOAuth($params->getValue('autocreate',false));
if($before != $user->id) { // user id changed
	$app =& JFactory::getApplication();
	$uri =& JFactory::getURI();
	$nextHop = $params->get('nexthop',false); 
	if(JRequest::getMethod() == 'GET') { // get methods we can proxy easily without losing info
		if($nextHop) { // redirect to the next hop location
			$app->redirect(getLinkFromItemID($nextHop));
		} else { // redirect back to the same page
			$app->redirect($uri->toString());
		}
	} else { // might be a POST or other request so don't redirect
		echo '<p>'. JText::_('SSO Login will activate next request') .'</p>';
	}
} else {


	$plugin = $params->get('plugin', '');
	
	if ($plugin) {
		$plugin = JPluginHelper :: getPlugin('sso', $plugin);
		if (empty ($plugin)) {
			JError :: raiseError(500, 'Invalid plugin');
			return false;
		}
	
		$className = 'plg' . $plugin->type . $plugin->name;
		if (class_exists($className)) {
			$plugin = new $className ($sso, (array) $plugin);
		} else {
			JError :: raiseWarning(500, 'Could not load ' . $className);
			return false;
		}
	
		// Output the form
		if(method_exists($plugin,'getForm')) {
			echo $plugin->getForm();
		} else if (method_exists($plugin, 'getSPLink')) {
			JAuthSSOAuthentication::getProvider($name);
			echo '<ul>';
			foreach($providers as $provider) {
				echo '<li><a href="'.$plugin->getSPLink($provider) .'">'. $provider->name .'</a>';
			} 
			echo '</ul>';
		}
	} else {
		$plugins = JPluginHelper :: getPlugin('sso');
		$forms = Array();
		$links = Array();
		
		foreach ($plugins as $plugin) {
			$className = 'plg' . $plugin->type . $plugin->name;
			$name = $plugin->name;
			if (class_exists($className)) {
				$plugin = new $className ($sso, (array) $plugin);
			} else {
				JError :: raiseWarning(50, 'Could not load ' . $className);
				continue; // skip this plugins!
			}
	
			// Output the form if the function is available
			if (method_exists($plugin, 'getForm')) {
				$forms[] = $plugin->getForm();
			}
			if (method_exists($plugin, 'getSPLink')) {
				$providers = JAuthSSOAuthentication::getProvider($name);
				foreach($providers as $provider) {
					$links[] = '<a href="'.$plugin->getSPLink($provider) .'">'. $provider->name .'</a>';
				}
			}		
		}
		if($params->get('show_links',0)) {
			if($params->get('show_titles',0)) echo '<h1>'. JText::_('Links') .'</h1><br />';
			// this is a _very_ bad way of doing this; TODO: don't do this
			echo '<ul style="padding-left:20px;">';
			foreach($links as $link) {
				echo '<li>'. $link;
			}
			echo '</ul>';
		}
		
		if($params->get('show_forms',0)) {
			if($params->get('show_titles',0)) echo '<h1>'. JText::_('Forms') .'</h1><br />';
			// this is a _very_ bad way of doing this; TODO: don't do this
			echo '<ul style="padding-left:20px;">';
			foreach($forms as $form) {
				echo '<li>'. $form;
			}
			echo '</ul>';	
		}
	}
}