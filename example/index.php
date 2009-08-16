<?php


// Set the path to your patchwork application directory:
$a = '../variations/hello';

	define('PATCHWORK_BOOTPATH', $a);


// Set the path to patchwork's bootstrapper.php file:
$a = '../bootstrapper.php';

	$a = include file_exists(PATCHWORK_BOOTPATH . '/.patchwork.php')
		? PATCHWORK_BOOTPATH . '/.patchwork.php' : $a;
	$a || die("Failed inclusion of patchwork's bootstrapper.php");
