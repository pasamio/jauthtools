<?php
/**
 * SSO Helper
 * 
 * Logs the user in automatically 
 * 
 * PHP4/5
 *  
 * Created on Oct 8, 2008
 * 
 * @package JAuthTools
 * @author Sam Moffatt <sam.moffatt@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Sam Moffatt 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/jauthtools   JoomlaCode Project: Joomla! Authentication Tools    
 */
 
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
}