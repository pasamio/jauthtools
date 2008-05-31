<?php
// test for params handling code
$params = <<<EOF
host=192.168.1.9
alternatehost=192.168.1.8
port=389
use_ldapV3=1
negotiate_tls=0
no_referrals=1
ldap_is_ad=1
base_dn=DC=site3,DC=digitalpaper,DC=homelinux,DC=net
users_dn=
search_string=sAMAccountName=[search]
username=CN=Administrator,CN=Users,DC=site3,DC=digitalpaper,DC=homelinux,DC=net
password=91153642
auth_method=authbind
ldap_fullname=name
ldap_email=mail
ldap_uid=sAMAccountName
ldap_password=userPassword
ldap_blocked=loginDisabled
ldap_groupname=memberOf
autocreate=1
autocreateregistered=1
demoteuser=0
forceldap=0
cbconfirm=0
obscurepw=0
syncloginonly=1
defaultgroup=frontend
groupMap=
use_iconv=0
iconv_from=ISO8859-1
iconv_to=UTF-8
ip_blacklist=127.0.0.1
externaltable=jos_contact_details
uidfield=user_id
pkeyfield=id
EOF;

$primary = '192.168.1.20'; $secondary = '192.168.1.90';
$regexs = Array('/^host=[0-9.A-Za-z ]*/','/^alternatehost=[0-9.A-Za-z ]*/');
$replacement = Array('host='.$primary,'alternatehost='.$secondary);
$params = implode("\n",preg_replace($regexs, $replacement, explode("\n",$params)));
echo "\n";
echo $params;
echo "\n";