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
	var $_data; 
	
	function getList() {
		$dbo =& JFactory::getDBO();
		
		$query  = 'SELECT p.name AS name, p.published AS published, sp.filename AS type, p.ordering AS ordering, p.id AS id ';
		switch($this->_mode) {
			case 'A':
			case 'C':
			case 'BG':
				$query .= ' FROM #__sso_plugins AS sp LEFT JOIN #__plugins AS p on sp.plugin_id = p.id';
				break;
			case 'B':
				$query .= ' FROM #__sso_providers AS p LEFT JOIN #__sso_plugins AS sp ON p.plugin_id = sp.plugin_id';
				break;
		}
		
		if($this->_mode) {
			if($this->_mode == 'BG') {
				// BG is a special type of 'B' for the global list
				$query .= ' WHERE sp.type = "B"';				
			} else {
				$query .= ' WHERE sp.type = "'. $this->_mode .'"';
			}
		}
		
		
		$dbo->setQuery($query);
		
		$res = $dbo->loadObjectList();
		return $res;
	}
	
	function setMode($mode) {
		$this->_mode = $mode;
	}
	
	function getMode() {
		return $this->_mode;
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
		$result = Array();
		$retval['success'] = 0;
		$retval['failure'] = 0;
		foreach($results as $result) {
			$table =& JTable::getInstance('ssoplugin');
			$table->load($result);
			$table->refresh();
			if($table->store()) {
				++$retval['success'];
			} else {
				++$retval['failure'];
			}
		}
		return $retval;
	}
	
	function getData() {
		return $this->_data;
	}
	
	function loadData($index) {
		if($index) {
			$dbo =& JFactory::getDBO();
			$query  = 'SELECT p.name AS name, p.published AS published, sp.filename AS type, p.ordering AS ordering, p.id AS id, p.params AS params ';
			switch($this->_mode) {
				case 'A':
				case 'C':
				case 'BG':
					$query .= ' FROM #__sso_plugins AS sp LEFT JOIN #__plugins AS p on sp.plugin_id = p.id';
					break;
				case 'B':
					$query .= ' FROM #__sso_providers AS p LEFT JOIN #__sso_plugins AS sp ON p.plugin_id = sp.plugin_id';
					break;
			}
			$query .= ' WHERE sp.type = "'. $this->_mode .'" AND p.id = '. $index;
			$dbo->setQuery($query);
			$this->_data = $dbo->loadObject();
		} else {
			// Fake this for new object
			$this->_data = new stdClass();
			$type = JRequest::getVar('type','');
			$this->_data->name = 'New '. $type .' plugin';
			$this->_data->published = 0;
			$this->_data->type = $type;
			$this->_data->ordering = 999;
			$this->_data->id = 0;
			$this->_data->params = '';
		}
		return $this->_data;
	}
	
	function store() {
		// The mode should have been set by the controller, so all we need to do 
		// is pull the data out of the request
		switch($this->_mode) {
			case 'A':
			case 'C':
			case 'BG':
				// type A or C plugins use the #__plugins table to store data
				// type BG is the type B global params
				// TODO: type B plugin non-instance params are global, so need to set this appropriately
				$row =& JTable::getInstance('plugin');
				$id = JRequest::getVar('cid',0);
				$row->load($id);
				
				// Check for request forgeries
				JRequest::checkToken() or jexit( 'Invalid Token' );
		
				$db   =& JFactory::getDBO();
				$row  =& JTable::getInstance('plugin');
		
				$client = JRequest::getWord( 'filter_client', 'site' );
		
				if (!$row->bind(JRequest::get('post'))) {
					JError::raiseError(500, $row->getError() );
				}
				if (!$row->check()) {
					JError::raiseError(500, $row->getError() );
				}
				if (!$row->store()) {
					JError::raiseError(500, $row->getError() );
				}
				$row->checkin();

				if ($client == 'admin') {
					$where = "client_id=1";
				} else {
					$where = "client_id=0";
				}

				$row->reorder( 'folder = '.$db->Quote($row->folder).' AND ordering > -10000 AND ordering < 10000 AND ( '.$where.' )' );
				return true;
				break;
			case 'B':
				// type B plugins are instance plugins
				
				break;
		}
	}
}