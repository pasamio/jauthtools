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
defined('_JEXEC') or die();

jimport( 'joomla.application.component.model' );
jimport( 'joomla.filesystem.file');
jimport( 'joomla.filesystem.folder');
jimport( 'jauthtools.token' );

class TokenLoginModelTokenLogin extends JModel {

	function getList() {
		$dbo =& JFactory::getDBO();
		$query = 'SELECT * FROM #__jauthtools_tokens';
		$limit		= JRequest::getVar('limit', 100, '', 'int');
		$limitstart	= JRequest::getVar('limitstart', 0, '', 'int');
		$dbo->setQuery($query, $limitstart, $limit);
		$results = $dbo->loadObjectList();
		return $results;
	}

	function getListCount() {
		$dbo =& JFactory::getDBO();
		$query = 'SELECT COUNT(*) FROM #__jauthtools_tokens';
		$dbo->setQuery($query);
		return $dbo->loadResult();
	}

	function getData($logintoken) {
		$null = null; // fake this as we're not going to do any db ops on it
		$token = new JAuthToolsToken($null);
		if($logintoken) {
			$dbo =& JFactory::getDBO();
			$query = 'SELECT * FROM #__jauthtools_tokens WHERE logintoken = '. $dbo->Quote($logintoken);
			$dbo->setQuery($query);
			$data = $dbo->loadObject();

			if($data) {
				$token->mapObject($data);
			}
		}
		if($token->expiry) {
			jimport('joomla.utilities.date');
			$date = new JDate($token->expiry);
			$token->expiry = $date->toMySQL();
		}
		return $token;
	}

	function save() {
		$dbo =& JFactory::getDBO();
		$token = new JAuthToolsToken($dbo);
		$token->bind($_REQUEST);
		jimport('joomla.utilities.date');
		if($token->expiry) {
			$date = new JDate($token->expiry);
			$token->expiry = $date->toUnix(); // revert this
		} else {
			$token->expiry = time() + (120 * 3600);
		}
		$token->logins = intval($token->logins) ? intval($token->logins) : 5;
		$token->logintoken = JRequest::getVar('token',''); // set this differently to prevent sso taking over inadvertantly		
		return $token->store();
	}
	
	function revoke() {
		$cid = JRequest::getVar( 'cid', array(), 'post', 'array' );
		$count = 0;
		foreach($cid as $token) {
			if(JAuthToolsToken::revokeToken($token)) {
				++$count;
			}
		}
		return $count;
	}
}