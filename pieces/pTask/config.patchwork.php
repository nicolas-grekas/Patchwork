<?php // vi: set encoding=utf-8 expandtab shiftwidth=4:

#patchwork ../toolbox

// By default, Poor Man's Cron is enabled.
// But it is better to use some external pingger like
// cron, scheluded tasks, webcron.org, cronjob.de, etc.
// and set this to false.

$CONFIG += array(
    'poorMansCron' => true,
);
