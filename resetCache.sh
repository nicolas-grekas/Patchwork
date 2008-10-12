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
		rm -f `find $I/zcache.old/ -mindepth 3 -maxdepth 3 -type f -name "*.watch.*.txt" -exec sh -c 'grep "^U" $0 | grep $1 -vF' {} $I/zcache/ \; | sed s/^U//`
		rm -Rf $I/zcache.old
	fi
done
