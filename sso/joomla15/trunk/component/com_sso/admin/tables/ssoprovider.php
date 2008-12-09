<?php
/**
* @version 	$Id: ssoProvider.class.php,v V1.1 6491 bytes, 2007-06-07 12:43:34 cero Exp $
* @package 	SSO
* @author	Tomo Cerovsek <Tomo.Cerovsek.fgg.uni-lj.si> 
* @author	Damjan Murn <Damjan.Murn.uni-lj.si>
* @copyright 	(C) 2007 SSO Team, UL FGG
* @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
* SSO was initiated during the EU CONNIE project
*/

defined('_JEXEC') or die('Direct access to this location is not allowed.');

jimport('joomla.database.table');

/**
 * SSO Provider
 * Derives from the JTable to provide access
 * @package SSO 
 */
class JTableSSOProvider extends JTable {
	var $id = null;
	var $plugin_id = null;
	var $key = null;
	var $name = null;
	var $description = null;
	var $_comments = null;
	var $abbreviation = null;
	var $_ipAddress = null;
	var $_country = null;
	var $_countryCode = null;
	var $_language = null;
	var $status = null;
	var $remotestatus = null;
	var $published = null;
	var $trusted = null;
	var $params = '';
	var $ordering = 0;
	var $_plugin = null;

    function __construct(&$database) {
        parent::__construct('#__sso_providers', 'id', $database);
    }

    function &getPlugin() {
    	if($this->plugin_id && !$this->_plugin) {
    		$this->_plugin =& JTable::getInstance('plugin');
    		$this->_plugin->load($this->plugin_id);
    	} else {
    		print_r($this);
    		die('no plugin ID and a valid plugin!');
    	}
    	return $this->_plugin;
    }
    
	/*function load($siteUrl) {
		// load events wipeout the plugin cache
		$this->_plugin = null;
		if( ! $siteUrl || ! parent::load( $siteUrl )) {
			return false;
		}
		else {
			return true;
		}
	}*/

	function init_record() {
		$this->published = '1';
		$this->trusted = '0';
	}

	function loadMyself() {
		global $mosConfig_live_site;
		$result = parent::load('LOCAL');
		if ($result) {
			$this->providerId = $mosConfig_live_site;
			return true;
		} else {
			return false;
		}
	}

	function getPublicPropertiesNames() {
		return array('providerId', 'siteUrl', 'siteName', 'description', 'ipAddress', 'country', 'countryCode', 'language');
	}

    function getPublicData(){
        $obj = new stdClass();
        $publicProperties = array('providerId', 'siteUrl', 'siteName', 'description', 'ipAddress', 'country', 'countryCode', 'language');
        foreach($this->getPublicPropertiesNames() as $publicProperty){
            $obj->$publicProperty = $this->$publicProperty;
        }
        return $obj;
    }

    function checkProviderData(){
        $requiredProperties = array('providerId', 'siteUrl', 'siteName', 'ipAddress');
        foreach($requiredProperties as $requiredProperty){
            if (! $this->$requiredProperty) {
				return false;
			}
        }
        return true;
    }

    function checkLocalProviderData(){
        $requiredProperties = array('siteName', 'ipAddress');
        foreach($requiredProperties as $requiredProperty){
            if (! $this->$requiredProperty) {
				return false;
			}
        }
        return true;
    }

    function fromStdClass($o) {
        foreach($this->getPublicPropertiesNames() as $publicProperty){
            $this->$publicProperty = trim($o->$publicProperty);
        }
    }


	function getStatusMessage($status) {
		switch ($status) {
			case 'REG_REQ_SENT':
				return 'Registration request sent';
			case 'REG_REQ_RECV':
				return 'Registration request received';
			case 'DENIAL_SENT':
				return 'Denial sent';
			case 'DENIAL_RECV':
				return 'Denial received';
			case 'REGISTERED':
				return 'Registered';
			case 'UNREGISTERED':
				return 'Unregistered';
			default:
				return '';
		}
	}


    function setStatus($status){
        global $database;
        $sql = "UPDATE #__sso_providers " .
               "SET status ='$status' " .
               "WHERE providerId='$this->providerId'";
        $database->setQuery($sql);
        if ( ! $database->query()) {
			return false;
        } else {
			return true;
		}
    }



	function publish( $cid, $publish ) {
		global $database, $my;

		if (!is_array($cid) || count($cid) < 1) {
		   $action = $publish ? 'publish' : 'unpublish';
		   echo "<script> alert('Select an item to $action.'); window.history.go(-1);</script>\n";
		   return false;
		}
		
		$cids = "'" . implode("','", $cid) . "'";
		$database->setQuery(
		   "UPDATE #__sso_providers SET published='$publish' " .
		   "WHERE providerId IN ($cids)");
		
		if (!$database->query()) {
		   echo "<script> alert('".$database->getErrorMsg() ."'); window.history.go(-1); </script>\n";
		   return false;
		}
		
		return true;
	}


   /*
   * @desc Delete providers
   * @param array an array with ids
   */

	function remove($cid) {
		global $database;
	
		if (!is_array( $cid ) || count( $cid ) < 1) {
			echo "<script> alert('Select an item to delete'); window.history.go(-1);</script>\n";
			exit;
		}
		if (count( $cid )) {
			$cids = "'" . implode( "','", $cid ) . "'";
			$query = "DELETE FROM #__sso_providers " .
				"WHERE providerId IN ($cids)"	;
	
			$database->setQuery( $query );
			if (!$database->query()) {
				echo "<script> alert('".$database->getErrorMsg()."'); window.history.go(-1); </script>\n";
			}
		}
		return true;
   }

	function saveRemoteProvider() {
		global $database;
		
		if (! $this->bind($_POST)) {
			echo "<script> alert('".$this->getError() ."'); window.history.go(-1); </script>\n";
			exit();
		}
		if (! $this->_db->updateObject( $this->_tbl, $this, $this->_tbl_key, false ) ) {
		   echo "<script> alert('" . $this->_db->getErrorMsg() ."'); window.history.go(-1); </script>\n";
		   exit();
		}
		return true;
	}

	function saveLocalProvider() {
		global $option, $database, $mosConfig_live_site, $mosConfig_absolute_path;
		
		if (! $this->bind($_POST)) {
			echo "<script> alert('".$this->getError() ."'); window.history.go(-1); </script>\n";
			exit();
		}
		$this->providerId = 'LOCAL';
		
		$database->setQuery("SELECT providerId FROM #__sso_providers WHERE providerId='LOCAL'");
		if ($database->loadResult()) { // record already exists
			if (! $this->update() ) {
			   echo "<script> alert('".$this->getError() ."'); window.history.go(-1); </script>\n";
			   exit();
			}
		}
		else { // record doesn't exist
			if (! $this->insert()) {
			   echo "<script> alert('".$this->getError() ."'); window.history.go(-1); </script>\n";
			   exit();
			}
		}
		return true;
	}


	function cancel() {
		return true;
	}

    function insert(){
        $result = $this->_db->insertObject($this->_tbl, $this);
		if ( ! $result) {
			$this->_error = $this->_db->getErrorMsg();
			return false;
		} else {
			return true;
		}
	}

    function update(){
        $result = $this->_db->updateObject( $this->_tbl, $this, $this->_tbl_key );
		if ( ! $result) {
			$this->_error = $this->_db->getErrorMsg();
			return false;
		} else {
			return true;
		}
    }

    function trust() {
    	global $database;
        $sql = "UPDATE #__sso_providers " .
               "SET trusted ='1' " .
               "WHERE providerId='$this->providerId'";
        $database->setQuery($sql);
        if ( ! $database->query()) {
			return false;
        } else {
			return true;
		}
    }
    
    function isLocal() {
    	return true; // check if the provider is local
    }
    
	/**
	* Overloaded bind function
	*
	* @access public
	* @param array $hash named array
	* @return null|string	null is operation was satisfactory, otherwise returns an error
	* @see JTable:bind
	* @since 1.5
	*/
	function bind($array, $ignore = '')
	{
		if (isset( $array['params'] ) && is_array($array['params']))
		{
			$registry = new JRegistry();
			$registry->loadArray($array['params']);
			$array['params'] = $registry->toString();
		}

		return parent::bind($array, $ignore);
	}
}

?>
