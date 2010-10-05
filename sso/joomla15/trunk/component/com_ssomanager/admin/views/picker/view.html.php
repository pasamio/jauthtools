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
 * @author Sam Moffatt <pasamio@gmail.com>
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2009 Sam Moffatt 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.view');
 
class ssomanagerviewPicker extends JView {
	
	function display($tpl=null) {
		$model =& $this->getModel();
		$model->setMode('BG');
		$list = $model->getList();
		$this->assign('mode', $model->getMode());
		$this->assignRef('items', $list);
		parent::display($tpl);
	}
	
	function loadItem($index) {
		$item =& $this->items[$index];
		$item->index = $index;
		$item->cb = '<input type="checkbox" id="cb'. $index.'" onclick="isChecked(this.checked);" value="'. $item->id .'" name="cid[]"/>';
		$this->assignRef('item',$item);
	}
}