#!/bin/bash

chown apache: * -R
find -name ".*.zcache.php" -exec rm -f {} \;
find -name config.cia.php -exec touch {} \;

for I in `find -name zcache -type d 2> /dev/null`
do
	mv $I $I.old

	for J in `find $I.old/?/? -maxdepth 1 -type f -name "*.watch.*.php" 2> /dev/null`
	do
		echo "<?php @include '$J';" | php -q
	done

	rm -Rf $I.old
done

find -name ".config.zcache.php" -exec rm -f {} \;
