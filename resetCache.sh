#!/bin/bash

for I in `locate /config.patchwork.php | grep '/config\.patchwork\.php$' 2> /dev/null`
do
    if test -f $I
    then
        I=`dirname $I`
        touch $I/config.patchwork.php $I/.patchwork.lock 2> /dev/null
        rm -f $I/.patchwork.php $I/.*.zcache.php $I/.patchwork.lock 2> /dev/null &

        for J in $I/zcache/?/?
        do
            mv $J $J.old 2> /dev/null
        done

        rm -Rf $I/zcache/?/?.old 2> /dev/null &
        find $I/.~* ! -mtime -1 -delete 2> /dev/null &
    fi
done
