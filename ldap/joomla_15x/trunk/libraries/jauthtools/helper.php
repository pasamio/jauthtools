<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Oct 15, 2008
 * 
 * @package package_name
 * @author Your Name <author@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Developer Name 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */
 
 
class JAuthToolsHelper {
	
	/**
	 * Gets a JParameter object for the param
	 *
	 * @param string $group The group
	 * @param string $plugin The plugin
	 * @return JParameter plugin's params
	 */
	function &getPluginParams($group, $plugin) {
		$retval = false;
		$dbo =& JFactory::getDBO();
		$query = 'SELECT params FROM #__plugins WHERE folder = "'. $group .'" AND element = "'. $plugin .'"';
		$dbo->setQuery($query);
		$result = $dbo->loadResult();
		if($result) {
			$retval = new JParameter($result);
		}
		return $retval;
	}
	
	function getContexts($moduleid=0) {
		static $results = null;
			if($results == null) {
			jimport('joomla.application.module.helper');
			if($moduleid) {
				$module = JAuthToolsHelper::getModule('contextlogin', null, $moduleid);
			} else {
				$module = JModuleHelper::getModule('contextlogin');	
			}
			$params = new JParameter($module->params);
			$contexts = $params->get('contexts');
			$result = Array();
			if(!empty($contexts)) {
				$result = explode("\n", $contexts);
			}
		}
		return $result;
	}
	
	function getContext($contextid) {
		$contexts = JAuthToolsHelper::getContexts();
		if(array_key_exists($contextid, $contexts)) {
			return $contexts[$contextid];
		}
		return '';
	}
	
	/**
	 * Get module by name (real, eg 'Breadcrumbs' or folder, eg 'mod_breadcrumbs')
	 *
	 * @access      public
	 * @param       string  $name   The name of the module
	 * @param       string  $title  The title of the module, optional
	 * @return      object  The Module object
	 */
	function &getModule($name, $title = null, $moduleid = 0 )
	{
		$result         = null;
		$modules        =& JModuleHelper::_load(); // ooh i wonder if this will break
		$total          = count($modules);
		for ($i = 0; $i < $total; $i++)
		{
			// Match the name of the module
			if ($modules[$i]->name == $name && (!$moduleid || $modules[$i]->id == $moduleid))
			{
				// Match the title if we're looking for a specific instance of the module
				if ( ! $title || $modules[$i]->title == $title )
				{
					$result =& $modules[$i];
					break;  // Found it
				}
			}
		}
		
		// if we didn't find it, and the name is mod_something, create a dummy object
		if (is_null( $result ) && substr( $name, 0, 4 ) == 'mod_')
		{
			$result                         = new stdClass;
			$result->id                     = 0;
			$result->title          = '';
			$result->module         = $name;
			$result->position       = '';
			$result->content        = '';
			$result->showtitle      = 0;
			$result->control        = '';
			$result->params         = '';
			$result->user           = 0;
		}
		
		return $result;
	}
	
}