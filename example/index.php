<?php

// Set the path to your patchwork application directory:
define('PATCHWORK_BOOTPATH', '../variations/hello');

// Set the path to patchwork's bootstrapper.php file:
$a = '../bootstrapper.php';


$a = include file_exists(PATCHWORK_BOOTPATH . '/.patchwork.php')
	? PATCHWORK_BOOTPATH . '/.patchwork.php' : $a;
$a || die("Failed inclusion of patchwork's bootstrapper.php");
