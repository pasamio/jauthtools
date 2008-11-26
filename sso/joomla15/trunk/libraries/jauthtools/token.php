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
 * @author Your Name <author@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Developer Name 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */
 
class JAuthToolsToken extends JTable {

	var $username = '';
	var $logintoken = '';
	var $logins = '';
	var $expiry = '';
	var $_new = false;
	
	function __construct(&$db) {
		parent::__construct( '#__jauthtools_tokens', 'logintoken', $db );
		// when we're creating a new token, ensure we have a table to put it in
		$this->init();
	}

	function init() {
		static $hit = false;
		if($hit) return;
		$dbo =& JFactory::getDBO();
		$query = <<<EOQ
CREATE TABLE IF NOT EXISTS `#__jauthtools_tokens` (
  `logintoken` varchar(150)  NOT NULL,
  `username` varchar(150)  NOT NULL,
  `logins` int UNSIGNED NOT NULL,
  `expiry` VARCHAR(14)  NOT NULL,
  PRIMARY KEY (`logintoken`),
  INDEX `usernames`(`username`),
  INDEX `expiry`(`expiry`)
)
ENGINE = MyISAM
COMMENT = 'JAuthTools Token Store';
EOQ;
		$dbo->setQuery($query);
		$dbo->query();		
		$hit = true;	
	}
	
	/**
	 * Issue a token
	 * @var string username to use
	 * @var int number of hours before token expiry (default is 120 or 5 days)
	 * @var int number of logins to provide before token is removed (default 5)
	 * @var string token identifier
	 */
	function issueToken($username, $expiry=120, $logins=5) {
		$dbo =& JFactory::getDBO();
		$token = new JAuthToolsToken($dbo, true);
		$token->username = $username;
		$token->expiry = time() + ($expiry * 3600);
		$token->logins = $logins;
		$token->logintoken = $token->createLoginToken();
		if(!$token->store()) {
			return false;
		} else {
			return md5(substr($token->logintoken, 0, 32)).md5(substr($token->logintoken, 32,32));
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
		if( $this->_new )
		{
			$ret = $this->_db->updateObject( $this->_tbl, $this, $this->_tbl_key, $updateNulls );
		}
		else
		{
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
	
	function load($oid = null) {
		$this->_new = false;
		parent::load($oid);
	}

	function revokeToken($token) {
		$dbo =& JFactory::getDBO();
		$dbo->setQuery('DELETE FROM #__jauthtools_tokens WHERE logintoken = '. $dbo->Quote($token));
		return $dbo->query();
	}
	
	function revokeUserTokens($username) {
		$dbo =& JFactory::getDBO();
		$dbo->setQuery('DELETE FROM #__jauthtools_tokens WHERE username = '. $dbo->Quote($username));
		return $dbo->query();
	}

	function validateToken($key) {
		// when we're looking for a token ensure we have a table
		JAuthToolsToken::init();
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
			return $row->username;
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