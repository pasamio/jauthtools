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
 
class ssomanagerviewList extends JView {
	
	function display($tpl=null) {
		$model =& $this->getModel();
		$list = $model->getList();
		$this->assign('mode', $model->getMode());
		$this->assignRef('items', $list);
		parent::display($tpl);
	}
	
	function loadItem($index) {
		$item =& $this->items[$index];
		$item->index = $index;
		switch($item->type) {
			case 'A':
				$item->type = 'Type A - Normal SSO Plugin';
				break;
			case 'B':
				$item->type = 'Type B - Service Provider SSO Plugin';
				break;
			case 'C':
				$item->type = 'Type C - Redirected Login SSO Plugin';
				break;
		}
		$item->published  = JHtml::_('grid.published', $item, $index);
		$item->cb = '<input type="checkbox" id="cb'. $index.'" onclick="isChecked(this.checked);" value="'. $item->id .'" name="cid[]"/>';
		$this->assignRef('item',$item);
	}
}