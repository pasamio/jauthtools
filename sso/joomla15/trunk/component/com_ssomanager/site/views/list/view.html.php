<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Jan 9, 2009
 * 
 * @package package_name
 * @author Your Name <author@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2009 Toowoomba Regional Council/Developer Name 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */
 
defined('_JEXEC') or die('list view!');

jimport('joomla.application.component.view');

class ssomanagerviewList extends JView {
	function display($tpl=null) {
		$model =& $this->getModel();
		$forms = $model->getForms();
		$links = $model->getLinks();
		$this->assignRef('forms', $forms);
		$this->assignRef('links', $links);
		parent::display();
	}
}