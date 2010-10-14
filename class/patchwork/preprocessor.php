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
		$tokens = file_get_contents($source);

		if (   'patchwork_tokenizer' === $class
			|| 'patchwork_tokenizer_normalizer' === $class) return $tokens;

		$tokenizer = new patchwork_tokenizer_normalizer;

		$i = array(
			'className'         => 0 <= $level && $class,
			'stringTagger'      => true,
			'scoper'            => true,
			'constantInliner'   => true,
			'classInfo'         => true,
			'aliasing'          => true,
			'superPositioner'   => true,
			'constructorStatic' => true,
			'constructor4to5'   => 0 > $level,
			'globalizer'        => 0 <= $level,
			'scream'            => self::$scream,
			'T'                 => true,
			'marker'            => true,
		);

		foreach ($i as $count => $i)
		{
			if (!$i) continue;
			if ($class === $i = 'patchwork_tokenizer_' . $count) break;

			switch ($count)
			{
			case 'T':                 new $i($tokenizer); break;
			case 'scream':            new $i($tokenizer); break;
			case 'stringTagger':      new $i($tokenizer); break;
			case 'constructor4to5':   new $i($tokenizer); break;
			case 'className':         new $i($tokenizer, $class); break;
			case 'globalizer':        new $i($tokenizer, '$CONFIG'); break;
			case 'marker':            new $i($tokenizer, self::$declaredClass); break;
			case 'constructorStatic': new $i($tokenizer, $is_top ? $class : false); break;
			case 'constantInliner':   new $i($tokenizer, $source, self::$constants); break;
			case 'aliasing':          new $i($tokenizer, $GLOBALS['patchwork_preprocessor_alias'], self::$classAlias); break;
			case 'scoper':            $tokenizer = new $i($tokenizer); break;
			case 'classInfo':         $tokenizer = new $i($tokenizer); break;
			case 'superPositioner':   $tokenizer = new $i($tokenizer, $level, $is_top ? $class : false); break;
			}
		}

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
