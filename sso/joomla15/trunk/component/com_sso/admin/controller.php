<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Sep 28, 2007
 * 
 * @package JLibMan
 * @author Your Name <author@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Sam Moffatt 
 * @version SVN: $Id:$
 * @see Project Documentation DM Number: #???????
 * @see Gaza Documentation: http://gaza.toowoomba.qld.gov.au
 * @see JoomlaCode Project: http://joomlacode.org/gf/project/
 */
 
 // no direct access

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.controller');

/**
 * JLibMan Component Controller
 *
 * @package    JLibMan
 */
class SSOController extends JController
{
    /**
     * Method to display the view
     *
     * @access    public
     */
    function display()
    {
    	switch($this->getTask()) {
    		case 'cancel':
    			$this->entries();
    			break;
    		default:
    			JToolbarHelper::title('SSO Manager');
	    		$this->configuration(); // default to configuration manager
    			break;
    	}
    }
    
    function entries() {
    	$mode = $this->getMode();
    	$this->setToolbarFromMode($mode);
    	$model =& $this->getModelFromMode($mode);
    	JToolbarHelper::title(JText::_('SSO Manager') . ' - '. JText::_($this->getNameFromMode($mode)));
    	$view = $this->getView('list','html');
    	$view->setModel($model, true);
    	$view->display();
    }
    
    function refresh() {
    	$model =& $this->getModel();
    	$mode = $this->getMode();
    	$count = $model->refreshPlugins();
    	$this->setRedirect('index.php?option=com_sso&task=configuration',JText::sprintf('Refreshed %d plugins successfully and failed to update %d plugins', $count['success'], $count['failure']));
    }
    
    function listView($mode='A') {
    	JRequest::setVar('task', 'type'.$mode);
    	JToolbarHelper::title(JText::sprintf('SSO - Type %s plugins', ucfirst($mode)));
    	if($mode == 'B') {
    		JToolbarHelper::addNew('new');
    		JToolbarHelper::editList('edit','Edit');
    		JToolbarHelper::deleteList('delete');
    	} else {
    		JToolbarHelper::editList('edit','Edit');
    	}
    	
    	$model =& $this->getModel();
    	$model->setMode($mode);
    	$view =& $this->getView('list','html');
    	$view->setModel( $model, true);
    	$view->display();
    }
    
    function edit() {
    	JToolBarHelper::title( JText::_( 'SSO' ) .': <small><small>[' .JText::_('Edit'). ']</small></small>', 'plugin.png' );
		JToolBarHelper::save();
		JToolBarHelper::cancel( 'cancel', 'Close' );
    	JRequest::setVar('hidemainmenu',1);
    	$mode = $this->getMode();
    	$model =& $this->getModelFromMode($mode);
    	$view =& $this->getViewFromMode($mode);
    	$view->setModel( $model, true);
    	$view->setLayout('form');
    	$view->display();
    }
    
    function save() {
    	// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );
    	$mode = $this->getMode();
    	$model =& $this->getModelFromMode($mode);
    	if(!$model) {
    		$this->setRedirect('index.php?option=com_sso', 'Save failed: Could not find  valid model','error');
    		return false;
    	}
    	
    	
    	if($model->store()) {
    		$msg = 'Saved';
    	} else {
    		$msg = 'Store failed';
    	}
    	if($mode == 'config') {
    		$link = 'index.php?option=com_sso';
    	} else {
    		$link = 'index.php?option=com_sso&task=entries&mode='. $mode;
    	}
    	$this->setRedirect($link, $msg);
    }
    
    function &getModelFromMode($mode) {
        switch($mode) {
    		case 'sso':
    		case 'identityprovider':
			case 'user':
    		case 'usersource':
    		case 'authentication':   
    		case 'config': // config is a special instance of the System - SSO plugin 			
    			$model =& $this->getModel('plugin');
    			break;
    		case 'serviceprovider':
    			$model =& $this->getModel('provider');
    			break;
    		default:
    			$model = false;
    			break;    			
    	}
    	return $model;
    }
    
    function &getViewFromMode($mode) {
    	switch($mode) {
    		case 'sso':
    		case 'identityprovider':
			case 'user':
    		case 'usersource':
    		case 'authentication':    			
    			$view =& $this->getView('plugin', 'html');
    			break;
    		case 'serviceprovider':
    			$view =& $this->getView('provider','html');
    			break;
    		default:
    			$view = false;
    	}
    	return $view;
    }
    
    function getNameFromMode($mode) {
    	switch($mode) {
    		case 'identityprovider':
    			return 'Identity Providers';
    			break;
    		case 'serviceprovider':
    			return 'Service Providers';
    			break;
    		case 'sso':
    			return 'SSO Plugins';
    			break;
    		case 'configuration':
    			return 'Configuration';
    		case 'authentication':
    			return 'Authentication Plugins';
    			break;
    		case 'user':
    			return 'User Plugins';
    			break;
    		case 'usersource':
    			return 'User Sources Plugins';
    			break;
    		default:
    			return 'Unknown';
    			break;
    	}	
    }
    
    function setToolbarFromMode($mode) {
    	switch($mode) {
    		case 'serviceprovider':
    			JToolbarHelper::addNew('new');
    			JToolbarHelper::editList('edit','Edit');
    			JToolbarHelper::deleteList('delete');
    			break;
    		case 'sso':
    		case 'identityprovider':
    		case 'user':
    		case 'usersource':
    		case 'authentication':
    			JToolbarHelper::editList('edit','Edit');
    			break;
    	}
    }
    
    function remove() {
    	$mode = JRequest::getVar('mode','');
    	$model =& $this->getModelFromMode($mode);
    	if(!$model) {
    		$this->setRedirect('index.php?option=com_sso', 'Failed to find model', 'error');
    	} else {
    		$cid 	= JRequest::getVar( 'cid', array(0), '', 'array' );
    		if($model->delete($cid)) {
    			$this->setRedirect('index.php?option=com_sso&task=entries&mode='. $mode, 'Delete successful!');
    		} else {
    			$this->setRedirect('index.php?option=com_sso&task=entries&mode='. $mode, 'Delete failed', 'error');
    		}
    	}
    	return true;
    }
    
    function getMode() {
    	static $mode = null;
    	if($mode === null) {
    		$mode = JRequest::getVar('mode','');
    		if($mode == 'sources' || $mode == 'source') { // replace this as j! gets confused
    			$mode = 'usersource';
    		}
    	}
    	return $mode;
    }
    
    function configuration() {
    	JHtml::stylesheet('toolbar.css', 'administrator/components/com_sso/media/css/');
    	JToolbarHelper::title(JText::_('SSO Manager'). ' - '.  JText::_('Configuration'));
    	JToolBarHelper::custom( 'refresh', 'refresh', 'refresh','Refresh Plugin List',false,false);
		JToolBarHelper::save();
		JToolBarHelper::cancel( 'cancel', 'Close' );
		$dbo =& JFactory::getDBO();
		$dbo->setQuery('SELECT id FROM #__plugins WHERE folder = "system" AND element = "sso"');
		$result = $dbo->loadResult();
		if($result) {
	    	JRequest::setVar('cid',$result);
	    	JRequest::setVar('mode','config');
	    	$model =& $this->getModel('plugin');
	    	$view =& $this->getView('plugin','html');
	    	$view->setModel( $model, true);
	    	$view->setLayout('form');
	    	$view->display();
		} else {
			parent::display();
		}
    }
}
