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

// TODO tokenizer refactorize:
// - autoload marker


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
	public

	$source,
	$line,
	$level,
	$class,
	$isTop;


	protected $tokenFilter = array();


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

	$declaredClass = array('self' => 1, 'parent' => 1, 'static' => 1, 'p' => 1, 'patchwork' => 1),
	$inlineClass,
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
			self::$declaredClass[$v] = 1;
		}
	}

	static function execute($source, $destination, $level, $class, $is_top)
	{
		$source = patchwork_realpath($source);

		if (!self::$recursivePool || $class)
		{
			self::$recursivePool || self::$inlineClass = self::$declaredClass;
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
			$preproc->source = $source;
			$preproc->level  = $level;
			$preproc->class  = $class;
			$preproc->isTop  = $is_top;

			$code = file_get_contents($source);
			$code =& $preproc->preprocess($code);

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

	function pushFilter($filter)
	{
		array_unshift($this->tokenFilter, $filter);
	}

	function popFilter()
	{
		array_shift($this->tokenFilter);
	}

	protected function &preprocess(&$tokens)
	{
		$T = PATCHWORK_PATH_TOKEN;

		$level  = $this->level;
		$class  = $this->class;
		$is_top = $this->isTop;

		$tokenizer = new patchwork_tokenizer_normalizer;
		self::$scream && new patchwork_tokenizer_scream($tokenizer);
		0 <= $level && $class && new patchwork_tokenizer_className($tokenizer, $class);
		new patchwork_tokenizer_stringTagger($tokenizer);
		new patchwork_tokenizer_T($tokenizer);
		$tokenizer = new patchwork_tokenizer_scoper($tokenizer);
		0 <= $level && new patchwork_tokenizer_globalizer($tokenizer, '$CONFIG');
		new patchwork_tokenizer_constantInliner($tokenizer, $this->source, self::$constants);
		$tokenizer = new patchwork_tokenizer_classInfo($tokenizer);
		new patchwork_tokenizer_aliasing($tokenizer, $GLOBALS['patchwork_preprocessor_alias'], self::$classAlias);
		new patchwork_tokenizer_constructorStatic($tokenizer, $is_top ? $class : false);
		0 > $level && new patchwork_tokenizer_constructor4to5($tokenizer);
		$tokenizer = new patchwork_tokenizer_superPositioner($tokenizer, $level, $is_top ? $class : false);
		new patchwork_tokenizer_marker($tokenizer, $T);


		$tokens = $tokenizer->tokenize($tokens);
		$count = count($tokens);

		if ($tokenizer = $tokenizer->getError())
		{
			patchwork_error::handle(E_USER_ERROR, $tokenizer[0], $this->source, $tokenizer[1]);
		}

		// Add dummy tokens to avoid checking for edges
		$tokens[] = array(false);
		$tokens[] = array(false);
		$tokens[] = array(false);

		$static_instruction = false;
		$antePrevType = false;
		$prevType = false;
		$new_code = array();
		$new_type = array();
		$new_code_length = 0;

		$curly_level = 0;
		$curly_starts_function = false;
		$class_pool = array();
		$curly_marker = array(array(0, 0));
		$curly_marker_last =& $curly_marker[0];

		$type = T_INLINE_HTML;
		$i = 0;

		while ($i < $count)
		{
			list($type, $code) = $token = $tokens[$i] + array(2 => '', 0);

			// Reduce memory usage
			unset($tokens[$i++]);

			switch ($type)
			{
			case T_INTERFACE:
			case T_CLASS:
				if (!empty($token['class']))
				{
					$class_pool[$curly_level] = $c = $token['class'];

					self::$inlineClass[strtolower($c->name)] = 1;
					$c->extends && self::$inlineClass[strtolower($c->extends)] = 1;
				}

				break;

			case T_FUNCTION:
				$curly_starts_function = true;
				break;

			case T_NEW:
				$c = '';

				if (T_STRING === $tokens[$i][0])
				{
					$c = strtolower($tokens[$i][1]);
					if (isset(self::$inlineClass[$c])) break;
				}

				if ('' === $c)
				{
					0 < $curly_marker_last[1] || $curly_marker_last[1] =  1;
					$c = "\$a{$T}=\$b{$T}=\$e{$T}";
				}
				else
				{
					$curly_marker_last[1]   || $curly_marker_last[1] = -1;
					$c = $this->marker($c);
				}

				if ('&' === $prevType)
				{
					$j = $new_code_length - 1;

					if ('=' === $antePrevType)
					{
						$j -= 4;
						$antePrevType = '&';
					}
					else
					{
						$new_type[$j] = $prevType = T_WHITESPACE;
						$new_code[$j] = ' ';
						$code = "(({$c})?" . $code;
					}
				}
				else $code = "(({$c})?" . $code;

			case T_DOUBLE_COLON:
				if (T_DOUBLE_COLON === $type)
				{
					if (strspn($antePrevType, '(,') // To not break pass by ref, isset, unset and list
						|| $static_instruction
						|| isset($class_pool[$curly_level-1])
						|| isset(self::$inlineClass[$prevType])
					) break;

					$curly_marker_last[1] || $curly_marker_last[1] = -1;
					$c = $this->marker($prevType);
					$j = $new_code_length - 1;

					if ('&' === $antePrevType)
					{
						$j -= 4;
						if ('=' !== $new_type[$j]) break;
						$j -= 2;
					}
					else
					{
						while (isset($new_type[$j -= 2]) && in_array($new_type[$j], array(T_DEC, T_INC))) ;
						$j += 2;
						$new_code[$j] = "(({$c})?" . $new_code[$j];
					}
				}

				if ('&' === $antePrevType)
				{
					$b = 0;

					do switch ($new_type[$j])
					{
					case '$': if (!$b && $j += 2) while (isset($new_type[$j -= 2]) && '$' === $new_type[$j]) ;
					case T_VARIABLE:
						if (!$b)
						{
							$j -= 2;
							if (T_OBJECT_OPERATOR !== $new_type[$j] && T_DOUBLE_COLON !== $new_type[$j] && $j += 2) break 2;
						}
						break;

					case '{': case '[': --$b; break;
					case '}': case ']': ++$b; break;
					}
					while (isset($new_type[$j -= 2]));

					$new_code[$j] = "(({$c})?" . $new_code[$j];
				}

				new patchwork_preprocessor_marker($this);

				break;

			case T_STRING:
				$type = strtolower($code);

				if (T_USE_FUNCTION === $token[3] && isset(patchwork_tokenizer_aliasing::$autoloader[$type]))
				{
					$code = "((\$a{$T}=\$b{$T}=\$e{$T})||1?{$code}";
					$b = new patchwork_preprocessor_marker($this);
					$b->curly = -1;
					0 < $curly_marker_last[1] || $curly_marker_last[1] = 1;
				}
				break;

			case T_EVAL:
				if ('(' === $tokens[$i][0])
				{
					$code = "((\$a{$T}=\$b{$T}=\$e{$T})||1?{$code}";
					$b = new patchwork_preprocessor_marker($this);
					$b->curly = -1;
					0 < $curly_marker_last[1] || $curly_marker_last[1] = 1;
				}
				break;

			case T_REQUIRE_ONCE:
			case T_INCLUDE_ONCE:
			case T_REQUIRE:
			case T_INCLUDE:
				if (false !== $tokens[$i][0] && 0 <= $level)
				{
					$code .= "((\$a{$T}=\$b{$T}=\$e{$T})||1?";
					$b = new patchwork_preprocessor_marker($this);
					0 < $curly_marker_last[1] || $curly_marker_last[1] = 1;
				}
				break;

			case T_STATIC:
				$static_instruction = true;
				break;

			case ';':
				$curly_starts_function = false;
				$static_instruction = false;
				$new_type = array($new_code_length - 1 => false);
				break;

			case '{':
				isset($class_pool[$curly_level-1]) && $static_instruction = false;

				++$curly_level;

				if ($curly_starts_function)
				{
					$curly_starts_function = false;
					$curly_marker_last =& $curly_marker[$curly_level];
					$curly_marker_last = array($new_code_length + 1, 0);
				}

				break;

			case '}':
				$curly_starts_function = false;

				if (isset($curly_marker[$curly_level]))
				{
					$curly_marker_last[1] && $new_code[$curly_marker_last[0]] .= $curly_marker_last[1]>0
						? "global \$a{$T},\$b{$T},\$c{$T};static \$d{$T}=1;(" . $this->marker() . ")&&\$d{$T}&&\$d{$T}=0;"
						: "global \$a{$T},\$c{$T};";

					unset($curly_marker[$curly_level]);
					end($curly_marker);
					$curly_marker_last =& $curly_marker[key($curly_marker)];
				}

				--$curly_level;

				if (isset($class_pool[$curly_level]))
				{
					$code .= "\$GLOBALS['c{$T}']['{$class_pool[$curly_level]->realName}']=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "';";

					unset($class_pool[$curly_level]);
				}

				break;
			}

			foreach ($this->tokenFilter as $filter) $code = call_user_func($filter, $type, $code);

			$antePrevType = $prevType;
			$prevType = $type;

			$new_code[] = $token[2];
			$new_code[] = $code;
			$new_type[] = false;
			$new_type[] = $type;
			$new_code_length += 2;
		}

		if (T_CLOSE_TAG !== $type && T_INLINE_HTML !== $type)
		{
			$new_code[] = '';
			$new_code[] = '?'.'>';
		}

		return $new_code;
	}

	protected function marker($class = '')
	{
		return ($class ? 'isset($c' . PATCHWORK_PATH_TOKEN . "['" . strtolower($class) . "'])||" : ('$e' . PATCHWORK_PATH_TOKEN . '=$b' . PATCHWORK_PATH_TOKEN . '=')) . '$a' . PATCHWORK_PATH_TOKEN . "=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "'";
	}

	protected function extractLF_callback($a)
	{
		return str_repeat("\n", substr_count($a[0], "\n"));
	}
}
