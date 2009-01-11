<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Jan 9, 2009
 * 
 * @package package_name
 * @author Your Name <author@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2009 Toowoomba Regional Council/Developer Name 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */
 
 
defined('_JEXEC') or die('this is model code!');

jimport('joomla.application.component.model');
jimport('jauthtools.sso');

class ssomanagermodelSSOmanager extends JModel {
	var $forms = Array();
	var $links = Array();
	var $host = null;
	
	function __construct($config = array()) {
		parent::__construct($config);
		// create a host for the plugins to attach to
		$this->host = new JAuthSSOAuthentication();
	}
	
	function getForms() {
		return $this->forms;
	}
	
	function getLinks() {
		return $this->links;
	}
	
	function prepareList() {
		$plugins = JPluginHelper::getPlugin('sso');
		foreach($plugins as $plugin) {
			$className = 'plg' . $plugin->type . $plugin->name;
			$name = $plugin->name;
			if (class_exists($className)) {
				$plugin = new $className ($this->host, (array)$plugin);
			} else {
				JError::raiseWarning(50, 'Could not load ' . $className);
				continue; // skip this plugins!
			}
			
			// Output the form if the function is available
			if(method_exists($plugin, 'getForm')) $this->forms[] = $plugin->getForm();
			if(method_exists($plugin, 'getSPLink')) {
				$providers = JAuthSSOAuthentication::getProvider($name);
				foreach($providers as $provider) {
					$this->links[] = '<a href="'.$plugin->getSPLink($provider) .'">'. $provider->name .'</a>';
				}
			}
		} 
	}
}