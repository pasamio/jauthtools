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
		$model =& $this->getModel();
		$this->assign('mode', $model->getMode());
		$data = $model->getData();
		$base_name = JApplicationHelper::getPath('plg_xml','sso'.DS.$data->type);
		if($base_name) {
			$params = new JParameter($data->params, $base_name);
			$params = $params->render();
		} else {
			$params = 'No XML file found!';
		}
		$this->assignRef('params', $params);
		$this->assignRef('data', $data); 
		parent::display($tpl);
	}
}