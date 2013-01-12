<?php

namespace Patchwork\PPP;

require __DIR__ . '/../class/Patchwork/PPP/AbstractStreamProcessor.php';
require __DIR__ . '/../class/Patchwork/PPP/Preprocessor.php';
require __DIR__ . '/../class/Patchwork/PPP/ShebangPreprocessor.php';

require Preprocessor::register() . dirname(__DIR__) . '/bootup.shim.php';

if (isset($_SERVER['SCRIPT_FILENAME'][0])) require ShebangPreprocessor::register() . $_SERVER['SCRIPT_FILENAME'];
else eval('?>' . file_get_contents(Preprocessor::register() . 'php://stdin'));

exit;
