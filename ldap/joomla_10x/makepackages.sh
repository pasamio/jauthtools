#!/bin/sh
# Builds the packages
ORIGINAL=`pwd`
for i in `ls trunk`
do
	echo Building $i
	cd trunk/$i
	tar -zcvf ../../packages/$i.tgz *
	echo
	cd $ORIGINAL
done

echo Done.
