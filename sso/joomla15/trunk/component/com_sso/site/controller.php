<?php
/**
 * Document Description
 *
 * Document Long Description
 *
 * PHP4/5
 *
 * Created on Sep 28, 2007
 *
 * @package JLibMan
 * @author Your Name <author@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Sam Moffatt
 * @version SVN: $Id:$
 * @see Project Documentation DM Number: #???????
 * @see Gaza Documentation: http://gaza.toowoomba.qld.gov.au
 * @see JoomlaCode Project: http://joomlacode.org/gf/project/
 */

// no direct access

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.controller');
jimport('jauthtools.sso');
jimport('jauthtools.usersource');

/**
 * JLibMan Component Controller
 *
 * @package    JLibMan
 */
class SSOController extends JController
{
	/**
	 * Method to display the view
	 *
	 * @access    public
	 */
	function display()
	{
		echo '<p>SSO Manager</p>';
		$plugin = JRequest::getVar('plugin', '');
		$host = new JAuthSSOAuthentication();
		if($plugin) {
			$plugin = JPluginHelper :: getPlugin('sso', $plugin);
			if(empty($plugin)) {
				JError::raiseError(500, 'Invalid plugin');
				return false;
			}
				
			$className = 'plg' . $plugin->type . $plugin->name;
			if (class_exists($className)) {
				$plugin = new $className ($host, (array)$plugin);
			} else {
				JError :: raiseWarning(500, 'Could not load ' . $className);
				return false;
			}

			// Output the form
			echo $plugin->getForm();
		} else {
			$plugins = JPluginHelper::getPlugin('sso');
			foreach($plugins as $plugin) {
				$className = 'plg' . $plugin->type . $plugin->name;

				if (class_exists($className)) {
					$plugin = new $className ($host, (array)$plugin);
				} else {
					JError::raiseWarning(50, 'Could not load ' . $className);
					continue; // skip this plugins!
				}
				
				// Output the form if the function is available
				if(method_exists($plugin, 'getForm')) echo '<p>'.$plugin->getForm().'</p>';
			}
		}
	}

	function delegate() {
		$plugin = JPluginHelper::getPlugin('system','sso');
		
		if($plugin) {
			die('plugin enabled');
			$this->setRedirect('index.php');
			return true;
		}
		
		$document =& JFactory::getDocument();
		$plugin = JRequest::getVar('plugin', '');
		$user =& JFactory::getUser();
		/*if(!$params->get('override',0)) {
			if($user->id) return false;
			}*/

		$before = $user->id;
		if($plugin) {
			$plugin = JPluginHelper :: getPlugin('sso', $plugin);
			if(empty($plugin)) {
				JError::raiseError(500, 'Invalid plugin');
				return false;
			}
				
			$className = 'plg' . $plugin->type . $plugin->name;
			$host = new JAuthSSOAuthentication();
			if (class_exists($className)) {
				$plugin = new $className ($host, (array)$plugin);
			} else {
				JError :: raiseWarning(500, 'Could not load ' . $className);
				return false;
			}

			// Try to authenticate remote user
			$username = $plugin->detectRemoteUser();
			$autocreate = 0; // TODO: Unfudge this so that it gets it off a param somewhere
			// If authentication is successful log them in
			if ($username != '') {
				if($autocreate) {
					$usersource = new JAuthUserSource();
					$usersource->doUserCreation($username);
				}
				$host->doSSOSessionSetup($username);
			}
		} else {
			JError::raiseError(500, JText::_('No plugin specified'));
			return false;
		}
		if($before != $user->id) { // user id changed
			$app =& JFactory::getApplication();
			$uri =& JFactory::getURI();
			//$nextHop = $params->get('nexthop',false);
			$nextHop = false;
			if(JRequest::getMethod() == 'GET') { // get methods we can proxy easily without losing info
				if($nextHop) { // redirect to the next hop location
					$app->redirect(getLinkFromItemID($nextHop));
				} else { // redirect back to the same page
					$app->redirect($uri->toString());
				}
			} else { // might be a POST or other request so don't redirect
				// if the document type is html then output else be silent
				if($document->getType() == 'html') { 
					echo '<p>'. JText::_('SSO Login will activate next request') .'</p>';
				}				
			}
		} else {
			if($document->getType() == 'html') '<p>'.JText::_('No user detected') .'</p>';
		}
	}
}