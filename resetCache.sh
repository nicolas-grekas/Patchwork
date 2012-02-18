#!/bin/bash

for I in `locate /config.patchwork.php | grep -v \\\.svn -v 2> /dev/null`
do
    if test -f $I
    then
        I=`dirname $I`
        rm -f $I/.*.zcache.php 2> /dev/null

        if test -d $I/zcache
        then
            rm -Rf $I/zcache/*/*/
        fi

        touch $I/config.patchwork.php
        rm -f $I/.*.zcache.php 2> /dev/null
        rm -f $I/.~* 2> /dev/null
        rm -f $I/.patchwork.lock $I/.patchwork.overrides.ser $I/.patchwork.php 2> /dev/null
    fi
done
