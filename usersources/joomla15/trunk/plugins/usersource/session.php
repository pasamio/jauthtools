<?php
/**
 * Session User Source
 * 
 * Grabs users details out of the session
 * This is designed to optimise access where the SSO
 * system will typically pull the information out
 * including the users details (e.g. JAuthTools'
 * Google Auth System)
 * 
 * PHP4/5
 *  
 * Created on July 3, 2008
 * 
 * @package Joomla! Authentication Tools
 * @author Sam Moffatt <pasamio@gmail.com>
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Sam Moffatt 
 * @version SVN: $Id:$
 * @see JoomlaCode Project: http://joomlacode.org/gf/project/jauthtools/
 */

jimport('joomla.event.plugin');


/**
 * SSO Initiation
 * Kicks off SSO Authentication
 */
class plgUserSourceSession extends JPlugin {
	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @since 1.5
	 */
	function plgUserSourceSession(& $subject) {
		parent :: __construct($subject); 
	}

	/**
	 * Retrieves a user
	 * @param string username Username of target use
	 * @return JUser object containing the valid user or false
	 */
	function getUser($username,&$user) {
		$session =& JFactory::getSession();
		$details = $session->get('UserSourceDetails',null);
		if($details) {
			$session->set('UserSourceDetails',null); // kill the variable
			if(!is_array($details)) {
				$details = Array($details); // wrap in array
			}
			foreach($details as $detail) {
				if(is_object($detail) && $detail->username == $username) {
					$user->username = $detail->username;
					$user->name = $detail->name;
					$user->email = $detail->email;
					$user->gid = 18;
			 	 	$user->usertype = 'Registered';
					return true;					
				}
			}
		}
		return false;		
	} 
}
