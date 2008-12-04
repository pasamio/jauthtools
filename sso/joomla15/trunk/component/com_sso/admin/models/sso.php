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
 
 
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport( 'joomla.application.component.model' );
jimport( 'joomla.filesystem.file');
jimport( 'joomla.filesystem.folder');
jimport( 'jauthtools.sso' );

class SSOModelSSO extends JModel {
	var $_mode = 'A'; 
	
	function getList() {
		$dbo =& JFactory::getDBO();
		
		$query  = 'SELECT p.name AS name, p.published AS published, sp.filename AS type, p.ordering AS ordering, p.id AS id ';
		switch($this->_mode) {
			case 'A':
			case 'C':
				$query .= ' FROM #__sso_plugins AS sp LEFT JOIN #__plugins AS p on sp.plugin_id = p.id';
				break;
			case 'B':
				$query .= ' FROM #__sso_plugins AS sp LEFT JOIN #__sso_providers AS p ON p.plugin_id = sp.plugin_id';
				break;
		}
		$query .= ' WHERE sp.type = "'. $this->_mode .'"';
		
		
		$dbo->setQuery($query);
		
		$res = $dbo->loadObjectList();
		echo $dbo->getquery();echo '<br />';
		print_r($res);
		echo '<hr />';
		return $res;
	}
	
	function setMode($mode) {
		$this->_mode = $mode;
	}
	
	function refreshPlugins() {
		$dbo =& JFactory::getDBO();	
		$query = 'INSERT INTO #__sso_plugins (plugin_id,filename) SELECT `id`,`element` FROM #__plugins WHERE `id` NOT IN (SELECT `plugin_id` FROM #__sso_plugins) AND `folder` = "sso"';
		$dbo->setQuery($query);
		$results = $dbo->Query();
		$query = 'DELETE FROM #__sso_plugins WHERE plugin_id NOT IN (SELECT id FROM #__plugins WHERE folder = "sso")';
		$dbo->setQuery($query);
		$dbo->Query();
		$query = 'SELECT plugin_id FROM #__sso_plugins';
		$dbo->setQuery($query);
		$results = $dbo->loadResultArray();
		foreach($results as $result) {
			$table =& JTable::getInstance('ssoplugin');
			$table->load($result);
			$table->refresh();
			$table->store();
		}
	}
}