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
		if (   'patchwork_tokenizer' === $class
			|| 'patchwork_tokenizer_normalizer' === $class) return file_get_contents($source);

		$t = new patchwork_tokenizer_normalizer;
		$p = array('normalizer' => $t);

		$i = array(
			'classAutoname'      => 0 <= $level && $class,
			'stringInfo'         => true,
			'namespaceInfo'      => true,
			'scoper'             => true,
			'constFuncDisabler'  => 0 <= $level,
			'constFuncResolver'  => true,
			'namespaceResolver'  => version_compare(PHP_VERSION, '5.3.0') < 0,
			'constantInliner'    => true,
			'classInfo'          => true,
			'namespaceRemover'   => version_compare(PHP_VERSION, '5.3.0') < 0,
			'constantExpression' => true,
			'superPositioner'    => true,
			'constructorStatic'  => true,
			'constructor4to5'    => 0 > $level,
			'functionAliasing'   => true,
			'globalizer'         => 0 <= $level,
			'scream'             => self::$scream,
			'T'                  => DEBUG,
			'marker'             => !DEBUG,
			'staticState'        => 0 <= $level,
		);

		foreach ($i as $c => $i)
		{
			if (!$i) continue;
			if (!class_exists($i = 'patchwork_tokenizer_' . $c, true)) break;

			switch ($c)
			{
			default:                 $p[$c] = new $i($t); break;
			case 'classAutoname':    $p[$c] = new $i($t, $class); break;
			case 'globalizer':       $p[$c] = new $i($t, '$CONFIG'); break;
			case 'marker':           $p[$c] = new $i($t, self::$declaredClass); break;
			case 'constantInliner':  $p[$c] = new $i($t, $source, self::$constants); break;
			case 'namespaceRemover': $p[$c] = new $i($t, 'patchwork_alias_class::add'); break;
			case 'superPositioner':  $p[$c] = new $i($t, $level, $is_top ? $class : false); break;
			case 'functionAliasing': $p[$c] = new $i($t, $GLOBALS['patchwork_preprocessor_alias']); break;
			}
		}

		$code = $t->parse(file_get_contents($source));

		if ($c = $t->getErrors())
		{
			foreach ($c as $c)
				patchwork_error::handle($c[3], $c[0], $source, $c[1]);
		}

		if (isset($p['staticState']))
		{
			self::evalbox($p['staticState']->getStaticCode($code));
			return $p['staticState']->getRuntimeCode();
		}

		return implode('', $code);
	}

	protected static function evalbox($code)
	{
		return eval('unset($code);' . $code);
	}
}
