<?php

define('PATCHWORK_BOOTPATH', '../variations/hello'); // XXX: Put the path to your application here

$a = include file_exists(PATCHWORK_BOOTPATH . '/.patchwork.php')
	? PATCHWORK_BOOTPATH . '/.patchwork.php'
	: '../patchwork.php'; // XXX: Put the path to patchwork's patchwork.php here

$a || die("Failed inclusion of patchwork's patchwork.php");
