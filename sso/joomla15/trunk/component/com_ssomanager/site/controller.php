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
 * @author Sam Moffatt <pasamio@gmail.com>
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2009 Sam Moffatt
 * @version SVN: $Id:$
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
class SSOManagerController extends JController
{
	/**
	 * Method to display the view
	 *
	 * @access    public
	 */
	function display()
	{
		$plugin = JRequest::getVar('plugin', '');
		$model =& $this->getModel();
		if($plugin) {
			echo '<p>SSO Manager</p>';
			$host = new JAuthSSOAuthentication();
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
			if(method_exists($plugin, 'getForm')) echo $plugin->getForm();
			if(method_exists($plugin, 'getSPLink')) echo $plugin->getSPLink();
		} else {
			$model->prepareList();
			$view =& $this->getView('list','html');
			$view->setModel($model, true);
			$view->display();
		}
	}

	function delegate() {
		$plugin = JPluginHelper::getPlugin('system','sso');
		
		if($plugin) {
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
			if (!empty($username)) {
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
			
			if($nextHop) { // redirect to the next hop location
				$app->redirect(getLinkFromItemID($nextHop));
			} else { // redirect back to the same page
				$app->redirect($uri->toString());
			}
		} else {
			if($document->getType() == 'html') '<p>'.JText::_('No user detected') .'</p>';
		}
	}
}
