<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Dec 5, 2008
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
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.view');
 
class SSOViewEdit extends JView {
	
	function display($tpl=null) {
		$db		=& JFactory::getDBO();
		$user 	=& JFactory::getUser();

		$client = JRequest::getWord( 'client', 'site' );
		$cid 	= JRequest::getVar( 'id', array(0), '', 'array' );
		$plugin_id = JRequest::getVar('plugin_id',0);
		JArrayHelper::toInteger($cid, array(0));

		$lists 	= array(); 
		$model =& $this->getModel();
		
		if($model->getMode() != 'B') {
			// Type A, C, and B Global (BG) are stored in plugin
			$row 	=& JTable::getInstance('plugin');
			// load the row from the db table
			$row->load( $cid[0] );
	
			// fail if checked out not by 'me'
	
			if ($row->isCheckedOut( $user->get('id') ))
			{
				$msg = JText::sprintf( 'DESCBEINGEDITTED', JText::_( 'The plugin' ), $row->title );
				$this->setRedirect( 'index.php?option='. $option .'&client='. $client, $msg, 'error' );
				return false;
			}
			
			if ($client == 'admin') {
				$where = "client_id='1'";
			} else {
				$where = "client_id='0'";
			}
	
			if ($cid[0])
			{
				$row->checkout( $user->get('id') );
	
				if ( $row->ordering > -10000 && $row->ordering < 10000 )
				{
					// build the html select list for ordering
					$query = 'SELECT ordering AS value, name AS text'
						. ' FROM #__plugins'
						. ' WHERE folder = '.$db->Quote($row->folder)
						. ' AND published > 0'
						. ' AND '. $where
						. ' AND ordering > -10000'
						. ' AND ordering < 10000'
						. ' ORDER BY ordering'
					;
					$order = JHTML::_('list.genericordering',  $query );
					$lists['ordering'] = JHTML::_('select.genericlist',   $order, 'ordering', 'class="inputbox" size="1"', 'value', 'text', intval( $row->ordering ) );
				} else {
					$lists['ordering'] = '<input type="hidden" name="ordering" value="'. $row->ordering .'" />'. JText::_( 'This plugin cannot be reordered' );
				}
	
				$lang =& JFactory::getLanguage();
				$lang->load( 'plg_' . trim( $row->folder ) . '_' . trim( $row->element ), JPATH_ADMINISTRATOR );
	
				$data = JApplicationHelper::parseXMLInstallFile(JApplicationHelper::getPath('plg_xml',$row->folder . DS . $row->element));
	
				$row->description = $data['description'];
	
			} else {
				$row->ordering 		= 999;
				$row->published 	= 1;
				$row->description 	= '';
				$lists['ordering'] = '<input type="hidden" name="ordering" value="'. $row->ordering .'" />'. JText::_( 'This plugin cannot be reordered' );
			}
	

		} else if($plugin_id || $cid[0]) {
			// Type B plugins are...special
			$row =& JTable::getInstance('ssoprovider');
			if($cid[0]) {
				// existing type B plugin instance
				
				$row->load($cid[0]);
				
				if ($row->isCheckedOut( $user->get('id') ))
				{
					$msg = JText::sprintf( 'DESCBEINGEDITTED', JText::_( 'The SSO plugin' ), $row->title );
					$this->setRedirect( 'index.php?option=com_sso&mode=B', $msg, 'error' );
					return false;
				}
				
				if ( $row->ordering > -10000 && $row->ordering < 10000 )
				{
					// build the html select list for ordering
					$query = 'SELECT ordering AS value, name AS text'
						. ' FROM #__sso_providers'
						. ' WHERE '
						. ' AND published > 0'
						. ' AND '. $where
						. ' AND ordering > -10000'
						. ' AND ordering < 10000'
						. ' ORDER BY ordering'
					;
					$order = JHTML::_('list.genericordering',  $query );
					$lists['ordering'] = JHTML::_('select.genericlist',   $order, 'ordering', 'class="inputbox" size="1"', 'value', 'text', intval( $row->ordering ) );
				} else {
					$lists['ordering'] = '<input type="hidden" name="ordering" value="'. $row->ordering .'" />'. JText::_( 'This plugin cannot be reordered' );
				}
								
				$data = JApplicationHelper::parseXMLInstallFile(JApplicationHelper::getPath('plg_xml','sso'.DS.$row->type));
	
				$row->description = $data['description'];
			} else {
				// new type B plugin instance
				$plugin =& JTable::getInstance('plugin');
				$plugin->load($plugin_id);
				$data = JApplicationHelper::parseXMLInstallFile(JApplicationHelper::getPath('plg_xml','sso'.DS.$plugin->element));
				$row->plugin_id = $plugin_id;
				$row->name = $plugin->name;
				$row->description = $data['description'];
				$lists['ordering'] = '<input type="hidden" name="ordering" value="'. $row->ordering .'" />'. JText::_( 'This plugin cannot be reordered' );				
			}
			$lang =& JFactory::getLanguage();
			// Core or 1.5
			$lang->load( 'plg_sso_' . trim( $row->element ), JPATH_ADMINISTRATOR );
		} else {
			$this->setRediret('index.php?option=com_sso&mode=B','No plugin ID or provider ID provided');
			return false;
		}
		
		// Common stuff
		$lists['published'] = JHTML::_('select.booleanlist',  'published', 'class="inputbox"', $row->published );
		
		$this->assign('mode', $model->getMode());
		$data = $model->getData();
		// get params definitions
		$params = new JParameter($data->params, JApplicationHelper::getPath('plg_xml','sso'.DS.$data->type));
		$this->assignRef('lists',		$lists);
		$this->assignRef('plugin',		$row);
		$this->assignRef('params', $params);
		$this->assignRef('data', $data); 
		parent::display($tpl);
	}
}