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


class patchwork_preprocessor__0
{
	static

	$scream = false,
	$constants = array(
		'DEBUG', 'IS_WINDOWS', 'PATCHWORK_ZCACHE', 'PATCHWORK_PATH_TOKEN',
		'PATCHWORK_PATH_LEVEL', 'PATCHWORK_PATH_OFFSET', 'PATCHWORK_PROJECT_PATH',
		'E_DEPRECATED', 'E_USER_DEPRECATED', 'E_RECOVERABLE_ERROR',
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
		$tokens = file_get_contents($source);

		if (   'patchwork_tokenizer' === $class
			|| 'patchwork_tokenizer_normalizer' === $class) return $tokens;

		$t = new patchwork_tokenizer_normalizer;

		$i = array(
			'className'         => 0 <= $level && $class,
			'stringInfo'        => true,
			'namespaceInfo'     => true,
			'scoper'            => true,
			'constantInliner'   => true,
			'classInfo'         => true,
			'superPositioner'   => true,
			'constructorStatic' => true,
			'constructor4to5'   => 0 > $level,
			'functionAliasing'  => true,
			'globalizer'        => 0 <= $level,
			'scream'            => self::$scream,
			'T'                 => DEBUG,
			'marker'            => !DEBUG,
		);

		foreach ($i as $c => $i)
		{
			if (!$i) continue;
			if (!class_exists($i = 'patchwork_tokenizer_' . $c, true)) break;

			switch ($c)
			{
			default:                 $t = new $i($t); break;
			case 'className':        $t = new $i($t, $class); break;
			case 'globalizer':       $t = new $i($t, '$CONFIG'); break;
			case 'marker':           $t = new $i($t, self::$declaredClass); break;
			case 'constantInliner':  $t = new $i($t, $source, self::$constants); break;
			case 'superPositioner':  $t = new $i($t, $level, $is_top ? $class : false); break;
			case 'functionAliasing': $t = new $i($t, $GLOBALS['patchwork_preprocessor_alias']); break;
			}
		}

		$tokens = $t->tokenize($tokens);

		if ($t = $t->getError())
		{
			patchwork_error::handle(E_USER_ERROR, $t[0], $source, $t[1]);
		}

		$i = 0;
		$c = count($tokens);

		while ($i < $c)
		{
			$tokens[$i] = (isset($tokens[$i][2]) ? $tokens[$i][2] : '') . $tokens[$i][1];
			++$i;
		}

		return implode('', $tokens);
	}
}
