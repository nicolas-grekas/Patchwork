#!/bin/bash

chown apache: * -R
find -name ".*.zcache.php" -exec rm -f {} \;
find -name config.php -exec touch {} \;
find -name ".config.zcache.php" -exec rm -f {} \;

for I in `find -name zcache -type d`
do
	find $I/?/? -maxdepth 1 -type f -name "*.watch.*.php" -exec /usr/bin/php -q {} \; > /dev/null
	find $I/?/? -maxdepth 1 -type f -exec rm -f {} \;
done

