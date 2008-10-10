<?php
/**
 * CSV File Parser
 * 
 * Loads a CSV file and presents it like a database 
 * 
 * PHP5
 *  
 * Created on Oct 10, 2008
 * 
 * @package JAuthTools
 * @author Sam Moffatt <Sam.Moffatt@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Sam Moffatt 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/jauthtools   JoomlaCode Project: Joomla! Authentication Tools    
 */
 
jimport('joomla.database.database');

class JDatabaseCSV extends JDatabase {
	var $name = 'csv';

	// Hide these from JObject 
	private $_tables = Array();
	private $_queries = Array();
	private $_results = Array();
	private $_dirty = 0;
	private $_insertid = 0;	
	
	// These can be exposed via get/set routines
	private $persist = 0;
	private $affectedrows = 0;
	
	
	private $current = null; // current result		

	function __construct($options) {
		if(isset($options['name'])) $this->addTable($options);
		$this->persist = array_key_exists('persist', $options) ? $options['persist'] : 0;
		parent::__construct($options);
	}
	
	function addTable($options) {
		if(isset($options['name']) && isset($options['file'])) {
			$maxlinelength = array_key_exists('maxlinelength', $options) ? $options['maxlinelength'] : 1000;
			$key = array_key_exists('key', $options) ? $options['key'] : null; 
			$name = $options['name'];
			$fh = fopen($options['file'],'r');
			if(!$fh) {
				JError::raiseWarning(10,'Failed to load database '. $name);
				return false;
			}
			$cols = fgetcsv($fh, $maxlinelength); // max 1000 chars; over kill really
			$this->_tables[$name] = new CSVTable($name, $cols, $key); // kill any existing table or create new
			while($row = fgetcsv($fh, $maxlinelength)) {
				$this->_tables[$name]->addRow($row);
			}
			fclose($fh);
		}
	}
	
	function test() {
		return (function_exists( 'fgetcsv' ));
	}
	
	function connected() {
		return true; // always connected
	}
	
	function getEscaped($text, $extra = false) {
		return '"' . addslashes($text) .'"';
	}
	
	function setQuery( $sql, $offset = 0, $limit = 0, $prefix='#__' )
		$this->_dirty = 1; // set the dirty flag
		parent::setQuery( $sql, $offset, $limit, $prefix );
	}
	
	function query() {
		$this->affectedrows = 0; // reset this
		$this->_dirty = 0; // and this
	}
	
	function getAffectedRows() {
		return $this->affectedrows;
	}
	
	function explain() {
		return 'EXPLAIN not supported';
	}
	
	function getNumRows( $cur = null ) {
		if($cur) $cur = $this->current;
		return isset($results[$cur]) ? count($result[$cur]) : false;
	}
	
	function loadResult() {
		if($this->_dirty && !($cur = $this->query())) {
			return null;
		}
		if(!$this->_dirty) $cur = $this->current;
		$ret = null;
		if(isset($this->_results[$this->current][0][0])) {
			$ret = $this->_results[$this->current][0][0];
		}
		return $ret;
	}
	
	function loadResultArray($numinarray = 0) {
		if($this->_dirty && !($cur = $this->query())) {
			return null;
		}
		if(!$this->_dirty) $cur = $this->current;
		$ret = Array();
		if(isset($this->_results[$this->current][0])) {
			foreach($this->_results[$this->current][0] as $row) {
				$ret[] = $row[$numinarray];
			}
		}
		return $ret;
	}
	
	function loadAssoc() {
		if($this->_dirty && !($cur = $this->query())) {
			return null;
		}
		if(!$this->_dirty) $cur = $this->current;
		//$ret = $this->
		// TODO: Finish this function
	}
	
	function insertid() {
		return $this->_insertid;
	}
	
	function getVersion() {
		return '0.1';
	}
	
	function getCollation() { 
		return 'latin1_swedish_ci'; // fake this
	}
	
	function getTableList()  {
		return array_keys($this->_tables);
	}
	
}

class CSVTable {
	var $tablename = '';
	var $columns = Array();
	var $rows = Array();
	private $key = '';
	private $keyindex = -1;
	
	function __construct($tablename, $columns=Array(), $key=null) {
		$this->tablename = $tablename;
		$this->columns = $columns;
		if($key) {
			foreach($columns as $index=>$colname) {
				if($colname == $key) {
					$this->key = $key;
					$this->keyindex = $index;
				}
			}
		}
	}
	
	function addRow($row)  {
		if($this->keyindex > -1) {
			$this->rows[$row[$this->keyindex]] = $row;
		} else {
			$this->rows[] = $row;
		}
	}
}