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
 
 
jimport('joomla.application.component.model');

class SSOModelProvider extends JModel {
	var $_data = null;
	
	function getList() {
		if(!$this->_data) {
			$dbo =& JFactory::getDBO();
			$query  = 'SELECT p.name AS name, p.published AS published, sp.filename AS type, p.ordering AS ordering, p.id AS id, p.params AS params ';
			$query .= ' FROM #__sso_providers AS p LEFT JOIN #__sso_plugins AS sp ON p.plugin_id = sp.plugin_id';
			$dbo->setQuery($query);
			$this->_data = $dbo->loadObjectList();
		}
		return $this->_data;
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
		$row =& JTable::getInstance('ssoprovider');
		if (!$row->bind(JRequest::get('post'))) {
			JError::raiseError(500, $row->getError() );
		}
		if (!$row->check()) {
			JError::raiseError(500, $row->getError() );
		}
		if (!$row->store()) {
			JError::raiseError(500, $row->getError() );
		}
		return true;
    }
    
    function delete($cid) {
    	if(!is_array($cid)) {
    		$cid = Array($cid);
    	}
    	$dbo =& JFactory::getDBO();
    	$query = 'DELETE FROM #__sso_providers WHERE id IN ('. implode(',', $cid) .')';
    	$dbo->setQuery($query);
    	return $dbo->Query();
     }
}