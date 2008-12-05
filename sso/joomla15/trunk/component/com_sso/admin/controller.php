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
    	$model =& $this->getModel();
    	$model->setMode('B');
    	$model->getList();
    	$model->setMode('A');
    	$model->getList();
    	$model->refreshPlugins();
        //parent::display();
    }
    
    function typea() {
    	JToolbarHelper::title('SSO - Type A plugins');
    	JToolbarHelper::editList('edit','Edit');
    	$this->listView('A');
    }
    
    function typeb() {
    	JToolbarHelper::title('SSO - Type B plugins');
    	$this->listView('B');
    }

    function typec() {
    	JToolbarHelper::title('SSO - Type C plugins');
    	
    	$this->listView('C');
    }
    
    function listView($mode='A') {
    	$model =& $this->getModel();
    	$model->setMode($mode);
    	$view =& $this->getView('list','html');
    	$view->setModel( $model, true);
    	$view->display();
    }
    
    function edit() {
    	$model =& $this->getModel();
    	$mode = JRequest::getVar('mode','A');
    	$id = JRequest::getVar('id','');
    	$model->setMode($mode);
    	$model->loadData($id);
    	$view =& $this->getView('edit', 'html');
    	$view->setModel( $model, true);
    	$view->display();
    }
    
    function save() {
    	?>save<?php
    }
}

?>
