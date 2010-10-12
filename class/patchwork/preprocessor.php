<?php /*********************************************************************
 *
 *   Copyright : (C) 2010 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


require_once patchworkPath('class/patchwork/tokenizer.php');
require_once patchworkPath('class/patchwork/tokenizer/normalizer.php');
require_once patchworkPath('class/patchwork/tokenizer/scream.php');
require_once patchworkPath('class/patchwork/tokenizer/className.php');
require_once patchworkPath('class/patchwork/tokenizer/stringTagger.php');
require_once patchworkPath('class/patchwork/tokenizer/T.php');
require_once patchworkPath('class/patchwork/tokenizer/scoper.php');
require_once patchworkPath('class/patchwork/tokenizer/globalizer.php');
require_once patchworkPath('class/patchwork/tokenizer/constantInliner.php');
require_once patchworkPath('class/patchwork/tokenizer/classInfo.php');
require_once patchworkPath('class/patchwork/tokenizer/aliasing.php');
require_once patchworkPath('class/patchwork/tokenizer/constructorStatic.php');
require_once patchworkPath('class/patchwork/tokenizer/constructor4to5.php');
require_once patchworkPath('class/patchwork/tokenizer/superPositioner.php');
require_once patchworkPath('class/patchwork/tokenizer/marker.php');


class patchwork_preprocessor__0
{
	static

	$scream = false,
	$constants = array(
		'DEBUG', 'IS_WINDOWS', 'PATCHWORK_ZCACHE',
		'PATCHWORK_PATH_LEVEL', 'PATCHWORK_PATH_OFFSET',
		'E_DEPRECATED', 'E_USER_DEPRECATED', 'E_RECOVERABLE_ERROR',
	),
	$classAlias = array(
		'p' => 'patchwork',
		's' => 'SESSION',
	);


	protected static

	$declaredClass = array('p', 'patchwork'),
	$recursivePool = array();


	static function __constructStatic()
	{
		self::$scream = (defined('DEBUG') && DEBUG)
			&& !empty($GLOBALS['CONFIG']['debug.scream'])
				|| (defined('DEBUG_SCREAM') && DEBUG_SCREAM);

		foreach (get_declared_classes() as $v)
		{
			$v = strtolower($v);
			if (false !== strpos($v, 'patchwork')) continue;
			if ('p' === $v) break;
			self::$declaredClass[] = $v;
		}
	}

	static function execute($source, $destination, $level, $class, $is_top)
	{
		$source = patchwork_realpath($source);

		if (!self::$recursivePool || $class)
		{
			$pool = array($source => array($destination, $level, $class, $is_top));
			self::$recursivePool[] =& $pool;
		}
		else
		{
			$pool =& self::$recursivePool[count(self::$recursivePool)-1];
			$pool[$source] = array($destination, $level, $class, $is_top);

			return;
		}

		while (list($source, list($destination, $level, $class, $is_top)) = each($pool))
		{
			$preproc = new patchwork_preprocessor;

			$code = $preproc->preprocess($source, $level, $class, $is_top);

			$tmp = PATCHWORK_PROJECT_PATH . '.~' . uniqid(mt_rand(), true);
			if (false !== file_put_contents($tmp, $code))
			{
				if (IS_WINDOWS)
				{
					$code = new COM('Scripting.FileSystemObject');
					$code->GetFile($tmp)->Attributes |= 2; // Set hidden attribute
					file_exists($destination) && @unlink($destination);
					@rename($tmp, $destination) || unlink($tmp);
				}
				else rename($tmp, $destination);
			}
		}

		array_pop(self::$recursivePool);
	}

	static function isRunning()
	{
		return count(self::$recursivePool);
	}

	protected function __construct() {}

	protected function preprocess($source, $level, $class, $is_top)
	{
		$tokenizer = new patchwork_tokenizer_normalizer;
		self::$scream && new patchwork_tokenizer_scream($tokenizer);
		0 <= $level && $class && new patchwork_tokenizer_className($tokenizer, $class);
		new patchwork_tokenizer_stringTagger($tokenizer);
		new patchwork_tokenizer_T($tokenizer);
		$tokenizer = new patchwork_tokenizer_scoper($tokenizer);
		0 <= $level && new patchwork_tokenizer_globalizer($tokenizer, '$CONFIG');
		new patchwork_tokenizer_constantInliner($tokenizer, $source, self::$constants);
		$tokenizer = new patchwork_tokenizer_classInfo($tokenizer);
		new patchwork_tokenizer_aliasing($tokenizer, $GLOBALS['patchwork_preprocessor_alias'], self::$classAlias);
		new patchwork_tokenizer_constructorStatic($tokenizer, $is_top ? $class : false);
		0 > $level && new patchwork_tokenizer_constructor4to5($tokenizer);
		$tokenizer = new patchwork_tokenizer_superPositioner($tokenizer, $level, $is_top ? $class : false);
		new patchwork_tokenizer_marker($tokenizer, self::$declaredClass);

		$tokens = file_get_contents($source);
		$tokens = $tokenizer->tokenize($tokens);

		if ($tokenizer = $tokenizer->getError())
		{
			patchwork_error::handle(E_USER_ERROR, $tokenizer[0], $source, $tokenizer[1]);
		}

		$i = 0;
		$count = count($tokens);

		while ($i < $count)
		{
			$tokens[$i] = (isset($tokens[$i][2]) ? $tokens[$i][2] : '') . $tokens[$i][1];
			++$i;
		}

		return implode('', $tokens);
	}
}
