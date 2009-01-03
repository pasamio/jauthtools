<?php

/**
 * Mutable JTable
 * Designed to do all of the really awesome things that 1.6 aims to prevent
 */
class MutableJTable extends JTable {
	function __construct($table, $key, &$db) {
		parent::__construct($table, $key, $db);
	}
	
	function __set($name, $value) {
		// fix for 1.6 compatibility
		$this->$name = $value;
	}
}