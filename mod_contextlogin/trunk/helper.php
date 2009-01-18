<?php
/**
* @version		$Id: helper.php 11299 2008-11-22 01:40:44Z ian $
* @package		Joomla
* @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

class modContextLoginHelper
{
	function getReturnURL($params, $type)
	{
		if($itemid =  $params->get($type))
		{
			$menu =& JSite::getMenu();
			$item = $menu->getItem($itemid);
			$url = JRoute::_($item->link.'&Itemid='.$itemid, false);
		}
		else
		{
			$url = JURI::base(true);
		}

		return base64_encode($url);
	}

	function getType()
	{
		$user = & JFactory::getUser();
		return (!$user->get('guest')) ? 'logout' : 'login';
	}
	
	function getContexts($params) {
		$contexts = $params->get('contexts');
		$result = '';
		if(!empty($contexts)) {
			$contexts = explode("\n", $contexts);
			$cc = count($contexts);
			for($i = 0; $i < $cc; $i++) {
				$tmp = new stdClass();
				$tmp->text = $contexts[$i];
				$tmp->value = $i;
				$contexts[$i] = clone($tmp);
			}
			if(!$params->get('require_context',0)) {
				$tmp = new stdClass();
				$tmp->text = JText::_('Select context');
				$tmp->value = -1;
				array_unshift($contexts, $tmp);
			}
			$result = JHTML::_('select.genericlist', $contexts, 'context', null, 'value', 'text', $params->get('default_context', -1));
		}
		return $result;
	}
}
