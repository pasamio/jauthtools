<?php
/**
 * This file provides Joomla! 1.5 Session Hacking Support
 */
function doJ15SSO($username) {
		// Get Database and find user
		$database = JFactory::getDBO();
		$query = 'SELECT id FROM #__users WHERE username=' . $database->Quote($username);
		$database->setQuery($query);
		$result = $database->loadResult();
		// If the user already exists, create their session
		if ($result > 0) {
			// load plugin parameters
			$plugin = & JPluginHelper :: getPlugin('user', 'joomla');
			$params = new JParameter($plugin->params);

			$my = new JUser();
			jimport('joomla.user.helper');
			if ($id = intval(JUserHelper :: getUserId($username))) {
				$my->load($id);
			} else {
				return JError :: raiseWarning('SOME_ERROR_CODE', 'JAuthSSOAuthentication::doSSOSessionSetup: Failed to load user');
			}

			// If the user is blocked, redirect with an error
			if ($my->get('block') == 1) {
				return JError :: raiseWarning('SOME_ERROR_CODE', JText :: _('E_NOLOGIN_BLOCKED'));
			}

			//Mark the user as logged in
			$my->set('guest', 0);

			// Discover the access group identifier
			// NOTE : this is a very basic for of permission handling, will be replaced by a full ACL in 1.6
			jimport('joomla.factory');
			$acl = & JFactory :: getACL();
			$grp = $acl->getAroGroup($my->get('id'));

			$my->set('aid', 1);
			if ($acl->is_group_child_of($grp->name, 'Registered', 'ARO') || $acl->is_group_child_of($grp->name, 'Public Backend', 'ARO')) {
				// fudge Authors, Editors, Publishers and Super Administrators into the special access group
				$my->set('aid', 2);
			}

			//Set the usertype based on the ACL group name
			$my->set('usertype', $grp->name);

			// Register the needed session variables
			$session = & JFactory :: getSession();
			$session->set('user', $my);

			// Get the session object
			$table = & JTable :: getInstance('session');
			$table->load($session->getId());

			$table->guest = $my->get('guest');
			$table->username = $my->get('username');
			$table->userid = intval($my->get('id'));
			$table->usertype = $my->get('usertype');
			$table->gid = intval($my->get('gid'));

			$table->update();

			// Hit the user last visit field
			$my->setLastVisit();

			// Set remember me option
			$lifetime = time() + 365 * 24 * 60 * 60;
			setcookie('usercookie[username]', $my->get('username'), $lifetime, '/');
			setcookie('usercookie[password]', $my->get('password'), $lifetime, '/');
			$auth = true;
			return true;
		}
	}
?>