#!/bin/sh
cp ~/public_html/workspace/joomla_trunk/plugins/authentication/gmail.* .
mv gmail.xml advgmail.xml
mv gmail.php advgmail.php
sed -e"s/Authentication - GMail/Authentication - Advanced GMail/" advgmail.xml | sed -e"s/gmail/advgmail/g" | sed -e"s/extension/install/" | sed -e"s/1.6/1.5/" > advgmail.xml.tmp
mv advgmail.xml.tmp advgmail.xml 
rm plgAuthenticationAdvGMail.zip
zip plgAuthenticationAdvGMail.zip advgmail.*
