<?php
// test for fail over code
/** include test utilities */
require('test.php');
/** include the library */
require('../joomla.ldap.bot/joomla.ldap.php');

/** off we test! */
$ldap = new ldapConnector(new mock());
$ldap->use_ldapV3 = true;
$servers = Array('192.168.1.20','192.168.1.9','127.0.0.1');
$ldap->host = implode(' ',$servers);
$ldap->host = $servers[2];
echo $ldap->host."\n";
//$ldap->alternatehost = $servers[1];
echo "Connecting to server\n";
if(!$ldap->connect()) {
	die("Failed to connect\n");
}
echo "Binding to server\n";
if(!$ldap->bind()) {
	echo ldap_error($ldap->_resource)."\n";
	die("Failed to bind\n");
}

print_r($ldap);