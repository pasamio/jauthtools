<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Dec 8, 2008
 * 
 * @package package_name
 * @author Your Name <author@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Developer Name 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */
 
jimport('joomla.application.component.models');

 
class ssomanagermodelPlugin extends JModel {
	var $_data = null;
	
	function getList() {
		if(!$this->_data) {
			$dbo =& JFactory::getDBO();
			$query  = 'SELECT p.name AS name, p.published AS published, sp.type AS type, p.ordering AS ordering, p.id AS id, p.params AS params ';
			$query .= ' FROM #__plugins AS p LEFT JOIN #__sso_plugins AS sp on sp.plugin_id = p.id';
			$mode = $this->getMode();
			if($mode) {
				$query .= ' WHERE folder = "'. $mode .'"';
			}
			$dbo->setQuery($query);
			$this->_data = $dbo->loadObjectList();
		}
		return $this->_data;
	}
	
	function getItem($index) {
		// TODO: Fill with plugin load code
	}
		
	// TODO: Change the way that this behaves
    function getMode() {
    	static $mode = null;
    	if($mode === null) {
    		$mode = JRequest::getVar('mode','');
    	}
    	return $mode;
    }

    function store() {
		$db   =& JFactory::getDBO();
		$row  =& JTable::getInstance('plugin');
		$mode = $this->getMode();

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
		$row->reorder( 'folder = '.$db->Quote($row->folder).' AND ordering > -10000 AND ordering < 10000' );
		return true;
    }
    
    function delete() {
    	JError::raiseError(500, 'Plugins cannot be deleted through this interface');
    }
}