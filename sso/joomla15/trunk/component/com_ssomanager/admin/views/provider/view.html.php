<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Dec 9, 2008
 * 
 * @package package_name
 * @author Your Name <author@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Developer Name 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */
 
defined('_JEXEC') or die('Maaf!');

jimport('joomla.application.component.view');

class ssomanagerviewProvider extends JView {
	
	function display($tpl = null) {
		global $option;

		$db		=& JFactory::getDBO();
		$user 	=& JFactory::getUser();

		$mode = JRequest::getVar('mode','');
		$client = JRequest::getWord( 'client', 'site' );
		$cid 	= JRequest::getVar( 'cid', array(0), '', 'array' );
		JArrayHelper::toInteger($cid, array(0));

		$lists 	= array();
		$row 	=& JTable::getInstance('ssoprovider');
		
		// load the row from the db table
		$row->load( $cid[0] );

		// fail if checked out not by 'me'

		if ($row->isCheckedOut( $user->get('id') ))
		{
			$msg = JText::sprintf( 'DESCBEINGEDITTED', JText::_( 'The plugin' ), $row->title );
			$this->setRedirect( 'index.php?option='. $option .'&client='. $client, $msg, 'error' );
			return false;
		}
		
		
		if(!$cid[0]) {
			$plugin_id = JRequest::getInt('plugin_id',0);
			if($plugin_id) {
				$row->plugin_id = $plugin_id;
			} else {
				// REALLY REALLY REALLY shouldn't do this here
				$app =& JFactory::getApplication();
				$app->redirect('index.php?option=com_ssomanager&task=entries&mode='. $mode,'Invalid plugin ID specified','error');
				return false;
			}
		}
		
		$plugin =& $row->getPlugin();
		$lang =& JFactory::getLanguage();
		$lang->load( 'plg_sso_' . trim( $plugin->element ), JPATH_ADMINISTRATOR );

		$data = JApplicationHelper::parseXMLInstallFile(JApplicationHelper::getPath( 'plg_xml', 'sso'.DS.$plugin->element ));

		$plugin->description = $data['description'];		
		if ($cid[0])
		{
			$row->checkout( $user->get('id') );

			if ( $row->ordering > -10000 && $row->ordering < 10000 )
			{
				// build the html select list for ordering
				$query = 'SELECT ordering AS value, name AS text'
					. ' FROM #__sso_providers'
					. ' WHERE published > 0'
					. ' AND ordering > -10000'
					. ' AND ordering < 10000'
					. ' ORDER BY ordering'
				;
				$order = JHTML::_('list.genericordering',  $query );
				$lists['ordering'] = JHTML::_('select.genericlist',   $order, 'ordering', 'class="inputbox" size="1"', 'value', 'text', intval( $row->ordering ) );
			} else {
				$lists['ordering'] = '<input type="hidden" name="ordering" value="'. $row->ordering .'" />'. JText::_( 'This plugin cannot be reordered' );
			}

		} else {
			$row->folder 		= '';
			$row->ordering 		= 999;
			$row->published 	= 1;
			$row->description 	= '';
			// Fudge this, its technically wrong but it'll evaluate to zero and then normality will take over
			$lists['ordering'] = '<input type="hidden" name="ordering" value="'. $row->ordering .'" />'. JText::_( 'This plugin cannot be reordered' );
			
		}

		$lists['published'] = JHTML::_('select.booleanlist',  'published', 'class="inputbox"', $row->published );

		// get params definitions
		$params = new JParameter( $row->params, JApplicationHelper::getPath( 'plg_xml', 'sso'.DS.$plugin->element ), 'plugin' );
		$this->assignRef('lists',		$lists);
		$this->assignRef('provider', $row);
		$this->assignRef('plugin',		$plugin);
		$this->assignRef('params',		$params);
		$this->assignRef('mode', $mode);
		
		parent::display($tpl);
	}
}
