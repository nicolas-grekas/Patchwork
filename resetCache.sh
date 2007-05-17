#!/bin/bash

chown apache: * -R

for I in `find -name config.patchwork.php -printf "%h\n" 2> /dev/null`
do
	touch $I/config.patchwork.php
	rm -f $I/.*.zcache.php 2> /dev/null

	test -d $I/zcache && mv $I/zcache $I/zcache.old

	rm -f "$I/.config.lock.php" 2> /dev/null
	rm -f "$I/.config.patchwork.php"  2> /dev/null

	if test -d $I/zcache.old
	then
		for J in `find $I/zcache.old/?/? -maxdepth 1 -type f -name "*.watch.*.php" 2> /dev/null`
		do
			echo "<?php @include '$J';" | php -q
		done

		rm -Rf $I/zcache.old
	fi
done
