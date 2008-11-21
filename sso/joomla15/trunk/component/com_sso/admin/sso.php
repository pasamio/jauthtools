<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Nov 21, 2008
 * 
 * @package package_name
 * @author Your Name <author@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Developer Name 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */
 
 
// no direct access
defined('_JEXEC') or die('No direct access allowed ;)');

// Require the base controller
require_once( JPATH_COMPONENT.DS.'controller.php' );

//require_once (JApplicationHelper::getPath('admin_html'));
require_once (JApplicationHelper::getPath('class'));

// Require specific controller if requested
if($controller = JRequest::getWord('controller')) {
    $path = JPATH_COMPONENT.DS.'controllers'.DS.$controller.'.php';
    if (file_exists($path)) {
        require_once $path;
    } else {
        $controller = '';
    }
}

// Create the controller
$classname    = 'SSOController'.$controller;
$controller   = new $classname( );

// Perform the Request task
$controller->execute( JRequest::getVar( 'task' ) );

// Redirect if set by the controller
$controller->redirect();

