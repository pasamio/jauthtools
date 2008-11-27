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
 
class TokenLoginViewTokenLogin extends JView {
	function display($tpl = null)
	{
		// Push a model into the view
		$model	= &$this->getModel();		
		$items = $model->getList();
		$total = $model->getListCount();
		$limit		= JRequest::getVar('limit', 100, '', 'int');
		$limitstart	= JRequest::getVar('limitstart', 0, '', 'int');
		jimport('joomla.html.pagination');
		$pagination = new JPagination($total, $limitstart, $limit);
		$this->assignRef('pagination', $pagination);
		$this->assignRef('items', $items);
		parent::display($tpl);
	}
	
	function loadItem($index=0)
	{
		$item =& $this->items[$index];
		$item->index	= $index;
		$item->cb = '&nbsp;';
		$null = null;
		$token = new JAuthToolsToken($null);
		$token->mapObject($item);
		$item->loginurl = $token->generateLoginURL();
		$item->cb = '<input type="checkbox" id="cb'. $index.'" onclick="isChecked(this.checked);" value="'. $item->logintoken .'" name="cid[]"/>';
		$this->assignRef('item', $item);
	}
}