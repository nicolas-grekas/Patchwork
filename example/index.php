<?php

chdir('../variations/hello'); // XXX: chdir to the directory of the application

require file_exists('./.patchwork.php')
	? './.patchwork.php'
	: '../patchwork.php'; // XXX: path to patchwork's patchwork.php
