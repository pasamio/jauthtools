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
}