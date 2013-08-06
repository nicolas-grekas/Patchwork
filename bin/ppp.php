<?php

use Patchwork\PPP as ppp;

require __DIR__ . '/../class/Patchwork/PPP/AbstractStreamProcessor.php';
require __DIR__ . '/../class/Patchwork/PPP/Preprocessor.php';
require __DIR__ . '/../class/Patchwork/PPP/ShebangPreprocessor.php';
require __DIR__ . '/../class/Patchwork/PPP.php';

require ppp::processedFile(dirname(__DIR__) . '/bootup.shim.php');

if (isset($_SERVER['SCRIPT_FILENAME'][0])) require ppp::shebangProcessedFile($_SERVER['SCRIPT_FILENAME']);
else eval('?>' . file_get_contents(ppp::filterProcessedFile('php://stdin')));

exit;
