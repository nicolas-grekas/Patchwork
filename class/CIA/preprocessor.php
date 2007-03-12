<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


function_exists('token_get_all') || die('Extension "tokenizer" is needed and not loaded.');


class CIA_preprocessor__0
{
	public $source;
	public $line = 1;
	public $level;
	public $class;
	public $marker;
	public $inString = false;

	protected $tokenFilter = array();


	static $constant = array();
	static $function = array(
		'header'       => 'CIA::header',
		'setcookie'    => 'CIA::setcookie',
		'setrawcookie' => 'CIA::setrawcookie',
		'rand'         => 'mt_rand',
		'srand'        => 'mt_srand',
		'getrandmax'   => 'mt_getrandmax',
	);

	static $variableType = array(
		T_EVAL, '(', T_FILE, T_LINE, T_FUNC_C, T_CLASS_C, T_INCLUDE, T_REQUIRE,
		T_VARIABLE, '$', T_INCLUDE_ONCE, T_REQUIRE_ONCE, T_DOLLAR_OPEN_CURLY_BRACES,
	);

	// List of native functions that could trigger __autoload()
	static $callback = array(
		// Unknown or multiple callback parameter position
		'array_diff_ukey'         => 0,
		'array_diff_uasso'        => 0,
		'array_intersect_ukey'    => 0,
		'array_udiff_assoc'       => 0,
		'array_udiff_uassoc'      => 0,
		'array_udiff'             => 0,
		'array_uintersect_assoc'  => 0,
		'array_uintersect_uassoc' => 0,
		'array_uintersect'        => 0,
		'assert'                  => 0,
		'constant'                => 0,
		'curl_setopt'             => 0,
		'create_function'         => 0,
		'preg_replace'            => 0,
		'sqlite_create_aggregate' => 0,

		// Classname as string in the first parameter
		'class_exists'      => -1,
		'get_class_methods' => -1,
		'get_class_vars'    => -1,
		'get_parent_class'  => -1,
		'interface_exists'  => -1,
		'method_exists'     => -1,
		'property_exists'   => -1,

		// Classname as callback in the first parameter
		'array_map'                  => 1,
		'call_user_func'             => 1,
		'call_user_func_array'       => 1,
		'is_callable'                => 1,
		'ob_start'                   => 1,
		'register_shutdown_function' => 1,
		'register_tick_function'     => 1,
		'session_set_save_handler'   => 1,
		'set_exception_handler'      => 1,
		'set_error_handler'          => 1,
		'sybase_set_message_handler' => 1,

		// Classname as callback in the second parameter
		'array_filter'                           => 2,
		'array_reduce'                           => 2,
		'array_walk'                             => 2,
		'array_walk_recursive'                   => 2,
		'assert_options'                         => 2,
		'pcntl_signal'                           => 2,
		'preg_replace_callback'                  => 2,
		'runkit_sandbox_output_handler'          => 2,
		'usort'                                  => 2,
		'uksort'                                 => 2,
		'uasort'                                 => 2,
		'xml_set_character_data_handler'         => 2,
		'xml_set_default_handler'                => 2,
		'xml_set_element_handler'                => 2,
		'xml_set_end_namespace_decl_handler'     => 2,
		'xml_set_processing_instruction_handler' => 2,
		'xml_set_start_namespace_decl_handler'   => 2,
		'xml_set_notation_decl_handler'          => 2,
		'xml_set_external_entity_ref_handler'    => 2,
		'xml_set_unparsed_entity_decl_handler'   => 2,

		// Classname as callback in the third parameter
		'filter_var'             => 3,
		'sqlite_create_function' => 3,
	);

	protected static $inline_class;
	protected static $recursive = false;

	static function __static_construct()
	{
		defined('E_RECOVERABLE_ERROR') || CIA_preprocessor::$constant['E_RECOVERABLE_ERROR'] = E_ERROR;

		// As of PHP5.1.2, md5($str) is a lot faster than md5($str) !
		extension_loaded('hash') && CIA_preprocessor::$function += array(
			'md5'   => "hash('md5',",
			'sha1'  => "hash('sha1',",
			'crc32' => "hash('crc32',",
		);

		if (!function_exists('mb_stripos'))
		{
			CIA_preprocessor::$function += array(
				'mb_stripos'  => 'mbstring_520::stripos',
				'mb_stristr'  => 'mbstring_520::stristr',
				'mb_strrchr'  => 'mbstring_520::strrchr',
				'mb_strrichr' => 'mbstring_520::strrichr',
				'mb_strripos' => 'mbstring_520::strripos',
				'mb_strstr'   => 'mbstring_520::strstr',
			);

			if (!extension_loaded('mbstring'))
			{
				CIA_preprocessor::$constant += array(
					'MB_OVERLOAD_MAIL'   => 1,
					'MB_OVERLOAD_STRING' => 2,
					'MB_OVERLOAD_REGEX'  => 4,
					'MB_CASE_UPPER' => 0,
					'MB_CASE_LOWER' => 1,
					'MB_CASE_TITLE' => 2
				);

				CIA_preprocessor::$function += array(
					'mb_convert_encoding'  => 'mbstring_500::convert_encoding',
					'mb_decode_mimeheader' => 'mbstring_500::decode_mimeheader',
					'mb_encode_mimeheader' => 'mbstring_500::encode_mimeheader',
					'mb_convert_case'      => 'mbstring_500::convert_case',
					'mb_list_encodings'    => 'mbstring_500::list_encodings',
					'mb_parse_str'         => 'parse_str',
					'mb_strlen'            => 'mbstring_500::strlen',
					'mb_strpos'            => 'mbstring_500::strpos',
					'mb_strrpos'           => 'mbstring_500::strrpos',
					'mb_strtolower'        => 'mbstring_500::strtolower',
					'mb_strtoupper'        => 'mbstring_500::strtoupper',
					'mb_substr_count'      => 'substr_count',
					'mb_substr'            => 'mbstring_500::substr',
				);

				extension_loaded('iconv') && CIA_preprocessor::$function += array(
					'mb_convert_encoding'  => 'mbstring_500::convert_encoding',
					'mb_decode_mimeheader' => 'mbstring_500::decode_mimeheader',
					'mb_encode_mimeheader' => 'mbstring_500::encode_mimeheader',
				);
			}
		}

		foreach (CIA_preprocessor::$constant as $k => &$v) $v = CIA_preprocessor::export($v);
	}

	static function run($source, $destination, $level, $class)
	{
		$recursive = CIA_preprocessor::$recursive;

		if (!$recursive)
		{
			CIA_preprocessor::$inline_class = array('self', 'parent', 'this', 'static');
			CIA_preprocessor::$recursive = true;
		}

		$code = $GLOBALS['cia_paths_token'];

		$preproc = class_exists('CIA_preprocessor', false) ? 'CIA_preprocessor' : 'CIA_preprocessor__0';
		$preproc = new $preproc;
		$preproc->source = $source = realpath($source);
		$preproc->level = $level;
		$preproc->class = $class;
		$preproc->marker = array(
			'global $a' . $code . ',$c' . $code . ';',
			'global $a' . $code . ',$b' . $code . ',$c' . $code . ';'
		);

		$code = file_get_contents($source);

		if (!preg_match("''u", $code)) W("File {$source}:\nfile encoding is not valid UTF-8. Please convert your source code to UTF-8.");

		$preproc->antePreprocess($code);
		$code =& $preproc->preprocess($code);

		CIA_preprocessor::$recursive = $recursive;

		cia_atomic_write($code, $destination);
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
		if (false !== strpos($code,  '#>>>')) $code = preg_replace_callback( "'^#>>>\s*^.*?^(?:#|//)<<<\s*$'ms", array($this, 'extractRxLF'), $code);
		if (false !== strpos($code, '//>>>')) $code = preg_replace_callback("'^//>>>\s*^.*?^(?:#|//)<<<\s*$'ms", array($this, 'extractRxLF'), $code);
		if (DEBUG)
		{
			if (false !== strpos($code,  '#>')) $code = preg_replace( "'^#>([^>].*)$'m", '$1', $code);
			if (false !== strpos($code, '//>')) $code = preg_replace("'^//>([^>].*)$'m", '$1', $code);
			if (false !== strpos($code, '/*>')) $code = preg_replace("'^(/\*>)(\s*^.*?^)(<\*/\s*)$'ms", '$2$1$3', $code);
		}
	}

	protected function &preprocess(&$code)
	{
		$source = $this->source;
		$level  = $this->level;
		$class  = $this->class;
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

		$opentag_marker = $this->marker[1] . "isset(\$e{$GLOBALS['cia_paths_token']})||\$e{$GLOBALS['cia_paths_token']}=false;";
		$curly_level = 0;
		$curly_starts_function = false;
		$class_pool = array();
		$curly_marker = array(array(0,0));
		$curly_marker_last =& $curly_marker[0];

		for ($i = 0; $i < $codeLen; ++$i)
		{
			$token = $code[$i];
			if (is_array($token))
			{
				$type = $token[0];
				$token = $token[1];
			}
			else $type = $token;

			switch ($type)
			{
			case T_OPEN_TAG_WITH_ECHO:
				array_splice($code, $i+1, 0, array(array(T_ECHO, 'echo')));
				++$codeLen;
				$type = T_OPEN_TAG;

			case T_OPEN_TAG: // Normalize PHP open tag
				$token = '<?php ' . $opentag_marker . $this->extractLF($token);
				$opentag_marker = '';
				break;

			case T_CLOSE_TAG: // Normalize PHP close tag
				$token = $this->extractLF($token) . '?>';
				break;

			case '"':  $this->inString = !$this->inString; break;
			case T_START_HEREDOC: $this->inString = true;  break;
			case T_END_HEREDOC:   $this->inString = false; break;

			case T_CURLY_OPEN:
			case T_DOLLAR_OPEN_CURLY_BRACES:
				++$curly_level;
				break;

			case T_CLASS_C:
				if ($class_pool)
				{
					$token = "'" . end($class_pool)->classname . "'";
					$type = T_CONSTANT_ENCAPSED_STRING;
				}
				break;

			case T_INTERFACE:
			case T_CLASS:
				$b = $c = '';

				$final    = T_FINAL    == $prevType;
				$abstract = T_ABSTRACT == $prevType;

				// Look forward
				$j = $this->seekSugar($code, $i);
				if (isset($code[$j]) && is_array($code[$j]) && T_STRING == $code[$j][0])
				{
					$token .= $this->fetchSugar($code, $i);

					$b = $c = $code[$i][1];

					if ($final) $token .= $c;
					else
					{
						$c = preg_replace("'__[0-9]+$'", '', $c);
						$b = $c . '__' . (0<=$level ? $level : '00');
						$token .= $b;
					}

					$token .= $this->fetchSugar($code, $i);
				}

				if (!$c)
				{
					if ($class && 0<=$level)
					{
						$c = $class;
						$b = $c . (!$final ? '__' . $level : '');
						$token .= ' ' . $b;
					}

					$token .= $this->fetchSugar($code, $i);
				}

				$class_pool[$curly_level] = (object) array(
					'classname' => $c,
					'real_classname' => strtolower($b),
					'is_final' => $final,
					'is_abstract' => $abstract,
					'has_php5_construct' => T_INTERFACE == $type,
					'construct_source' => '',
				);

				CIA_preprocessor::$inline_class[] = strtolower($c);

				if ($c && isset($code[$i]) && is_array($code[$i]) && T_EXTENDS == $code[$i][0])
				{
					$token .= $code[$i][1];
					$token .= $this->fetchSugar($code, $i);
					if (isset($code[$i]) && is_array($code[$i]))
					{
						$c = 0<=$level && 'self' == $code[$i][1] ? $c . '__' . ($level ? $level-1 : '00') : $code[$i][1];
						$token .= $c;
						CIA_preprocessor::$inline_class[] = strtolower($c);
					}
					else --$i;
				}
				else --$i;

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
					if (T_STATIC == $prevType) $j = true;
					else
					{
						// Look forward for the "static" keyword
						$j = $this->seekSugar($code, $i);
						$j = isset($code[$j]) && is_array($code[$j]) && T_STATIC == $code[$j][0];
					}

					if ($j)
					{
						$token = 'protected';
						$type = T_PROTECTED;

						if (0<=$level) W("File {$source} line {$line}:\nprivate static methods or properties are banned.\nPlease use protected static ones instead.");
					}
				}

				break;

			case T_FUNCTION:
				$curly_starts_function = true;
				break;

			case T_NEW:
				$token .= $this->fetchSugar($code, $i);
				if (!isset($code[$j = $i--])) break;

				$c = true;

				if (is_array($code[$j]) && T_STRING == $code[$j][0])
				{
					if (in_array($code[$j][1], CIA_preprocessor::$inline_class)) break;
					$c = false;
				}

				if ($c)
				{
					$curly_marker_last[1]>0 || $curly_marker_last[1] =  1;
					$c = "\$a{$GLOBALS['cia_paths_token']}=\$b{$GLOBALS['cia_paths_token']}=\$e{$GLOBALS['cia_paths_token']}";
				}
				else
				{
					$curly_marker_last[1]   || $curly_marker_last[1] = -1;
					$c = $this->marker($code[$j][1]);
				}

				if ('&' == $prevType)
				{
					if ('=' != $antePrevType) break;
					$antePrevType = '&';

					$j = $new_code_length;
					while (--$j && in_array($new_type[$j], array('=', '&', T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;
				}
				else $token = "(({$c})?" . $token;

			case T_DOUBLE_COLON:
				if (T_DOUBLE_COLON == $type)
				{
					if ($static_instruction || isset($class_pool[$curly_level-1]) || in_array($prevType, CIA_preprocessor::$inline_class)) break;

					$curly_marker_last[1] || $curly_marker_last[1] = -1;
					$c = $this->marker($prevType);
					$j = $new_code_length;

					if ('&' == $antePrevType)
					{
						while (--$j && in_array($new_type[$j], array('&', $prevType, T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;
						if ('=' != $new_type[$j--]) break;
					}
					else
					{
						while (--$j && in_array($new_type[$j], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;
						$new_code[$j] = "(({$c})?" . $new_code[$j];
					}
				}

				if ('&' == $antePrevType)
				{
					$b = 0;

					do switch ($new_type[$j])
					{
					case '$': if (!$b && ++$j) while (--$j && in_array($new_type[$j], array('$', T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;
					case T_VARIABLE:
						if (!$b)
						{
							while (--$j && in_array($new_type[$j], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;
							if (T_OBJECT_OPERATOR != $new_type[$j] && ++$j) break 2;
						}
						break;

					case '{': case '[': --$b; break;
					case '}': case ']': ++$b; break;
					}
					while (--$j);

					$new_code[$j] = "(({$c})?" . $new_code[$j];
				}

				new CIA_preprocessor_marker_($this, true);

				break;

			case T_STRING:
				if ($this->inString || T_DOUBLE_COLON == $prevType || T_OBJECT_OPERATOR == $prevType) break;

				$type = strtolower($token);

				if (T_FUNCTION == $prevType || ('&' == $prevType && T_FUNCTION == $antePrevType))
				{
					if (   isset($class_pool[$curly_level-1])
						&& ($c = $class_pool[$curly_level-1])
						&& !$c->has_php5_construct)
					{
						// If the currently parsed method is this class constructor
						// build a PHP5 constructor if needed.

						if ('__construct' == $type) $c->has_php5_construct = true;
						else if ($type == strtolower($c->classname))
						{
							$c->construct_source = $c->classname;
							new CIA_preprocessor_construct_($this, $c->construct_source);
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

				if ('(' == $code[$i+1])
				{
					if (isset(CIA_preprocessor::$function[$type]))
					{
						if (CIA_preprocessor::$function[$type] instanceof CIA_preprocessor_bracket)
						{
							$token .= $c;
							$c = clone CIA_preprocessor::$function[$type];
							$c->setupFilter();
							break;
						}
						else if (0 !== stripos(CIA_preprocessor::$function[$type], $class . '::'))
						{
							$token = CIA_preprocessor::$function[$type];
							if (false !== strpos($token, '(')) ++$i && $type = '(';
							else $type = strtolower($token);
						}
					}

					switch ($type)
					{
					case 'resolvepath':
					case 'processpath':
						// If possible, resolve the path now, else append their third arg to resolve|processPath
						if (0<=$level)
						{
							$j = (string) $this->fetchConstant($code, $i);
							if ('' !== $j)
							{
								eval("\$b={$token}{$j};");
								$token = false !== $b ? CIA_preprocessor::export($b) : "{$token}({$j})";
								$type = T_CONSTANT_ENCAPSED_STRING;
							}
							else new CIA_preprocessor_path_($this, true);
						}
						break;

					case 't':
						if (0<=$level) new CIA_preprocessor_t_($this, true);
						break;

					default:
						if (!isset(CIA_preprocessor::$callback[$type])) break;

						$token = "((\$a{$GLOBALS['cia_paths_token']}=\$b{$GLOBALS['cia_paths_token']}=\$e{$GLOBALS['cia_paths_token']})||1?" . $token;
						$b = new CIA_preprocessor_marker_($this, true);
						$b->curly = -1;
						$curly_marker_last[1]>0 || $curly_marker_last[1] = 1;

						if ('&' == $prevType)
						{
							$j = $new_code_length;
							while (--$j && in_array($new_type[$j], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;
							$new_code[$j] = ' ';
							$new_type[$j] = T_WHITESPACE;
						}

						// For files in the include_path, always set the 2nd arg of class|interface_exists() to true
						if (0>$level && in_array($type, array('interface_exists', 'class_exists'))) new CIA_preprocessor_classExists_($this, true);
					}
				}
				else if (!(is_array($code[$i+1]) && T_DOUBLE_COLON == $code[$i+1][0])) switch ($type)
				{
				case '__cia_level__': if (0>$level) break;
					$token = $level;
					$type = T_LNUMBER;
					break;

				case '__cia_file__': if (0>$level) break;
					$token = CIA_preprocessor::export($source);
					$type = T_CONSTANT_ENCAPSED_STRING;
					break;

				default:
					if (isset(CIA_preprocessor::$constant[$token]))
					{
						$token = CIA_preprocessor::$constant[$token];
						     if (  is_int($token)) $type = T_LNUMBER;
						else if (is_float($token)) $type = T_DNUMBER;
						else if ("'" == $token[0]) $type = T_CONSTANT_ENCAPSED_STRING;
					}
				}
				else if ('self' == $type && $class_pool) $token = end($class_pool)->classname; // Replace every self::* by __CLASS__::*

				$token .= $c;

				break;

			case T_EVAL:
				$token .= $this->fetchSugar($code, $i);
				if ('(' == $code[$i--])
				{
					$token = "((\$a{$GLOBALS['cia_paths_token']}=\$b{$GLOBALS['cia_paths_token']}=\$e{$GLOBALS['cia_paths_token']})||1?" . $token;
					$b = new CIA_preprocessor_marker_($this, true);
					$b->curly = -1;
					$curly_marker_last[1]>0 || $curly_marker_last[1] = 1;
				}
				break;

			case T_REQUIRE_ONCE:
			case T_INCLUDE_ONCE:
			case T_REQUIRE:
			case T_INCLUDE:
				$token .= $this->fetchSugar($code, $i);

				// Every require|include inside files in the include_path
				// is preprocessed thanks to cia_adaptRequire().
				if (isset($code[$i--]))
				{
					if (0>$level)
					{
						$j = (string) $this->fetchConstant($code, $i);
						if ( '' !== $j)
						{
							eval("\$b=cia_adaptRequire({$j});");
							$code[$i--] = array(
								T_CONSTANT_ENCAPSED_STRING,
								false !== $b ? CIA_preprocessor::export($b) : "cia_adaptRequire({$j})"
							);
						}
						else
						{
							$token .= 'cia_adaptRequire(';
							new CIA_preprocessor_require_($this, true);
						}
					}
					else
					{
						$j = '(' == $code[$i+1] && isset($code[$i+2]) ? $this->seekSugar($code, $i+1) : $i+1;
						if (!(is_array($code[$j]) && T_STRING == $code[$j][0] && 'processpath' == strtolower($code[$j][1])))
						{
							$token .= "((\$a{$GLOBALS['cia_paths_token']}=\$b{$GLOBALS['cia_paths_token']}=\$e{$GLOBALS['cia_paths_token']})||1?";
							$b = new CIA_preprocessor_require_($this, true);
							$b->close = ':0)';
							$curly_marker_last[1]>0 || $curly_marker_last[1] = 1;
						}
					}
				}

				break;

			case T_COMMENT: $type = T_WHITESPACE;
			case T_WHITESPACE:
				$token = substr_count($token, "\n");
				$line += $token;
				$token = $token ? str_repeat("\n", $token) : ' ';
				break;

			case T_DOC_COMMENT: // Preserve T_DOC_COMMENT for PHP's native Reflection API
			case T_CONSTANT_ENCAPSED_STRING:
			case T_ENCAPSED_AND_WHITESPACE:
				$line += substr_count($token, "\n");
				break;

			case T_STATIC:
				$static_instruction = true;
				break;

			case ';':
				$static_instruction = false;
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
				if (isset($curly_marker[$curly_level]))
				{
					$curly_marker_last[1] && $new_code[$curly_marker_last[0]] .= $curly_marker_last[1]>0
						? "{$this->marker[1]}static \$d{$GLOBALS['cia_paths_token']}=1;(" . $this->marker() . ")&&\$d{$GLOBALS['cia_paths_token']}&&\$d{$GLOBALS['cia_paths_token']}=0;"
						: $this->marker[0];

					unset($curly_marker[$curly_level]);
					end($curly_marker);
					$curly_marker_last =& $curly_marker[key($curly_marker)];
				}

				--$curly_level;

				if (isset($class_pool[$curly_level]))
				{
					$c = $class_pool[$curly_level];

					if (!$c->has_php5_construct) $token = $c->construct_source . '}';
					if ($c->is_abstract) $token .= "\$GLOBALS['cia_abstract']['{$c->real_classname}']=1;";
					$token .= "\$GLOBALS['c{$GLOBALS['cia_paths_token']}']['{$c->real_classname}']=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "';";

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

		$token =& $code[$codeLen - 1];

		if (!is_array($token) || (T_CLOSE_TAG != $token[0] && T_INLINE_HTML != $token[0])) $new_code[] = '?>';

		return $new_code;
	}

	protected function marker($class = '')
	{
		global $cia_paths_token;
		return ($class ? "isset(\$c{$cia_paths_token}['" . strtolower($class) . "'])||" : ("\$e{$cia_paths_token}=\$b{$cia_paths_token}=")) . "\$a{$cia_paths_token}=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "'";
	}

	protected function seekSugar(&$code, $i)
	{
		while (
			isset($code[++$i]) && is_array($code[$i]) && ($t = $code[$i][0])
			&& (T_WHITESPACE == $t || T_COMMENT == $t || T_DOC_COMMENT == $t)
		) ;

		return $i;
	}

	protected function fetchSugar(&$code, &$i)
	{
		$token = '';
		$nonEmpty = false;

		while (
			isset($code[++$i]) && is_array($code[$i]) && ($t = $code[$i][0])
			&& (T_WHITESPACE == $t || T_COMMENT == $t || T_DOC_COMMENT == $t)
		)
		{
			// Preserve T_DOC_COMMENT for PHP's native Reflection API
			$token .= T_DOC_COMMENT == $t ? $code[$i][1] : $this->extractLF($code[$i][1]);
			$nonEmpty || $nonEmpty = true;
		}

		$this->line += substr_count($token, "\n");

		return $nonEmpty && '' === $token ? ' ' : $token;
	}

	protected function extractLF($a)
	{
		return str_repeat("\n", substr_count($a, "\n"));
	}

	protected function extractRxLF($a)
	{
		return $this->extractLF($a[0]);
	}

	protected function fetchConstant(&$code, &$i)
	{
		if (DEBUG) return false;

		$new_code = array();
		$codeLen = count($code);
		$inString = false;
		$bracket = 0;

		for ($j = $i+1; $j < $codeLen; ++$j)
		{
			$token = $code[$j];
			if (is_array($token))
			{
				$type = $token[0];
				$token = $token[1];
			}
			else $type = $token;

			$close = '';

			switch ($type)
			{
			case '"':             $inString = !$inString;  break;
			case T_START_HEREDOC: $inString = true;        break;
			case T_END_HEREDOC:   $inString = false;       break;
			case T_STRING:   if (!$inString) return false; break;
			case '?': case '(': ++$bracket;                break;
			case ':': case ')':   $bracket-- ||    $close = true; break;
			case ',':             $bracket   ||    $close = true; break;
			case T_AS: case T_CLOSE_TAG: case ';': $close = true; break;
			default: if (in_array($type, CIA_preprocessor::$variableType)) return false;
			}

			if ($close)
			{
				$i = $j - 1;
				$j = implode('', $new_code);
				if (false === @eval($j . ';')) return false;
				$this->line += substr_count($j, "\n");
				return $j;
			}
			else $new_code[] = $token;
		}
	}

	protected static function export($var)
	{
		return
			is_string($var) ? "'" . str_replace("'", "\\'", str_replace('\\', '\\\\', $var)) . "'" : (
			is_bool($var)   ? ($var ? 'true' : 'false') : (
			is_null($var)   ? 'null' : (string) $var
		));
	}
}

abstract class CIA_preprocessor_bracket__0
{
	protected $preproc;
	protected $registered = false;
	protected $first;
	protected $position;
	protected $bracket;

	function __construct($preproc, $autoSetup = false)
	{
		$this->preproc = $preproc;
		$autoSetup && $this->setupFilter();
	}

	function setupFilter()
	{
		$this->popFilter();
		$this->preproc->pushFilter(array($this, 'filterToken'));
		$this->first = $this->registered = true;
		$this->position = 0;
		$this->bracket = 0;
	}

	function popFilter()
	{
		$this->registered && $this->preproc->popFilter();
		$this->registered = false;
	}

	function filterPreBracket($type, $token)
	{
		0>=$this->bracket
			&& T_WHITESPACE != $type && T_COMMENT != $type && T_DOC_COMMENT != $type
			&& $this->popFilter();
		return $token;
	}

	function filterBracket($type, $token) {return $token;}
	function onStart      ($token) {return $token;}
	function onReposition ($token) {return $token;}
	function onClose      ($token) {$this->popFilter(); return $token;}

	function filterToken($type, $token)
	{
		if ($this->first) $this->first = false;
		else switch ($type)
		{
		case '(':
			$token = 1<++$this->bracket ? $this->filterBracket($type, $token) : $this->onStart($token);
			break;

		case ')':
			$token = !--$this->bracket ? $this->onClose($token)
				: (0>$this->bracket ? $this->filterPreBracket($type, $token)
					: $this->filterBracket($type, $token)
				);
			break;

		case ',':
			if (1 == $this->bracket)
			{
				$token = $this->filterBracket($type, $token);
				++$this->position;
				$token = $this->onReposition($token);
				break;
			}

		default: $token = 0<$this->bracket ? $this->filterBracket($type, $token) : $this->filterPreBracket($type, $token);
		}

		return $token;
	}
}

class CIA_preprocessor_construct___0 extends CIA_preprocessor_bracket
{
	protected $source;
	protected $proto = '';
	protected $args = '';
	protected $num_args = 0;

	function __construct($preproc, &$source)
	{
		$this->source =& $source;
		parent::__construct($preproc, true);
	}

	function filterBracket($type, $token)
	{
		if (T_VARIABLE == $type)
		{
			$this->proto .=  '$p' . $this->num_args;
			$this->args  .= '&$p' . $this->num_args . ',';

			++$this->num_args;
		}
		else $this->proto .= $token;

		return $token;
	}

	function onClose($token)
	{
		$this->source = 'function __construct(' . $this->proto . ')'
			. '{$a=array(' . $this->args . ');'
			. 'if(' . $this->num_args . '<func_num_args()){$b=func_get_args();array_splice($a,0,' . $this->num_args . ',$b);}'
			. 'call_user_func_array(array($this,"' . $this->source . '"),$a);}';

		return parent::onClose($token);
	}
}

class CIA_preprocessor_path___0 extends CIA_preprocessor_bracket
{
	function onClose($token)
	{
		return parent::onClose(1 == $this->position ? ',' . $this->preproc->level . $token : $token);
	}
}

class CIA_preprocessor_classExists___0 extends CIA_preprocessor_bracket
{
	function onReposition($token)
	{
		return 1 == $this->position ? $token . '(' : (2 == $this->position ? ')||1' . $token : $token);
	}

	function onClose($token)
	{
		return parent::onClose(1 == $this->position ? ')||1' . $token : $token);
	}
}

class CIA_preprocessor_t___0 extends CIA_preprocessor_bracket
{
	function filterBracket($type, $token)
	{
		if (in_array($type, CIA_preprocessor::$variableType))
			W("File {$this->preproc->source} line {$this->preproc->line}:\nUsage of T() is potentially divergent.\nUse sprintf() instead of string concatenation.");

		return $token;
	}
}

class CIA_preprocessor_require___0 extends CIA_preprocessor_bracket
{
	public $close = ')';

	function filterPreBracket($type, $token) {return $this->filter($type, $token);}
	function filterBracket   ($type, $token) {return $this->filter($type, $token);}
	function onClose($token)                 {return $this->filter(')'  , $token);}

	function filter($type, $token)
	{
		switch ($type)
		{
		case '?': ++$this->bracket; break;
		case ',': if ($this->bracket) break;
		case ':': if ($this->bracket--) break;
		case ')': if (0<=$this->bracket) break;
		case T_AS: case T_CLOSE_TAG: case ';':
			$token = $this->close . $token;
			$this->popFilter();
		}

		return $token;
	}
}

class CIA_preprocessor_marker___0 extends CIA_preprocessor_require_
{
	public $curly = 0;

	function filter($type, $token)
	{
		if (T_WHITESPACE == $type || T_COMMENT == $type || T_DOC_COMMENT == $type) ;
		else if (0<=$this->curly) switch ($type)
		{
			case '$': break;
			case '{': ++$this->curly; break;
			case '}': --$this->curly; break;
			default: 0<$this->curly || $this->curly = -1;
		}
		else if (0>=$this->bracket && ($this->bracket || ')' != $type))
		{
			if (T_OBJECT_OPERATOR == $type) $this->curly = 0;
			else
			{
				$token = ':0)' . $token;
				$this->popFilter();
			}
		}

		return $token;
	}
}
