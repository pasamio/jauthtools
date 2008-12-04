<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Dec 4, 2008
 * 
 * @package package_name
 * @author Your Name <author@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Developer Name 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */
 
 
defined('_JEXEC') or die('Direct access to this location is not allowed.');

jimport('joomla.database.table');

/*
 * SSO Plugins
 */
class JTableSSOPlugin extends JTable {
	var $plugin_id = 0;
	var $filename = '';
	var $type = 'A';
	var $key = '';
	var $cache = '';
	
	function __construct(&$db) {
		parent::__construct('#__sso_plugins', 'plugin_id', $db);
	}
	
	function refresh() {
		$base_name = JPATH_PLUGINS.DS.'sso'.DS.$this->filename;
		if(!file_exists($base_name) && file_exists($base_name.'.xml')) {
			// we're using 1.5 style
			$base_name .= '.xml'; 
		} else if(file_exists($base_name) && file_exists($base_name . DS.$this->filename.'.xml')) {
			// 1.6 style
			$base_name .= DS.$this->filename.'.xml';
		} else return false; // skip this iteration
			
		$data = JAuthSSOAuthentication::getSSOXMLData($base_name);
		$this->type = $data['type'];
		$this->key = $data['key'];
		$this->cache = $data;
		return true;
	}
	
	function store( $updateNulls=false ) {
		$this->cache = serialize($this->cache);
		$res = parent::store($updateNulls);
		$this->cache = unserialize($this->cache);
		return $res;
	}
	
	function load($oid=null) {
		$res = parent::load($oid);
		$this->cache = unserialize($this->cache);
		return $res;
	}
}