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
 * @author Sam Moffatt <pasamio@gmail.com>
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2009 Sam Moffatt 
 * @version SVN: $Id:$
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
class TokenLoginController extends JController
{
    /**
     * Method to display the view
     *
     * @access    public
     */
    function display()
    {
    	JToolbarHelper::title(JText::_('Token Login'),'user.png');
    	JToolBarHelper::customX('edit','new.png', 'new_f2.png','Issue Token',false);
    	JToolBarHelper::trash('revoke','Revoke Token',false);
    	$document =& JFactory::getDocument();

		$viewName	= JRequest::getCmd( 'view', '' );
		$viewType	= $document->getType();

		$view = &$this->getView($viewName, $viewType);

		$model	= &$this->getModel( );
		if (!JError::isError( $model )) {
			$view->setModel( $model, true );
		}

		$view->assign('error', $this->getError());
        parent::display();
    }
    
    function edit() {
    	JToolbarHelper::title(JText::_('Token Login') .' - '. JText::_('Edit'),'user.png');
    	JToolbarHelper::save();
    	JToolBarHelper::cancel();
    	$model =& $this->getModel();
    	$document =& JFactory::getDocument();
    	$viewType	= $document->getType();
    	$view =& $this->getView('token', $viewType);
    	$view->setModel($model, true);
    	$view->display();
    }
    
    function save() {
    	$model =& $this->getModel();
    	if($model->save()) {
    		$msg = JText::_('Success');
    	} else {
    		$msg = JText::_('Failed');
    	}
    	$this->setRedirect('index.php?option=com_tokenlogin', $msg);
    }
    
    function revoke() {
    	$model =& $this->getModel();
    	$tokens = $model->revoke();
    	$msg = JText::sprintf('Revoked %d tokens', $tokens);
    	$this->setRedirect('index.php?option=com_tokenlogin', $msg);
    }
}
