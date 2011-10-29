<?php // vi: set fenc=utf-8 ts=4 sw=4 et:

#patchwork __patchwork__/pieces/toolbox

// By default, Poor Man's Cron is enabled.
// But it is better to use some external pingger like
// cron, scheluded tasks, webcron.org, cronjob.de, etc.
// and set this to false.

$CONFIG += array(
    'poorMansCron' => true,
);
