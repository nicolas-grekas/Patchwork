#!/bin/bash

chown apache: `dirname $0` -R

for I in `locate /config.patchwork.php | grep -v \\\.svn -v 2> /dev/null`
do
	I=`dirname $I`
	touch $I/config.patchwork.php
	rm -f $I/.*.zcache.php 2> /dev/null

	if test -d $I/zcache
	then
		mv $I/zcache $I/zcache.old
		mkdir $I/zcache
		chown `stat $I/zcache.old -c %u:%g` $I/zcache
	fi

	rm -f "$I/.parentPaths.db" 2> /dev/null
	rm -f "$I/.patchwork.lock" 2> /dev/null
	rm -f "$I/.patchwork.php"  2> /dev/null

	if test -d $I/zcache.old
	then
		for J in `find $I/zcache.old/?/? -maxdepth 1 -type f -name "*.watch.*.php" 2> /dev/null`
		do
			echo "<?php @include '$J';" | php -q
		done

		rm -Rf $I/zcache.old
	fi
done
