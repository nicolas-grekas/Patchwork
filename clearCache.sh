#!/bin/bash

chown apache: * -R
find -name ".*.zcache.php" -exec rm -f {} \;
find -name config.php -exec touch {} \;
find -name ".config.zcache.php" -exec rm -f {} \;

for I in `find -name zcache -type d`
do
	for J in `find $I/?/? -maxdepth 1 -type f -name "*.watch.*.php" 2> /dev/null`
	do
		echo "<?php @include '$J';" | php -q
	done

	find $I/?/? -maxdepth 1 -type f -exec rm -f {} \;
done

