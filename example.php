<?php

header('Content-type: text/plain');
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);

include 'Logger.php';

Patchwork\Logger::start('./output', 'sid');

