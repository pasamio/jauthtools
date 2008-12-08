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
    	JToolbarHelper::title('SSO Manager');
    	$model =& $this->getModel();
    	$mode = $this->getMode();
    	if(in_array($mode, Array('A','B','C'))) {
    		switch($this->getTask()) {
    			case 'new':
    				// do funky stuff here
    				$view =& $this->getView('picker','html');
    				$view->setModel($model, true);
    				$view->display();
    				break;
    			default:
    				// flick off to the listView
    				$this->listView($mode);
    				break;
    		}
    	} else {
	    	$this->configuration();
    	}
    }
    
    function refresh() {
    	$model =& $this->getModel();
    	$mode = $this->getMode();
    	$count = $model->refreshPlugins();
    	$this->setRedirect('index.php?option=com_sso&task=configuration',JText::sprintf('Refreshed %d plugins successfully and failed to update %d plugins', $count['success'], $count['failure']));
    }
    
    function typea() {
    	$this->listView('A');
    }
    
    function typeb() {
    	$this->listView('B');
    }

    function typec() {
    	$this->listView('C');
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
    	$model =& $this->getModel();
    	$mode = $this->getMode();
    	$id = JRequest::getVar('id',0);
    	$model->setMode($mode);
    	$model->loadData($id);
    	$view =& $this->getView('edit', 'html');
    	$view->setModel( $model, true);
    	$view->display();
    }
    
    function save() {
    	$mode = JRequest::getVar('mode','A');
    	$model =& $this->getModel();
    	$model->setMode($mode);
    	if($model->store()) {
    		$this->setRedirect('index.php?option=com_sso&mode='. $mode, 'Saved');
    	} else {
    		die('failure');
    		$this->setRedirect('index.php?option=com_sso&mode='. $mode, 'Store failed');
    	}
    }
    
    function getMode() {
    	static $mode = null;
    	if($mode === null) {
    		$mode = JRequest::getVar('mode','');
    	}
    	return $mode;
    }
    
    function configuration() {
    	JHtml::stylesheet('toolbar.css', 'administrator/components/com_sso/media/css/');
    	JToolbarHelper::title(JText::_('SSO Manager'). ' - '.  JText::_('Configuration'));
    	JToolBarHelper::custom( 'refresh', 'refresh', 'refresh','Refresh Plugin List',false,false);
    	parent::display();
    }
}
