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
// - construct static
// - app. inhertiance : intercept includes + patchworkPath() fourth arg
// - class superpositioner : intercept class_exists / interface_exists
// - function and class aliasing
// - translator pre-caching


require_once patchworkPath('class/patchwork/tokenizer.php');
require_once patchworkPath('class/patchwork/tokenizer/normalizer.php');
require_once patchworkPath('class/patchwork/tokenizer/scream.php');
require_once patchworkPath('class/patchwork/tokenizer/className.php');
require_once patchworkPath('class/patchwork/tokenizer/stringTagger.php');
require_once patchworkPath('class/patchwork/tokenizer/scoper.php');
require_once patchworkPath('class/patchwork/tokenizer/globalizer.php');
require_once patchworkPath('class/patchwork/tokenizer/constantInliner.php');
require_once patchworkPath('class/patchwork/tokenizer/classInfo.php');
require_once patchworkPath('class/patchwork/tokenizer/constructor4to5.php');
require_once patchworkPath('class/patchwork/tokenizer/superPositioner.php');


class patchwork_preprocessor__0
{
	public

	$source,
	$line,
	$level,
	$class,
	$isTop,
	$marker;


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
	),

	$functionAlias = array();


	protected static

	$declaredClass = array('self' => 1, 'parent' => 1, 'this' => 1, 'static' => 1, 'p' => 1, 'patchwork' => 1),
	$inlineClass,
	$recursivePool = array(),
	$callback;


	static function __constructStatic()
	{
		self::$scream = (defined('DEBUG') && DEBUG)
			&& !empty($GLOBALS['CONFIG']['debug.scream'])
				|| (defined('DEBUG_SCREAM') && DEBUG_SCREAM);

		$v = get_defined_functions();

		foreach ($v['user'] as $v)
		{
			$v = strtolower($v);
			if (0 !== strncmp($v, '__patchwork_', 12)) continue;
			self::$functionAlias[substr($v, 12)] = $v;
		}

		foreach ($GLOBALS['patchwork_preprocessor_alias'] as $k => $v)
		{
			function_exists('__patchwork_' . $k) && self::$functionAlias[$k] = $v;
		}

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
			$preproc->marker = array(
				'global $a' . PATCHWORK_PATH_TOKEN . ',$c' . PATCHWORK_PATH_TOKEN . ';',
				'global $a' . PATCHWORK_PATH_TOKEN . ',$b' . PATCHWORK_PATH_TOKEN . ',$c' . PATCHWORK_PATH_TOKEN . ';'
			);

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
		$tokenizer = new patchwork_tokenizer_scoper($tokenizer);
		0 <= $level && new patchwork_tokenizer_globalizer($tokenizer, '$CONFIG');
		new patchwork_tokenizer_constantInliner($tokenizer, $this->source, self::$constants);
		$tokenizer = new patchwork_tokenizer_classInfo($tokenizer);
		0 > $level && new patchwork_tokenizer_constructor4to5($tokenizer);
		$tokenizer = new patchwork_tokenizer_superPositioner($tokenizer, $level, $is_top ? 'c' . $T : false);


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

		$opentag_marker = "if(!isset(\$a{$T})){global " . substr($this->marker[1], 7) . "}isset(\$e{$T})||\$e{$T}=false;";

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
			case T_OPEN_TAG:
				if ($opentag_marker)
				{
					$code .= $opentag_marker;
					$opentag_marker = '';
				}
				break;

			case '(':
				if (   ('}' === $prevType || T_VARIABLE === $prevType)
					&& !in_array($antePrevType, array(T_NEW, T_OBJECT_OPERATOR, T_DOUBLE_COLON)) )
				{
					$j = $new_code_length - 1;

					if (T_VARIABLE === $prevType && '$' !== $antePrevType)
					{
						if ('this' !== strtolower($b = substr($new_code[$j], 1)))
						{
							$new_code[$j] = "\${is_string(\${$b})&&function_exists(\$v{$T}='__patchwork_'.\${$b})?'v{$T}':'{$b}'}";
						}
					}
					else
					{
						if ($b = '}' === $prevType ? 1 : 0)
						{
							$c = array($j, 0);

							while ($b && isset($new_type[$j -= 2]))
							{
								if ('{' === $new_type[$j]) --$b;
								else if ('}' === $new_type[$j]) ++$b;
							}

							$c[1] = $j;
							$j -= 2;

							if ('$' !== $new_type[$j]) break;
						}
						else $c = 0;

						while (isset($new_type[$j -= 2]) && '$' === $new_type[$j]) ;

						if (in_array($new_type[$j], array(T_NEW, T_OBJECT_OPERATOR, T_DOUBLE_COLON))) break;

						$j += 2;

						$c && $new_code[$c[0]] = $new_code[$c[1]] = '';

						$new_code[$j] = "\${is_string(\$k{$T}=";
						$new_code[$new_code_length-1] .= ")&&function_exists(\$v{$T}='__patchwork_'.\$\$k{$T})?'v{$T}':\$k{$T}}";
					}
				}
				break;

			case T_INTERFACE:
			case T_CLASS:
				$b = $c = '';

				if (T_STRING !== $tokens[$i][0]) break;

				$code .= $tokens[$i][2] . $tokens[$i][1];
				unset($tokens[$i++]);

				$c = $token['class'];
				$c->addConstructStatic = 0;
				$c->addDestructStatic  = 0;

				$class_pool[$curly_level] = $c;

				self::$inlineClass[strtolower($c->name)] = 1;

				$c = '';

				if (T_EXTENDS === $tokens[$i][0])
				{
					$code .= $tokens[$i][2] . $tokens[$i][1];
					unset($tokens[$i++]);

					if (T_STRING === $tokens[$i][0])
					{
						$code .= $tokens[$i][2];
						$code .= $c = $tokens[$i][1];
						unset($tokens[$i++]);

						$c = strtolower($c);

						if (isset(self::$declaredClass[$c]) && 'patchwork' !== $c && 'p' !== $c)
						{
							$class_pool[$curly_level]->addConstructStatic = 2;
							$class_pool[$curly_level]->addDestructStatic  = 2;
						}

						self::$inlineClass[$c] = 1;
					}
				}
				else
				{
					if (T_CLASS === $type)
					{
						$class_pool[$curly_level]->addConstructStatic = 2;
						$class_pool[$curly_level]->addDestructStatic  = 2;
					}
				}

				break;

			case T_FUNCTION:
				$curly_starts_function = true;
				break;

			case T_NEW:
				$c = '';

				if (T_STRING === $tokens[$i][0])
				{
					$c = strtolower($tokens[$i][0]);
					empty(self::$classAlias[$c]) || $c = self::$classAlias[$c];
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

				switch ($token[3])
				{
				case T_USE_PROPERTY:
				case T_USE_METHOD:
				case T_USE_CONST:
					break 2;

				case T_NAME_FUNCTION:
					if (isset($class_pool[$curly_level-1]) && $c = $class_pool[$curly_level-1])
					{
						switch ($type)
						{
						case '__constructstatic': $c->addConstructStatic = 1 ; break;
						case '__destructstatic' : $c->addDestructStatic  = 1 ; break;
						}
					}

					break;

				case T_USE_CLASS:
					empty(self::$classAlias[$type]) || $code = self::$classAlias[$type];
					break;

				case T_USE_FUNCTION:
					if (isset(self::$functionAlias[$type]))
					{
						$j = self::$functionAlias[$type];

						if (0 !== strncasecmp($j, $class . '::', strlen($class)+2))
						{
							$j = explode('::', $j, 2);

							if (2 === count($j))
							{
								$tokens[--$i] = array(T_STRING, $j[1], '', T_USE_METHOD);
								$tokens[--$i] = array(T_DOUBLE_COLON, '::');
								$tokens[--$i] = array(T_STRING, $j[0], $token[2], T_USE_CLASS);

								continue 3;
							}
							else
							{
								$code = $j[0];
								$type = strtolower($code);
							}
						}
					}

					switch ($type)
					{
					case 'patchworkpath':
						// Append its fourth arg to patchworkPath
						if (0 <= $level) new patchwork_preprocessor_path($this);
						break;

					case 't':
						if (0 <= $level)
						{
							$j = $i;
							$j = !DEBUG && TURBO ? patchwork_tokenizer::fetchConstantCode($tokens, $j, $count, $b) : null;

							if (null === $j)
							{
								// new patchwork_preprocessor_t($this); // TODO: update then re-enable me
							}
							else if ($_SERVER['PATCHWORK_LANG'])
							{
								// Add the string to the translation table
								TRANSLATOR::get($b, $_SERVER['PATCHWORK_LANG'], false);
							}
						}
						break;

					default:
						if (!isset(self::$callback) && 0 !== strncmp($class, 'patchwork_preprocessor_', 23))
						{
							self::$callback =& patchwork_preprocessor_callback::$list;
						}

						if (!isset(self::$callback[$type])) break;

						$code = "((\$a{$T}=\$b{$T}=\$e{$T})||1?{$code}";
						$b = new patchwork_preprocessor_callback($this, $type);
						$b->curly = -1;
						0 < $curly_marker_last[1] || $curly_marker_last[1] = 1;

						if ('&' === $prevType)
						{
							$j = $new_code_length - 1;
							$new_code[$j] = ' ';
							$new_type[$j] = T_WHITESPACE;
						}

						// For files in the include_path, always set the 2nd arg of class|interface_exists() to true
						if (0 > $level && in_array($type, array('interface_exists', 'class_exists'))) new patchwork_preprocessor_classExists($this);
					}

					break;
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

				// Every require|include inside files in the include_path
				// is preprocessed thanks to patchworkProcessedPath().
				if (false !== $tokens[$i][0])
				{
					if (0 > $level)
					{
						$j = !DEBUG && TURBO ? patchwork_tokenizer::fetchConstantCode($tokens, $i, $count, $b) : null;

						if (null !== $j)
						{
							$b = patchworkProcessedPath($b);

							if (false === $b) $c = "patchworkProcessedPath({$j})";
							else
							{
								$c = substr_count($j, "\n");
								$c = patchwork_tokenizer::export($b, $c);
							}

							$tokens[--$i] = array(T_CONSTANT_ENCAPSED_STRING, $c, ' ');
						}
						else
						{
							$code .= ' patchworkProcessedPath(';
							new patchwork_preprocessor_require($this);
						}
					}
					else
					{
						$code .= "((\$a{$T}=\$b{$T}=\$e{$T})||1?";
						$b = new patchwork_preprocessor_require($this);
						$b->close = ':0)';
						0 < $curly_marker_last[1] || $curly_marker_last[1] = 1;
					}
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
						? "{$this->marker[1]}static \$d{$T}=1;(" . $this->marker() . ")&&\$d{$T}&&\$d{$T}=0;"
						: $this->marker[0];

					unset($curly_marker[$curly_level]);
					end($curly_marker);
					$curly_marker_last =& $curly_marker[key($curly_marker)];
				}

				--$curly_level;

				if (isset($class_pool[$curly_level]))
				{
					$c = $class_pool[$curly_level];
					$j = strtolower($c->name);

					if ($c->addConstructStatic)
					{
						$code = "const __cS{$T}=" . (1 === $c->addConstructStatic ? "'{$j}';" : "'';static function __constructStatic(){}") . $code;
					}

					if ($c->addDestructStatic)
					{
						$code = "const __dS{$T}=" . (1 === $c->addDestructStatic  ? "'{$j}';" : "'';static function __destructStatic() {}") . $code;
					}

					$code .= "\$GLOBALS['c{$T}']['{$c->realName}']=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "';";

					if ($is_top && strtolower($class) === $j)
					{
						if (!$c->extends)
						{
							1 === $c->addConstructStatic && $code .= "{$j}::__constructStatic();";
							1 === $c->addDestructStatic  && $code .= "\$GLOBALS['patchwork_destructors'][]='{$j}';";
						}
						else
						{
							2 !== $c->addConstructStatic && $code .= "if('{$j}'==={$j}::__cS{$T}){$j}::__constructStatic();";
							2 !== $c->addDestructStatic  && $code .= "if('{$j}'==={$j}::__dS{$T})\$GLOBALS['patchwork_destructors'][]='{$j}';";
						}
					}

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
