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


// New tokens since PHP 5.3
defined('T_GOTO')         || define('T_GOTO', -1);
defined('T_USE' )         || define('T_USE' , -1);
defined('T_DIR' )         || define('T_DIR' , -1);
defined('T_NS_C')         || define('T_NS_C', -1);
defined('T_NAMESPACE')    || define('T_NAMESPACE', -1);
defined('T_NS_SEPARATOR') || define('T_NS_SEPARATOR', -1);

class patchwork_preprocessor__0
{
	public

	$source,
	$line = 1,
	$level,
	$class,
	$isTop,
	$marker,
	$inString = 0;


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

	protected function &preprocess(&$code)
	{
		$source = $this->source;
		$level  = $this->level;
		$class  = $this->class;
		$is_top = $this->isTop;
		$line   =& $this->line;
		$line   = 1;

		$code = token_get_all($code);
		$codeLen = count($code);

		$static_instruction = false;
		$antePrevType = '';
		$prevType = '';
		$new_code = array();
		$new_type = array();
		$new_code_length = 0;

		$T = PATCHWORK_PATH_TOKEN;
		$opentag_marker = "if(!isset(\$a{$T})){global \$CONFIG," . substr($this->marker[1], 7) . "}isset(\$e{$T})||\$e{$T}=false;";

		$curly_level = 0;
		$curly_starts_function = false;
		$autoglobalize = 0;
		$class_pool = array();
		$curly_marker = array(array(0,0));
		$curly_marker_last =& $curly_marker[0];

		$type = T_INLINE_HTML;

		for ($i = 0; $i < $codeLen; ++$i)
		{
			if (is_array($code[$i]))
			{
				$type  = $code[$i][0];
				$token = $code[$i][1];
			}
			else
			{
				$token = $code[$i];
				$type = ($this->inString & 1) && '"' !== $token && '`' !== $token ? T_ENCAPSED_AND_WHITESPACE : $token;
			}

			// Reduce memory usage
			unset($code[$i]);

			switch ($type)
			{
			case '@':
				if (self::$scream)
				{
					$code[$i--] = array(T_WHITESPACE, ' ');
					continue;
				}
				else break;

			case T_OPEN_TAG_WITH_ECHO:
				$line += substr_count($token, "\n");
				$code[$i--] = array(T_ECHO, 'echo');
				$code[$i--] = array(T_OPEN_TAG, '<?php ' . $this->extractLF($token));
				continue 2;

			case T_OPEN_TAG: // Normalize PHP open tag
				$line += substr_count($token, "\n");
				$token = '<?php ' . $opentag_marker . $this->extractLF($token);
				$opentag_marker = '';
				break;

			case T_CLOSE_TAG: // Normalize PHP close tag
				$line += substr_count($token, "\n");
				$token = $this->extractLF($token) . '?>';
				break;

			case '`':
			case '"':
				if ($this->inString & 1) --$this->inString;
				else ++$this->inString;
				break;

			case T_CURLY_OPEN:
			case T_DOLLAR_OPEN_CURLY_BRACES:
			case T_START_HEREDOC: ++$this->inString; break;
			case T_END_HEREDOC:   --$this->inString; break;

			case T_FILE:
				$token = patchwork_preprocessor::export($source);
				$type = T_CONSTANT_ENCAPSED_STRING;
				break;

			case T_DIR:
				$token = patchwork_preprocessor::export(dirname($source));
				$type = T_CONSTANT_ENCAPSED_STRING;
				break;

			case T_CLASS_C:
				if ($class_pool)
				{
					$token = "'" . end($class_pool)->classname . "'";
					$type = T_CONSTANT_ENCAPSED_STRING;
				}
				break;

			case T_METHOD_C:
				if ($class_pool)
				{
					// XXX PB WITH STATIC INSTRUCTIONS !!!
					$token = "('" . end($class_pool)->classname . "::'.__FUNCTION__)";
					$type = T_CONSTANT_ENCAPSED_STRING;
				}
				break;

			case '(':
				if (   ('}' === $prevType || T_VARIABLE === $prevType)
					&& !in_array($antePrevType, array(T_NEW, T_OBJECT_OPERATOR, T_DOUBLE_COLON)) )
				{
					$j = $new_code_length;

					while (--$j && in_array($new_type[$j], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;

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

							while ($b && --$j) switch ($new_type[$j])
							{
							case T_CURLY_OPEN:
							case T_DOLLAR_OPEN_CURLY_BRACES:
							case '{': --$b; break;
							case '}': ++$b; break;
							}

							$c[1] = $j;

							while (--$j && in_array($new_type[$j], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;

							if ('$' !== $new_type[$j]) break;
						}
						else $c = 0;

						while (--$j && in_array($new_type[$j], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT, '$'))) ;

						if (in_array($new_type[$j], array(T_NEW, T_OBJECT_OPERATOR, T_DOUBLE_COLON))) break;

						while (++$j && in_array($new_type[$j], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;

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

				$j = $this->seekSugar($code, $i);
				if (isset($code[$j]) && is_array($code[$j]) && T_STRING === $code[$j][0])
				{
					$token .= $this->fetchSugar($code, $i);

					$b = $c = $code[$i][1];

					if ($final) $token .= $c;
					else $token .= $b = $c . '__' . (0<=$level ? $level : '00');
				}
				else if ($class && 0<=$level)
				{
					$c = $class;
					$b = $c . (!$final ? '__' . $level : '');
					$token .= ' ' . $b;
				}
				else patchwork_preprocessor::error("Please specify explicitly the name of the class.", $source, $line);

				$token .= $this->fetchSugar($code, $i);

				$class_pool[$curly_level] = (object) array(
					'classname'   => $c,
					'classkey'    => strtolower($b),
					'is_child'    => false,
					'is_final'    => $final,
					'is_abstract' => T_ABSTRACT === $prevType,
					'add_php5_construct'  => T_CLASS === $type && 0>$level,
					'add_constructStatic' => 0,
					'add_destructStatic'  => 0,
					'construct_source'    => '',
				);

				if (T_ABSTRACT === $prevType)
				{
					$j = $new_code_length;
					while (--$j && in_array($new_type[$j], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;
					$new_code[$j] = "\$GLOBALS['patchwork_abstract']['{$class_pool[$curly_level]->classkey}']=1;" . $new_code[$j];
				}

				self::$inlineClass[strtolower($c)] = 1;

				if ($c && isset($code[$i]) && is_array($code[$i]) && T_EXTENDS === $code[$i][0])
				{
					$token .= $code[$i][1];
					$token .= $this->fetchSugar($code, $i);
					if (isset($code[$i]) && is_array($code[$i]))
					{
						$class_pool[$curly_level]->is_child = $code[$i][1];
						$c = 0<=$level && 'self' === $code[$i][1] ? $c . '__' . ($level ? $level-1 : '00') : $code[$i][1];
						$token .= $c;

						$c = strtolower($c);

						if (isset(self::$declaredClass[$c]) && 'patchwork' !== $c && 'p' !== $c)
						{
							$class_pool[$curly_level]->add_constructStatic = 2;
							$class_pool[$curly_level]->add_destructStatic  = 2;
						}

						self::$inlineClass[$c] = 1;
					}
					else --$i;
				}
				else
				{
					if (T_CLASS === $type)
					{
						$class_pool[$curly_level]->add_constructStatic = 2;
						$class_pool[$curly_level]->add_destructStatic  = 2;
					}

					--$i;
				}

				break;

			case T_VAR:
				if (0>$level)
				{
					$token = 'public';
					$type = T_PUBLIC;
				}

				break;

			case T_PRIVATE:
				// "private static" methods or properties are problematic when considering application inheritance.
				// To work around this, we change them to "protected static", and warn about it
				// (except for files in the include path). Side effects exist but should be rare.
				if (isset($class_pool[$curly_level-1]) && !$class_pool[$curly_level-1]->is_final)
				{
					// Look backward for the "static" keyword
					if (T_STATIC === $prevType) $j = true;
					else
					{
						// Look forward for the "static" keyword
						$j = $this->seekSugar($code, $i);
						$j = isset($code[$j]) && is_array($code[$j]) && T_STATIC === $code[$j][0];
					}

					if ($j)
					{
						$token = 'protected';
						$type = T_PROTECTED;

						if (0<=$level) patchwork_preprocessor::error("Private static methods or properties are banned, please use protected static ones instead.", $source, $line);
					}
				}

				break;

			case T_FUNCTION:
				$curly_starts_function = true;
				$autoglobalize = 0;
				break;

			case T_NEW:
				$token .= $this->fetchSugar($code, $i);
				if (!isset($code[$j = $i--])) break;

				$c = '';

				if (is_array($code[$j]) && T_STRING === $code[$j][0])
				{
					$c = strtolower($code[$j][1]);
					empty(self::$classAlias[$c]) || $c = self::$classAlias[$c];
					if (isset(self::$inlineClass[$c])) break;
				}

				if ('' === $c)
				{
					$curly_marker_last[1]>0 || $curly_marker_last[1] =  1;
					$c = "\$a{$T}=\$b{$T}=\$e{$T}";
				}
				else
				{
					$curly_marker_last[1]   || $curly_marker_last[1] = -1;
					$c = $this->marker($c);
				}

				if ('&' === $prevType)
				{
					$j = $new_code_length;
					while (--$j && '&' !== $new_type[$j]) ;

					if ('=' === $antePrevType)
					{
						while (--$j && '=' !== $new_type[$j]) ;
						--$j;
						$antePrevType = '&';
					}
					else
					{
						$new_type[$j] = $prevType = T_WHITESPACE;
						$new_code[$j] = ' ';
						$token = "(({$c})?" . $token;
					}
				}
				else $token = "(({$c})?" . $token;

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
					$j = $new_code_length;

					if ('&' === $antePrevType)
					{
						while (--$j && in_array($new_type[$j], array('&', $prevType, T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;
						if ('=' != $new_type[$j--]) break;
					}
					else
					{
						while (--$j && in_array($new_type[$j], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT, T_DEC, T_INC, $prevType))) ;
						while (++$j && in_array($new_type[$j], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;
						$new_code[$j] = "(({$c})?" . $new_code[$j];
					}
				}

				if ('&' === $antePrevType)
				{
					$b = 0;

					do switch ($new_type[$j])
					{
					case '$': if (!$b && ++$j) while (--$j && in_array($new_type[$j], array('$', T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;
					case T_VARIABLE:
						if (!$b)
						{
							while (--$j && in_array($new_type[$j], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;
							if (T_OBJECT_OPERATOR !== $new_type[$j] && T_DOUBLE_COLON !== $new_type[$j] && ++$j) break 2;
						}
						break;

					case T_CURLY_OPEN:
					case T_DOLLAR_OPEN_CURLY_BRACES:
					case '{': case '[': --$b; break;
					case '}': case ']': ++$b; break;
					}
					while (--$j);

					while (in_array($new_type[$j], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ++$j;

					$new_code[$j] = "(({$c})?" . $new_code[$j];
				}

				new patchwork_preprocessor_marker($this);

				break;

			case T_STRING:
				if (($this->inString & 1) || T_DOUBLE_COLON === $prevType || T_OBJECT_OPERATOR === $prevType) break;

				$type = strtolower($token);

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

				$c = $this->fetchSugar($code, $i);
				if (!isset($code[$i--]))
				{
					$token .= $c;
					break;
				}

				if (T_NEW === $prevType || is_array($code[$i+1]) && T_DOUBLE_COLON === $code[$i+1][0])
				{
					if ('self' === $type) $class_pool && $token = end($class_pool)->classname; // Replace every self::* by __CLASS__::*
					else empty(self::$classAlias[$type]) || $token = self::$classAlias[$type];
				}
				else if ('(' === $code[$i+1])
				{
					if (isset(self::$functionAlias[$type]))
					{
						$j = self::$functionAlias[$type];

						if (0 !== strncasecmp($j, $class . '::', strlen($class)+2))
						{
							$j = explode('::', $j, 2);

							if (2 === count($j))
							{
								'' !== $c && $code[$i--] = array(T_WHITESPACE, $c);
								$code[$i--] = array(T_STRING, $j[1]);
								$code[$i--] = array(T_DOUBLE_COLON, '::');
								$code[$i--] = array(T_STRING, $j[0]);

								continue 2;
							}
							else
							{
								$token = $j[0];
								$type = strtolower($token);
							}
						}
					}

					switch ($type)
					{
					case 'patchworkpath':
						// Append its fourth arg to patchworkPath
						if (0<=$level) new patchwork_preprocessor_path($this);
						break;

					case 't':
						if (0<=$level)
						{
							$j = $i;
							$j = !DEBUG && TURBO ? $this->fetchConstantCode($code, $j, $codeLen, $b) : null;

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

						$token = "((\$a{$T}=\$b{$T}=\$e{$T})||1?{$token}";
						$b = new patchwork_preprocessor_callback($this, $type);
						$b->curly = -1;
						$curly_marker_last[1]>0 || $curly_marker_last[1] = 1;

						if ('&' === $prevType)
						{
							$j = $new_code_length;
							while (--$j && in_array($new_type[$j], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;
							$new_code[$j] = ' ';
							$new_type[$j] = T_WHITESPACE;
						}

						// For files in the include_path, always set the 2nd arg of class|interface_exists() to true
						if (0>$level && in_array($type, array('interface_exists', 'class_exists'))) new patchwork_preprocessor_classExists($this);
					}
				}
				else switch ($type)
				{
				case '__patchwork_level__': if (0>$level) break;
					$token = $level;
					$type = T_LNUMBER;
					break;

				default:
					if (isset(self::$constant[$token]))
					{
						$token = self::$constant[$token];
						     if (  is_int($token)) $type = T_LNUMBER;
						else if (is_float($token)) $type = T_DNUMBER;
						else if ("'" === $token[0]) $type = T_CONSTANT_ENCAPSED_STRING;
					}
				}

				$token .= $c;

				break;

			case T_EVAL:
				$token .= $this->fetchSugar($code, $i);
				if ('(' === $code[$i--])
				{
					$token = "((\$a{$T}=\$b{$T}=\$e{$T})||1?{$token}";
					$b = new patchwork_preprocessor_marker($this);
					$b->curly = -1;
					$curly_marker_last[1]>0 || $curly_marker_last[1] = 1;
				}
				break;

			case T_REQUIRE_ONCE:
			case T_INCLUDE_ONCE:
			case T_REQUIRE:
			case T_INCLUDE:
				$token .= ' ' . $this->fetchSugar($code, $i);

				// Every require|include inside files in the include_path
				// is preprocessed thanks to patchworkProcessedPath().
				if (isset($code[$i--]))
				{
					if (0>$level)
					{
						$j = !DEBUG && TURBO ? $this->fetchConstantCode($code, $i, $codeLen, $b) : null;

						if (null !== $j)
						{
							$b = patchworkProcessedPath($b);

							if (false === $b) $c = "patchworkProcessedPath({$j})";
							else
							{
								$c = substr_count($j, "\n");
								$line += $c;
								$c = patchwork_preprocessor::export($b, $c);
							}

							$code[$i--] = array(T_CONSTANT_ENCAPSED_STRING, $c);
						}
						else
						{
							$token .= 'patchworkProcessedPath(';
							new patchwork_preprocessor_require($this);
						}
					}
					else
					{
						$token .= "((\$a{$T}=\$b{$T}=\$e{$T})||1?";
						$b = new patchwork_preprocessor_require($this);
						$b->close = ':0)';
						$curly_marker_last[1]>0 || $curly_marker_last[1] = 1;
					}
				}

				break;

			case T_VARIABLE:

				if (!$autoglobalize && '$CONFIG' === $token && T_DOUBLE_COLON != $prevType)
				{
					$autoglobalize = $curly_starts_function ? 2 : 1;
				}

				break;

			case T_DOC_COMMENT:
			case T_COMMENT: $type = T_WHITESPACE;
			case T_WHITESPACE:
				$token = substr_count($token, "\n");
				$line += $token;
				$token = $token ? str_repeat("\n", $token) : ' ';
				break;

			case T_CONSTANT_ENCAPSED_STRING:
			case T_ENCAPSED_AND_WHITESPACE:
				$line += substr_count($token, "\n");
				break;

			case T_STATIC:
				$static_instruction = true;
				break;

			case ';':
				$curly_starts_function = false;
				$static_instruction = false;
				$new_type = array(($new_code_length-1) => '');
				break;

			case '{':
				isset($class_pool[$curly_level-1]) && $static_instruction = false;

				++$curly_level;

				if ($curly_starts_function)
				{
					$curly_starts_function = false;
					$curly_marker_last =& $curly_marker[$curly_level];
					$curly_marker_last = array($new_code_length, 0);
				}

				break;

			case '}':
				if ($this->inString)
				{
					$type = T_ENCAPSED_AND_WHITESPACE;
					--$this->inString;
					break;
				}

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

					if ($c->add_php5_construct) $token = $c->construct_source . '}';

					if ($c->add_constructStatic)
					{
						$token = "const __cS{$T}=" . (1 === $c->add_constructStatic ? "'{$j}';" : "'';static function __constructStatic(){}") . $token;
					}

					if ($c->add_destructStatic)
					{
						$token = "const __dS{$T}=" . (1 === $c->add_destructStatic  ? "'{$j}';" : "'';static function __destructStatic() {}") . $token;
					}

					$token .= "\$GLOBALS['c{$T}']['{$c->classkey}']=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "';";

					if ($is_top && strtolower($class) === $j)
					{
						if (!$c->is_final)
						{
							$token .= ($c->is_abstract ? 'abstract ' : '')
								. "class {$c->classname} extends {$c->classkey} {}"
								. "\$GLOBALS['c{$T}']['{$j}']=1;";
						}

						if (!$c->is_child)
						{
							1 === $c->add_constructStatic && $token .= "{$j}::__constructStatic();";
							1 === $c->add_destructStatic  && $token .= "\$GLOBALS['patchwork_destructors'][]='{$j}';";
						}
						else
						{
							2 !== $c->add_constructStatic && $token .= "if('{$j}'==={$j}::__cS{$T}){$j}::__constructStatic();";
							2 !== $c->add_destructStatic  && $token .= "if('{$j}'==={$j}::__dS{$T})\$GLOBALS['patchwork_destructors'][]='{$j}';";
						}
					}

					unset($class_pool[$curly_level]);
				}

				break;
			}

			foreach ($this->tokenFilter as $filter) $token = call_user_func($filter, $type, $token);

			if (T_WHITESPACE != $type && T_COMMENT != $type && T_DOC_COMMENT != $type)
			{
				$antePrevType = $prevType;
				$prevType = $type;
			}

			$new_code[] = $token;
			$new_type[] = $type;
			++$new_code_length;
		}

		if (T_CLOSE_TAG != $type && T_INLINE_HTML != $type) $new_code[] = '?>';

		return $new_code;
	}

	protected function marker($class = '')
	{
		return ($class ? 'isset($c' . PATCHWORK_PATH_TOKEN . "['" . strtolower($class) . "'])||" : ('$e' . PATCHWORK_PATH_TOKEN . '=$b' . PATCHWORK_PATH_TOKEN . '=')) . '$a' . PATCHWORK_PATH_TOKEN . "=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "'";
	}

	protected function seekSugar(&$code, $i)
	{
		while (
			isset($code[++$i]) && is_array($code[$i]) && ($t = $code[$i][0])
			&& (T_WHITESPACE === $t || T_COMMENT === $t || T_DOC_COMMENT === $t)
		) ;

		return $i;
	}

	protected function fetchSugar(&$code, &$i)
	{
		$token = '';
		$nonEmpty = false;

		while (
			isset($code[++$i]) && is_array($code[$i]) && ($t = $code[$i][0])
			&& (T_WHITESPACE === $t || T_COMMENT === $t || T_DOC_COMMENT === $t)
		)
		{
			// Preserve T_DOC_COMMENT for PHP's native Reflection API
			$token .= T_DOC_COMMENT === $t ? $code[$i][1] : $this->extractLF($code[$i][1]);
			$nonEmpty || $nonEmpty = true;
		}

		$this->line += substr_count($token, "\n");

		return $nonEmpty && '' === $token ? ' ' : $token;
	}

	protected function extractLF($a)
	{
		return str_repeat("\n", substr_count($a, "\n"));
	}

	protected function extractLF_callback($a)
	{
		return $this->extractLF($a[0]);
	}

	protected function fetchConstantCode(&$code, &$i, $codeLen, &$value)
	{
		$new_code = array();
		$inString = false;
		$bracket = 0;
		$close = 0;

		for ($j = $i+1; $j < $codeLen; ++$j)
		{
			$token = $code[$j];
			if (is_array($token))
			{
				$type = $token[0];
				$token = $token[1];
			}
			else $type = $inString && '"' !== $token && '`' !== $token ? T_ENCAPSED_AND_WHITESPACE : $token;

			switch ($type)
			{
			case '`': $close = 2; break;

			case '"':             $inString = !$inString; break;
			case T_START_HEREDOC: $inString = true;       break;
			case T_END_HEREDOC:   $inString = false;      break;
			case T_STRING:   if (!$inString) $close = 2;  break;

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
			else $new_code[] = $token;
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
