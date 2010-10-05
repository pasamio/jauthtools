<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Nov 27, 2008
 * 
 * @package package_name
 * @author Sam Moffatt <pasamio@gmail.com>
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2009 Sam Moffatt 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.view');

 
class TokenLoginViewToken extends JView {

	function display($tpl = null) {
		$model =& $this->getModel();
		$logintoken = JRequest::getVar('token', '');
		$token = $model->getData($logintoken);
		$loginurl = $token->generateLoginURL();		
		$this->assignRef('loginurl',$loginurl);
		$this->assignRef('data', $token);
		parent::display($tpl);
	}
}
