<?php
/**
 * This file provides Joomla! 1.0 Session Hacking Support
 */

function doJ10SSO($username) {
			global $acl, $database, $mainframe;
			$database->setQuery("SELECT id FROM #__users WHERE username = '". $username."'");
			$id = $database->loadResult();
			$user = new mosUser($database);
			$user->load($id);
 			// fudge the group stuff
			$grp = $acl->getAroGroup($user->id);
			$user->gid = 1;

			if ($acl->is_group_child_of($grp->name, 'Registered', 'ARO') || $acl->is_group_child_of($grp->name, 'Public Backend', 'ARO')) {
				// fudge Authors, Editors, Publishers and Super Administrators into the Special Group
				$user->gid = 2;
			}
			$user->usertype = $grp->name;

			$session = & $mainframe->_session;
			$session->guest = 0;
			$session->username = $user->username;
			$session->userid = intval($user->id);
			$session->usertype = $user->usertype;
			$session->gid = intval($user->gid);
			$userid = $user->id;
			// Persistence
			$query = "SELECT id, name, username, password, usertype, block, gid"
			. "\n FROM #__users"
			. "\n WHERE id = $userid"
			;
			$row = null;
			$database->setQuery( $query );
			$database->loadObject($row);
			$lifetime               = time() + 365*24*60*60;
			$remCookieName  = mosMainFrame::remCookieName_User();
			$remCookieValue = mosMainFrame::remCookieValue_User( $row->username ) . mosMainFrame::remCookieValue_Pass( $row->password ) . $row->id;
			setcookie( $remCookieName, $remCookieValue, $lifetime, '/' );
			$session->store();
			// update user visit data
			$currentDate = date("Y-m-d\TH:i:s");

			$query = "UPDATE #__users"
			. "\n SET lastvisitDate = ". $database->Quote( $currentDate )
			. "\n WHERE id = " . (int) $session->userid
			;
			$database->setQuery($query);
			$database->Query();
			
			mosCache :: cleanCache();
			return true;
}
?>