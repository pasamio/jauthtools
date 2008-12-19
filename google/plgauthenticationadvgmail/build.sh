#!/bin/sh
cp ~/public_html/workspace/joomla_trunk/plugins/authentication/gmail.* .
mv gmail.xml advgmail.xml
mv gmail.php advgmail.php
sed -e"s/Authentication - GMail/Authentication - Advanced GMail/" advgmail.xml | sed -e"s/gmail/advgmail/g" | sed -e"s/extension/install/" | sed -e"s/1.6/1.5/" > advgmail.xml.tmp
sed -e"s/plgAuthenticationGMail/plgAuthenticationAdvGMail/" advgmail.php >  advgmail.php.tmp
mv advgmail.xml.tmp advgmail.xml 
mv advgmail.php.tmp advgmail.php
rm plgAuthenticationAdvGMail.zip
zip plgAuthenticationAdvGMail.zip advgmail.*
