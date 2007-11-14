#!/bin/sh
# SSO Build Script

# Packages
mkdir packages

# Library
cd trunk/libraries/jauthtools/
tar -zcf ../../../packages/lib_jauthtools_sso.tgz lib_jauthtools_sso.xml sso.php
cd ../../../

# Plugins
# SSO Primary
cd trunk/plugins/system/
tar -zcf ../../../packages/plgSystemSSO.tgz sso.xml sso.php
cd ../

# SSO Plugins
cd sso
# eDir LDAP
tar -zcf ../../../packages/plgSSOeDirLDAP.tgz edirldap.php edirldap.xml
# HTTP
tar -zcf ../../../packages/plgSSOHTTP.tgz http.php http.xml

cd ../../../

