<?php
/**
* @version		$Id: view.html.php 11376 2008-12-31 00:14:24Z eddieajau $
* @package		Joomla
* @subpackage	Modules
* @copyright	Copyright (C) 2005 - 2008 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * HTML View class for the Modules component
 *
 * @static
 * @package		Joomla
 * @subpackage	Modules
 * @since 1.6
 */
class SSOViewSelecttype extends JView
{
	function display($tpl = null)
	{
		// Initialize some variables
		$model =& $this->getModel();
		$plugins = $model->getList();
		
		JToolBarHelper::title(JText::_('SSO Manager') . ': <small><small>[ '. JText::_('New') .' ]</small></small>', 'plugin.png');
		JToolBarHelper::customX('edit', 'forward.png', 'forward_f2.png', 'Next', true);
		JToolBarHelper::cancel();
		

		// sort array of objects alphabetically by name
		JArrayHelper::sortObjects($plugins, 'name');

		$this->assignRef('plugins',		$plugins);

		parent::display($tpl);
	}
}
