<?php

/**
 * @version 	$Id: mod_sso.php,v V1.1 4766 bytes, 2007-06-07 12:51:40 cero Exp $
 * @package 	SSO
 * @author	Tomo Cerovsek <Tomo.Cerovsek.fgg.uni-lj.si>
 * @author	Damjan Murn <Damjan.Murn.uni-lj.si>
 * @copyright 	(C) 2007 SSO Team, UL FGG
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * SSO was initiated during the EU CONNIE project
 */

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

$plugin = $params->get('plugin', '');
$host = new JAuthSSOAuthentication();

if ($plugin) {
	$plugin = JPluginHelper :: getPlugin('sso', $plugin);
	if (empty ($plugin)) {
		JError :: raiseError(500, 'Invalid plugin');
		return false;
	}

	$className = 'plg' . $plugin->type . $plugin->name;
	if (class_exists($className)) {
		$plugin = new $className ($host, (array) $plugin);
	} else {
		JError :: raiseWarning(500, 'Could not load ' . $className);
		return false;
	}

	// Output the form
	echo $plugin->getForm();
} else {
	$plugins = JPluginHelper :: getPlugin('sso');
	foreach ($plugins as $plugin) {
		$className = 'plg' . $plugin->type . $plugin->name;

		if (class_exists($className)) {
			$plugin = new $className ($host, (array) $plugin);
		} else {
			JError :: raiseWarning(50, 'Could not load ' . $className);
			continue; // skip this plugins!
		}

		// Output the form if the function is available
		if (method_exists($plugin, 'getForm')) {
			echo '<p>' . $plugin->getForm() . '</p>';
		}
	
			
	}
}