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

	$constant = array(
		'DEBUG' => DEBUG,
		'UTF8_BOM' => UTF8_BOM,
		'IS_WINDOWS' => IS_WINDOWS,
		'PATCHWORK_ZCACHE' => PATCHWORK_ZCACHE,
		'PATCHWORK_PATH_LEVEL' => PATCHWORK_PATH_LEVEL,
		'PATCHWORK_PATH_OFFSET' => PATCHWORK_PATH_OFFSET,
	),

	$classAlias = array(
		'p' => 'patchwork',
		's' => 'SESSION',
	),

	$functionAlias = array();


	protected static

	$declaredClass = array('self' => 1, 'parent' => 1, 'this' => 1, 'static' => 1, 'p' => 1, 'patchwork' => 1),
	$variableType = array(
		T_EVAL, '(', T_LINE, T_FILE, T_DIR, T_FUNC_C, T_CLASS_C, T_METHOD_C, T_NS_C, T_INCLUDE, T_REQUIRE,
		T_CURLY_OPEN, T_VARIABLE, '$', T_INCLUDE_ONCE, T_REQUIRE_ONCE, T_DOLLAR_OPEN_CURLY_BRACES, T_EXIT,
	),
	$inlineClass,
	$recursivePool = array(),
	$callback;


	static function __constructStatic()
	{
		self::$scream = (defined('DEBUG') && DEBUG)
			&& !empty($GLOBALS['CONFIG']['debug.scream'])
				|| (defined('DEBUG_SCREAM') && DEBUG_SCREAM);

		defined('E_RECOVERABLE_ERROR') || self::$constant['E_RECOVERABLE_ERROR'] = E_ERROR;

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

		$v = get_defined_constants(true);
		unset(
			$v['user'],
			$v['standard']['INF'],
			$v['internal']['TRUE'],
			$v['internal']['FALSE'],
			$v['internal']['NULL'],
			$v['internal']['PHP_EOL']
		);

		foreach ($v as $v) self::$constant += $v;

		foreach (self::$constant as &$v) $v = patchwork_preprocessor::export($v);
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
			0 === strncmp($code, UTF8_BOM, 3) && $code = substr($code, 3);

			if (!preg_match('//u', $code)) patchwork_preprocessor::error("File encoding is not valid UTF-8.", $source, 0);

			$preproc->antePreprocess($code);
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

	protected function antePreprocess(&$code)
	{
		if (false !== strpos($code, "\r")) $code = strtr(str_replace("\r\n", "\n", $code), "\r", "\n");
		if (false !== strpos($code,  '#>>>')) $code = preg_replace_callback("'^#>>>[^\n]*\n.*?^#<<<[^\n]*'ms", array($this, 'extractLF_callback'), $code);

		if (DEBUG)
		{
			if (false !== strpos($code,  '#>')) $code = preg_replace("'^#>([^>\n].*)$'m", '$1', $code);
			if (false !== strpos($code, '/*<')) $code = preg_replace("'^(/\*<[^\n]*)(\n.*?)^>\*/'ms", '$1*/$2', $code);
		}
	}

	protected function &preprocess(&$tokens)
	{
		$source = $this->source;
		$level  = $this->level;
		$class  = $this->class;
		$is_top = $this->isTop;
		$line   =& $this->line;

		$tokens = patchwork_tokenizer::getAll($tokens, true);
		$count = count($tokens);

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

		$T = PATCHWORK_PATH_TOKEN;
		$opentag_marker = "if(!isset(\$a{$T})){global \$CONFIG," . substr($this->marker[1], 7) . "}isset(\$e{$T})||\$e{$T}=false;";

		$curly_level = 0;
		$curly_starts_function = false;
		$autoglobalize = 0;
		$class_pool = array();
		$curly_marker = array(array(0, 0));
		$curly_marker_last =& $curly_marker[0];

		$type = T_INLINE_HTML;

		for ($i = 0; $i < $count; ++$i)
		{
			list($type, $code, $line, $sugar) = $tokens[$i];

			// Reduce memory usage
			unset($tokens[$i]);

			switch ($type)
			{
			case '@':
				if (self::$scream) continue 2;
				break;

			case T_OPEN_TAG:
				if ($opentag_marker)
				{
					$code .= $opentag_marker;
					$opentag_marker = '';
				}
				break;

			case T_FILE:
				$code = patchwork_preprocessor::export($source);
				$type = T_CONSTANT_ENCAPSED_STRING;
				break;

			case T_DIR:
				$code = patchwork_preprocessor::export(dirname($source));
				$type = T_CONSTANT_ENCAPSED_STRING;
				break;

			case T_CLASS_C:
				if ($class_pool)
				{
					$code = "'" . end($class_pool)->classname . "'";
					$type = T_CONSTANT_ENCAPSED_STRING;
				}
				break;

			case T_METHOD_C:
				if ($class_pool)
				{
					// XXX PB WITH STATIC INSTRUCTIONS !!!
					$code = "('" . end($class_pool)->classname . "::'.__FUNCTION__)";
					$type = T_CONSTANT_ENCAPSED_STRING;
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

							while ($b && isset($new_type[$j -= 2])) switch ($new_type[$j])
							{
							case T_CURLY_OPEN:
							case T_DOLLAR_OPEN_CURLY_BRACES:
							case '{': --$b; break;
							case '}': ++$b; break;
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

				$final = T_FINAL === $prevType;

				if (T_STRING === $tokens[$i+1][0])
				{
					$code .= $tokens[++$i][3];

					$b = $c = $tokens[$i][1];

					if ($final) $code .= $c;
					else $code .= $b = $c . '__' . (0<=$level ? $level : '00');
				}
				else if ($class && 0<=$level)
				{
					$c = $class;
					$b = $c . (!$final ? '__' . $level : '');
					$code .= ' ' . $b;
				}
				else patchwork_preprocessor::error("Please specify explicitly the name of the class.", $source, $line);

				$class_pool[$curly_level] = (object) array(
					'classname'   => $c,
					'classkey'    => strtolower($b),
					'is_child'    => false,
					'is_final'    => $final,
					'is_abstract' => T_ABSTRACT === $prevType,
					'add_php5_construct'  => T_CLASS === $type && 0 > $level,
					'add_constructStatic' => 0,
					'add_destructStatic'  => 0,
					'construct_source'    => '',
				);

				if (T_ABSTRACT === $prevType)
				{
					$j = $new_code_length - 1;
					$new_code[$j] = "\$GLOBALS['patchwork_abstract']['{$class_pool[$curly_level]->classkey}']=1;" . $new_code[$j];
				}

				self::$inlineClass[strtolower($c)] = 1;

				if ($c && T_EXTENDS === $tokens[$i+1][0])
				{
					$code .= $tokens[++$i][3] . $tokens[$i][1];

					if (T_STRING === $tokens[$i+1][0])
					{
						$code .= $tokens[++$i][3];
						$class_pool[$curly_level]->is_child = $tokens[$i][1];
						$c = 0 <= $level && 'self' === $tokens[$i][1] ? $c . '__' . ($level ? $level - 1 : '00') : $tokens[$i][1];
						$code .= $c;

						$c = strtolower($c);

						if (isset(self::$declaredClass[$c]) && 'patchwork' !== $c && 'p' !== $c)
						{
							$class_pool[$curly_level]->add_constructStatic = 2;
							$class_pool[$curly_level]->add_destructStatic  = 2;
						}

						self::$inlineClass[$c] = 1;
					}
				}
				else
				{
					if (T_CLASS === $type)
					{
						$class_pool[$curly_level]->add_constructStatic = 2;
						$class_pool[$curly_level]->add_destructStatic  = 2;
					}
				}

				break;

			case T_VAR:
				if (0>$level)
				{
					$code = 'public';
					$type = T_PUBLIC;
				}

				break;

			case T_PRIVATE:
				// "private static" methods or properties are problematic when considering application inheritance.
				// To work around this, we change them to "protected static", and warn about it
				// (except for files in the include path). Side effects exist but should be rare.
				if (isset($class_pool[$curly_level-1]) && !$class_pool[$curly_level-1]->is_final)
				{
					// Look backward and forward for the "static" keyword
					if (T_STATIC === $prevType || T_STATIC === $tokens[$i+1][0])
					{
						$code = 'protected';
						$type = T_PROTECTED;

						if (0 <= $level) patchwork_preprocessor::error("Private static methods or properties are banned, please use protected static ones instead.", $source, $line);
					}
				}

				break;

			case T_FUNCTION:
				$curly_starts_function = true;
				$autoglobalize = 0;
				break;

			case T_NEW:
				$c = '';

				if (T_STRING === $tokens[$i+1][0])
				{
					$c = strtolower($tokens[$i+1][0]);
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

					case T_CURLY_OPEN:
					case T_DOLLAR_OPEN_CURLY_BRACES:
					case '{': case '[': --$b; break;
					case '}': case ']': ++$b; break;
					}
					while (isset($new_type[$j -= 2]));

					$new_code[$j] = "(({$c})?" . $new_code[$j];
				}

				new patchwork_preprocessor_marker($this);

				break;

			case T_STRING:
				if (T_DOUBLE_COLON === $prevType || T_OBJECT_OPERATOR === $prevType) break;

				$type = strtolower($code);

				if (T_FUNCTION === $prevType || ('&' === $prevType && T_FUNCTION === $antePrevType))
				{
					if (isset($class_pool[$curly_level-1]) && $c = $class_pool[$curly_level-1])
					{
						switch ($type)
						{
						case '__constructstatic': $c->add_constructStatic = 1 ; break;
						case '__destructstatic' : $c->add_destructStatic  = 1 ; break;
						case '__construct'      : $c->add_php5_construct  = false; break;

						case strtolower($c->classname):
							$c->construct_source = $c->classname;
							new patchwork_preprocessor_construct($this, $c->construct_source);
						}
					}

					break;
				}

				if (T_NEW === $prevType || T_DOUBLE_COLON === $tokens[$i+1][0])
				{
					if ('self' === $type) $class_pool && $code = end($class_pool)->classname; // Replace every self::* by __CLASS__::*
					else empty(self::$classAlias[$type]) || $code = self::$classAlias[$type];
				}
				else if ('(' === $tokens[$i+1][0])
				{
					if (isset(self::$functionAlias[$type]))
					{
						$j = self::$functionAlias[$type];

						if (0 !== strncasecmp($j, $class . '::', strlen($class)+2))
						{
							$j = explode('::', $j, 2);

							if (2 === count($j))
							{
								$tokens[$i--] = array(T_STRING,       $j[1], $line, '');
								$tokens[$i--] = array(T_DOUBLE_COLON, '::' , $line, '');
								$tokens[$i--] = array(T_STRING,       $j[0], $line, $sugar);

								continue 2;
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
							$j = !DEBUG && TURBO ? $this->fetchConstantCode($tokens, $j, $count, $b) : null;

							if (null === $j)
							{
								new patchwork_preprocessor_t($this);
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
				}
				else switch ($type)
				{
				case '__patchwork_level__': if (0 > $level) break;
					$code = $level;
					$type = T_LNUMBER;
					break;

				default:
					if (isset(self::$constant[$code]))
					{
						$code = self::$constant[$code];
						     if (  is_int($code)) $type = T_LNUMBER;
						else if (is_float($code)) $type = T_DNUMBER;
						else if ("'" === $code[0]) $type = T_CONSTANT_ENCAPSED_STRING;
					}
				}

				break;

			case T_EVAL:
				if ('(' === $tokens[$i+1][0])
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
				if (false !== $tokens[$i+1][0])
				{
					if (0 > $level)
					{
						$j = !DEBUG && TURBO ? $this->fetchConstantCode($tokens, $i, $count, $b) : null;

						if (null !== $j)
						{
							$b = patchworkProcessedPath($b);

							if (false === $b) $c = "patchworkProcessedPath({$j})";
							else
							{
								$c = substr_count($j, "\n");
								$c = patchwork_preprocessor::export($b, $c);
							}

							$tokens[$i--] = array(T_CONSTANT_ENCAPSED_STRING, $c, $line, $sugar);
						}
						else
						{
							$code .= 'patchworkProcessedPath(';
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

			case T_VARIABLE:

				if (!$autoglobalize && '$CONFIG' === $code && T_DOUBLE_COLON != $prevType)
				{
					$autoglobalize = $curly_starts_function ? 2 : 1;
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

					1 === $autoglobalize && $new_code[$curly_marker_last[0]] .= 'global $CONFIG;';

					unset($curly_marker[$curly_level]);
					end($curly_marker);
					$curly_marker_last =& $curly_marker[key($curly_marker)];
				}

				--$curly_level;

				if (isset($class_pool[$curly_level]))
				{
					$c = $class_pool[$curly_level];
					$j = strtolower($c->classname);

					if ($c->add_php5_construct) $code = $c->construct_source . '}';

					if ($c->add_constructStatic)
					{
						$code = "const __cS{$T}=" . (1 === $c->add_constructStatic ? "'{$j}';" : "'';static function __constructStatic(){}") . $code;
					}

					if ($c->add_destructStatic)
					{
						$code = "const __dS{$T}=" . (1 === $c->add_destructStatic  ? "'{$j}';" : "'';static function __destructStatic() {}") . $code;
					}

					$code .= "\$GLOBALS['c{$T}']['{$c->classkey}']=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "';";

					if ($is_top && strtolower($class) === $j)
					{
						if (!$c->is_final)
						{
							$code .= ($c->is_abstract ? 'abstract ' : '')
								. "class {$c->classname} extends {$c->classkey} {}"
								. "\$GLOBALS['c{$T}']['{$j}']=1;";
						}

						if (!$c->is_child)
						{
							1 === $c->add_constructStatic && $code .= "{$j}::__constructStatic();";
							1 === $c->add_destructStatic  && $code .= "\$GLOBALS['patchwork_destructors'][]='{$j}';";
						}
						else
						{
							2 !== $c->add_constructStatic && $code .= "if('{$j}'==={$j}::__cS{$T}){$j}::__constructStatic();";
							2 !== $c->add_destructStatic  && $code .= "if('{$j}'==={$j}::__dS{$T})\$GLOBALS['patchwork_destructors'][]='{$j}';";
						}
					}

					unset($class_pool[$curly_level]);
				}

				break;
			}

			foreach ($this->tokenFilter as $filter) $code = call_user_func($filter, $type, $code);

			$antePrevType = $prevType;
			$prevType = $type;

			$new_code[] = $sugar;
			$new_code[] = $code;
			$new_type[] = false;
			$new_type[] = $type;
			$new_code_length += 2;
		}

		if (T_CLOSE_TAG !== $type && T_INLINE_HTML !== $type)
		{
			$new_code[] = '';
			$new_code[] = '?>';
		}

		return $new_code;
	}

	protected function marker($class = '')
	{
		return ($class ? 'isset($c' . PATCHWORK_PATH_TOKEN . "['" . strtolower($class) . "'])||" : ('$e' . PATCHWORK_PATH_TOKEN . '=$b' . PATCHWORK_PATH_TOKEN . '=')) . '$a' . PATCHWORK_PATH_TOKEN . "=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "'";
	}

	protected function extractLF($a)
	{
		return str_repeat("\n", substr_count($a, "\n"));
	}

	protected function extractLF_callback($a)
	{
		return $this->extractLF($a[0]);
	}

	protected function fetchConstantCode(&$tokens, &$i, $count, &$value)
	{
		$new_code = array();
		$bracket = 0;
		$close = 0;

		for ($j = $i+1; $j < $count; ++$j)
		{
			list($type, $code) = $tokens[$j];

			switch ($type)
			{
			case '`': $close = 2; break;
			case T_STRING: $close = 2; break;

			case '?': case '(': case '{': case '[':
				++$bracket;
				break;

			case ':': case ')': case '}': case ']':
				$bracket-- || ++$close;
				break;

			case ',':
				$bracket   || ++$close;
				break;

			case T_AS:
			case T_CLOSE_TAG:
			case ';':
				++$close;
				break;

			default:
				if (in_array($type, self::$variableType)) $close = 2;
			}

			if (1 === $close)
			{
				$i = $j - 1;
				$j = implode('', $new_code);
				return false === @eval("\$value={$j};") ? null : $j;
			}
			else if (2 === $close)
			{
				return;
			}
			else $new_code[] = $code;
		}
	}

	static function export($a, $lf = 0)
	{
		return patchwork_bootstrapper_preprocessor::export($a, $lf);
	}

	static function error($message, $file, $line, $code = E_USER_ERROR)
	{
		patchwork_error::handle($code, $message, $file, $line);
	}
}
