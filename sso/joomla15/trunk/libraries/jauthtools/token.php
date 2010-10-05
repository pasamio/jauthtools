<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Nov 26, 2008
 * 
 * @package package_name
 * @author Sam Moffatt <pasamio@gmail.com>
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2009 Sam Moffatt 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */
 
class JAuthToolsToken extends JTable {

	var $username = '';
	var $logintoken = '';
	var $logins = '';
	var $expiry = '';
	var $landingpage = '';
	
	/**
	 * Constructor
	 */
	function __construct(&$db) {
		parent::__construct( '#__jauthtools_tokens', 'logintoken', $db );
	}
	
	/**
	 * Issue a token
	 * @var string username to use
	 * @var int number of hours before token expiry (default is 120 or 5 days)
	 * @var int number of logins to provide before token is removed (default 5)
	 * @var string token identifier
	 */
	function issueToken($username, $expiry=120, $logins=5, $landingpage='') {
		$dbo =& JFactory::getDBO();
		$token = new JAuthToolsToken($dbo, true);
		$token->username = $username;
		$token->expiry = time() + ($expiry * 3600);
		$token->logins = $logins;
		$token->landingpage = $landingpage;
		if(!$token->store()) {
			return false;
		} else {
			return md5(substr($token->logintoken, 0, 32)).md5(substr($token->logintoken, 32,32));
		}
	}
	
	/**
	 * Generate a login url for a token (either from the loaded object or from a given param)
	 * @param string Token to use, if blank uses the logintoken attribute of the current object
	 * @return string URL to redirect to, alternatively blank on failure
	 */
	function generateLoginURL($token='', $encoded = false) {
		if(!$token) {
			if(!isset($this->logintoken)) {
				return '';
			} else {
				$token = $this->logintoken;
				$encoded = false; // the login token is never encoded
			}
		}
		if($encoded) {
			return str_replace('administrator/','', JURI::base()).'index.php?option=com_tokenlogin&logintoken='. $token);
		} else {
			return str_replace('administrator/','', JURI::base()).'index.php?option=com_tokenlogin&logintoken='. md5(substr($token, 0, 32)).md5(substr($token, 32,32));
		}
	}
	
	/**
	 * Map an object to this object
	 * @param object Object to map to this object
	 */
	function mapObject($object) {
		$vars = get_object_vars($object);
		$class_vars = get_class_vars(get_class($this));
		foreach($vars as $var=>$value) {
			if(array_key_exists($var, $class_vars)) {
				$this->$var = $value;
			}
		}
	}
	
	/**
	 * Inserts a new row if id is zero or updates an existing row in the database table
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @access public
	 * @param boolean If false, null object variables are not updated
	 * @return null|string null if successful otherwise returns and error message
	 */
	function store( $updateNulls=false )
	{
		if( $this->logintoken )
		{
			$ret = $this->_db->updateObject( $this->_tbl, $this, $this->_tbl_key, $updateNulls );
		}
		else
		{
			$minime = new JAuthToolsToken($this->_db);
			$this->logintoken = $this->createLoginToken();
			while($minime->load($this->logintoken)) {
				$this->logintoken = $this->createLoginToken();	
			}
			$ret = $this->_db->insertObject( $this->_tbl, $this, $this->_tbl_key );
		}
		if( !$ret )
		{
			$this->setError(get_class( $this ).'::store failed - '.$this->_db->getErrorMsg());
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Revoke a given token
	 * @param string Token to revoke
	 * @return boolean result of db operation
	 */
	function revokeToken($token) {
		$dbo =& JFactory::getDBO();
		$dbo->setQuery('DELETE FROM #__jauthtools_tokens WHERE logintoken = '. $dbo->Quote($token));
		return $dbo->query();
	}
	
	/**
	 * Revoke a users outstanding tokens
	 * @param string Username to revoke
	 * @return boolean result of db operation
	 */
	function revokeUserTokens($username) {
		$dbo =& JFactory::getDBO();
		$dbo->setQuery('DELETE FROM #__jauthtools_tokens WHERE username = '. $dbo->Quote($username));
		return $dbo->query();
	}
	

	/**
	 * Validate a given token
	 * @param string Token to validate
	 * @return boolean Result of validation
	 */
	function validateToken($key) {
		$dbo =& JFactory::getDBO();
		// delete any older tokens
		$dbo->setQuery('DELETE FROM #__jauthtools_tokens WHERE expiry < "' . time() .'"');
		$dbo->Query();
		// find the matching token
		$dbo->setQuery('SELECT * FROM #__jauthtools_tokens WHERE concat(md5(substr(logintoken,1,32)), md5(substr(logintoken,33,32))) = '. $dbo->Quote($key));
		$row = $dbo->loadObject();
		if($row) {
			if(!--$row->logins) {
				// delete the token if the number of logins is exhausted
				$dbo->setQuery('DELETE FROM #__jauthtools_tokens WHERE logintoken = '. $dbo->Quote($row->logintoken)	);
			} else {
				$dbo->setQuery('UPDATE #__jauthtools_tokens SET logins = logins - 1 WHERE logintoken = '. $dbo->Quote($row->logintoken)	);
			}
			$dbo->Query();
			return $row;
		}
		return false;
	}		
	
	/**
	 * Create a token-string
	 *
	 * @param int $length lenght of string
	 * @return string $id generated token (64 char)
	 */
	function createLoginToken()
	{
		static $chars	=	'0123456789abcdef';
		$dirname = dirname(__FILE__);
		$fstat = implode('', stat(__FILE__));
		$max			=	strlen( $chars ) - 1;
		$token			=	'';
		for( $i = 0; $i < 32; ++$i ) {
			$token .=	$chars[ (rand( 0, $max )) ];
		}
		return md5($token.$dirname).md5($token.$fstat);
	}
}