<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


defined('T_DIR') || define('T_DIR', 378); // T_DIR exists since PHP 5.3

class patchwork_preprocessor__0
{
	public

	$source,
	$line = 1,
	$level,
	$class,
	$marker,
	$inString = 0;


	protected $tokenFilter = array();


	static

	$constant = array(
		'DEBUG' => DEBUG,
		'UTF8_BOM' => UTF8_BOM,
		'IS_WINDOWS' => IS_WINDOWS,
		'PATCHWORK_ZCACHE' => PATCHWORK_ZCACHE,
		'PATCHWORK_PATH_LEVEL' => PATCHWORK_PATH_LEVEL,
		'PATCHWORK_PATH_OFFSET' => PATCHWORK_PATH_OFFSET,
	),

	$function = array(
		'ob_start'   => 'ob::start',
		'rand'       => 'mt_rand',
		'srand'      => 'mt_srand',
		'getrandmax' => 'mt_getrandmax',

		'w'            => 'trigger_error',
		'header'       => 'patchwork::header',
		'setcookie'    => 'patchwork::setcookie',
		'setrawcookie' => 'patchwork::setrawcookie',
	),

	$variableType = array(
		T_EVAL, '(', T_FILE, T_LINE, T_FUNC_C, T_CLASS_C, T_INCLUDE, T_REQUIRE, T_CURLY_OPEN,
		T_VARIABLE, '$', T_INCLUDE_ONCE, T_REQUIRE_ONCE, T_DOLLAR_OPEN_CURLY_BRACES, T_DIR,
	),

	// List of native functions that could trigger __autoload()
	$callback = array(
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
		'unserialize'             => 0,

		// Classname as string in the first parameter
		'__autoload'        => -1,
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


	private static

	$declared_class = array('self' => 1, 'parent' => 1, 'this' => 1, 'static' => 1, 'p' => 1),
	$inline_class,
	$recursive = false;


	static function __constructStatic()
	{
		defined('E_RECOVERABLE_ERROR') || self::$constant['E_RECOVERABLE_ERROR'] = E_ERROR;

		if (IS_WINDOWS && (DEBUG || PHP_VERSION < '5.2'))
		{
			// In debug mode, checks if character case is strict.
			// Fix a bug with long file names.
			self::$function += array(
				'file_exists'   => 'win_file_exists',
				'is_file'       => 'win_is_file',
				'is_dir'        => 'win_is_dir',
				'is_link'       => 'win_is_link',
				'is_executable' => 'win_is_executable',
				'is_readable'   => 'win_is_readable',
				'is_writable'   => 'win_is_writable',
				'is_writeable'  => 'win_is_writable',
				'stat'          => 'win_stat',
			);
		}

		class_exists('patchwork', false) && self::$function += array(
			'header'       => 'patchwork::header',
			'setcookie'    => 'patchwork::setcookie',
			'setrawcookie' => 'patchwork::setrawcookie',
		);

		foreach (get_declared_classes() as $v)
		{
			$v = lowerascii($v);
			if ('patchwork_' === substr($v, 0, 10)) break;
			self::$declared_class[$v] = 1;
		}

		// As of PHP5.1.2, hash('md5', $str) is a lot faster than md5($str) !
		extension_loaded('hash') && self::$function += array(
			'md5'   => "hash('md5',",
			'sha1'  => "hash('sha1',",
			'crc32' => "hash('crc32',",
		);

		if (!function_exists('mb_stripos'))
		{
			self::$function += array(
				'mb_stripos'  => 'utf8_mbstring_520::stripos',
				'mb_stristr'  => 'utf8_mbstring_520::stristr',
				'mb_strrchr'  => 'utf8_mbstring_520::strrchr',
				'mb_strrichr' => 'utf8_mbstring_520::strrichr',
				'mb_strripos' => 'utf8_mbstring_520::strripos',
				'mb_strrpos'  => 'utf8_mbstring_520::strrpos',
				'mb_strstr'   => 'utf8_mbstring_520::strstr',
				'mb_strrpos_500' => 'mb_strrpos',
			);

			if (!extension_loaded('mbstring'))
			{
				self::$constant += array(
					'MB_OVERLOAD_MAIL'   => 1,
					'MB_OVERLOAD_STRING' => 2,
					'MB_OVERLOAD_REGEX'  => 4,
					'MB_CASE_UPPER' => 0,
					'MB_CASE_LOWER' => 1,
					'MB_CASE_TITLE' => 2
				);

				self::$function += array(
					'mb_convert_encoding'     => 'utf8_mbstring_500::convert_encoding',
					'mb_decode_mimeheader'    => 'utf8_iconv::mime_decode',
					'mb_convert_case'         => 'utf8_mbstring_500::convert_case',
					'mb_encode_mimeheader'    => 'E(\'mb_encode_mimeheader() is bugged. Please use iconv_mime_encode() instead.\',',
					'mb_internal_encoding'    => 'utf8_mbstring_500::internal_encoding',
					'mb_list_encodings'       => 'utf8_mbstring_500::list_encodings',
					'mb_parse_str'            => 'parse_str',
					'mb_strlen'               => extension_loaded('xml') ? 'utf8_mbstring_500::strlen' : 'utf8_mbstring_500::strlen2',
					'mb_strpos'               => 'utf8_mbstring_500::strpos',
					'mb_strrpos_500'          => 'utf8_mbstring_500::mb_strrpos',
					'mb_strtolower'           => 'utf8_mbstring_500::strtolower',
					'mb_strtoupper'           => 'utf8_mbstring_500::strtoupper',
					'mb_substitute_character' => 'utf8_mbstring_500::substitute_character',
					'mb_substr_count'         => 'substr_count',
					'mb_substr'               => 'utf8_mbstring_500::substr',
				);
			}
		}

		if (extension_loaded('iconv') && 'fi' === @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'ﬁ'))
		{
			// iconv is way faster than mbstring
			self::$function['mb_strlen']            = 'iconv_strlen';
			self::$function['mb_strpos']            = 'iconv_strpos';
			self::$function['mb_strrpos_500']       = 'iconv_strrpos';
			self::$function['mb_substr']            = 'iconv_substr';
			self::$function['mb_decode_mimeheader'] = 'iconv_mime_decode';
		}
		else
		{
			self::$constant += array(
				'ICONV_IMPL' => '"patchworkiconv"',
				'ICONV_VERSION' => '1.0',
				'ICONV_MIME_DECODE_STRICT' => 1,
				'ICONV_MIME_DECODE_CONTINUE_ON_ERROR' => 2,
			);

			self::$function += array(
				'iconv' => 'utf8_iconv::iconv',
				'iconv_get_encoding' => 'utf8_iconv::get_encoding',
				'iconv_set_encoding' => 'utf8_iconv::set_encoding',
				'iconv_mime_decode'  => 'utf8_iconv::mime_decode',
				'iconv_mime_encode'  => 'utf8_iconv::mime_encode',
				'ob_iconv_handler'   => 'utf8_iconv::ob_handler',
				'iconv_mime_decode_headers' => 'utf8_iconv::mime_decode_headers',
			);

			if (extension_loaded('mbstring'))
			{
				self::$function += array(
					'iconv_strlen'  => 'mb_strlen',
					'iconv_strpos'  => 'mb_strpos',
					'iconv_strrpos' => 'mb_strrpos',
					'iconv_substr'  => 'mb_substr',
				);

				self::$function['iconv_mime_decode'] = 'mb_decode_mimeheader';
			}
			else
			{
				self::$function += array(
					'iconv_strlen'  => 'utf8_mbstring_500::strlen',
					'iconv_strpos'  => 'utf8_mbstring_500::strpos',
					'iconv_strrpos' => 'utf8_mbstring_500::strrpos',
					'iconv_substr'  => 'utf8_mbstring_500::substr',
				);
			}
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

	static function execute($source, $destination, $level, $class)
	{
		$recursive = self::$recursive;

		if (!$recursive)
		{
			self::$inline_class = self::$declared_class;
			self::$recursive = true;
		}

		$preproc = new patchwork_preprocessor;
		$preproc->source = $source = realpath($source);
		$preproc->level = $level;
		$preproc->class = $class;
		$preproc->marker = array(
			'global $a' . PATCHWORK_PATH_TOKEN . ',$c' . PATCHWORK_PATH_TOKEN . ';',
			'global $a' . PATCHWORK_PATH_TOKEN . ',$b' . PATCHWORK_PATH_TOKEN . ',$c' . PATCHWORK_PATH_TOKEN . ';'
		);

		$code = file_get_contents($source);
		UTF8_BOM === substr($code, 0, 3) && $code = substr($code, 3);

		if (!preg_match('//u', $code)) trigger_error("File {$source}:\nfile encoding is not valid UTF-8. Please convert your source code to UTF-8.");

		$preproc->antePreprocess($code);
		$code =& $preproc->preprocess($code);

		self::$recursive = $recursive;

		$tmp = './' . uniqid(mt_rand(), true);
		if (false !== file_put_contents($tmp, $code))
		{
			if (IS_WINDOWS)
			{
				$code = new COM('Scripting.FileSystemObject');
				$code->GetFile(PATCHWORK_PROJECT_PATH . $tmp)->Attributes |= 2; // Set hidden attribute
				file_exists($destination) && @unlink($destination);
				@rename($tmp, $destination) || unlink($tmp);
			}
			else rename($tmp, $destination);
		}
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
			$token = $code[$i];
			if (is_array($token))
			{
				$type = $token[0];
				$token = $token[1];
			}
			else $type = ($this->inString & 1) && '"' !== $token && '`' !== $token ? T_ENCAPSED_AND_WHITESPACE : $token;

			// Reduce memory usage
			unset($code[$i]);

			switch ($type)
			{
			case T_OPEN_TAG_WITH_ECHO:
				$code[$i--] = array(T_ECHO, 'echo');
				$code[$i--] = array(T_OPEN_TAG, '<?php ' . $this->extractLF($token));
				continue 2;

			case T_OPEN_TAG: // Normalize PHP open tag
				$token = '<?php ' . $opentag_marker . $this->extractLF($token);
				$opentag_marker = '';
				break;

			case T_CLOSE_TAG: // Normalize PHP close tag
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
					else
					{
						$c = preg_replace("'__[0-9]+$'", '', $c);
						if ($c) $b = $c . '__' . (0<=$level ? $level : '00');
						else $c = $b;
						$token .= $b;
					}
				}
				else if ($class && 0<=$level)
				{
					$c = $class;
					$b = $c . (!$final ? '__' . $level : '');
					$token .= ' ' . $b;
				}

				$token .= $this->fetchSugar($code, $i);

				$class_pool[$curly_level] = (object) array(
					'classname' => $c,
					'real_classname' => lowerascii($b),
					'is_root' => true,
					'is_final' => $final,
					'add_php5_construct' => T_CLASS === $type && 0>$level,
					'add_constructStatic' => 0,
					'add_destructStatic'  => 0,
					'construct_source' => '',
				);

				if (T_ABSTRACT === $prevType)
				{
					$j = $new_code_length;
					while (--$j && in_array($new_type[$j], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ;
					$new_code[$j] = "\$GLOBALS['patchwork_abstract']['{$class_pool[$curly_level]->real_classname}']=1;" . $new_code[$j];
				}

				self::$inline_class[lowerascii($c)] = 1;

				if ($c && isset($code[$i]) && is_array($code[$i]) && T_EXTENDS === $code[$i][0])
				{
					$class_pool[$curly_level]->is_root = false;

					$token .= $code[$i][1];
					$token .= $this->fetchSugar($code, $i);
					if (isset($code[$i]) && is_array($code[$i]))
					{
						$c = 0<=$level && 'self' === $code[$i][1] ? $c . '__' . ($level ? $level-1 : '00') : $code[$i][1];
						$token .= $c;
						self::$inline_class[lowerascii($c)] = 1;
					}
					else --$i;
				}
				else
				{
					if (T_CLASS === $type)
					{
						$class_pool[$curly_level]->add_constructStatic = 2;
						$class_pool[$curly_level]->add_destructStatic = 2;
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

						if (0<=$level) trigger_error("File {$source} line {$line}:\nprivate static methods or properties are banned.\nPlease use protected static ones instead.");
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

				$c = true;

				if (is_array($code[$j]) && T_STRING === $code[$j][0])
				{
					if (isset(self::$inline_class[lowerascii($code[$j][1])])) break;
					$c = false;
				}

				if ($c)
				{
					$curly_marker_last[1]>0 || $curly_marker_last[1] =  1;
					$c = "\$a{$T}=\$b{$T}=\$e{$T}";
				}
				else
				{
					$curly_marker_last[1]   || $curly_marker_last[1] = -1;
					$c = $this->marker($code[$j][1]);
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
						|| isset(self::$inline_class[$prevType])
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
							if (T_OBJECT_OPERATOR != $new_type[$j] && ++$j) break 2;
						}
						break;

					case '{': case '[': --$b; break;
					case '}': case ']': ++$b; break;
					}
					while (--$j);

					while (in_array($new_type[$j], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT))) ++$j;

					$new_code[$j] = "(({$c})?" . $new_code[$j];
				}

				new __patchwork_preprocessor_marker($this, true);

				break;

			case T_STRING:
				if (($this->inString & 1) || T_DOUBLE_COLON === $prevType || T_OBJECT_OPERATOR === $prevType) break;

				$type = lowerascii($token);

				if (T_FUNCTION === $prevType || ('&' === $prevType && T_FUNCTION === $antePrevType))
				{
					if (isset($class_pool[$curly_level-1]) && $c = $class_pool[$curly_level-1])
					{
						switch ($type)
						{
						case '__constructstatic': $c->add_constructStatic = 1 ; break;
						case '__destructstatic' : $c->add_destructStatic  = 1 ; break;
						case '__construct'      : $c->add_php5_construct  = false; break;

						case lowerascii($c->classname):
							$c->construct_source = $c->classname;
							new __patchwork_preprocessor_construct($this, $c->construct_source);
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

				if ('(' === $code[$i+1])
				{
					if (isset(self::$function[$type]))
					{
						if (self::$function[$type] instanceof __patchwork_preprocessor_bracket)
						{
							$token .= $c;
							$c = clone self::$function[$type];
							$c->setupFilter();
							break;
						}
						else if (0 !== stripos(self::$function[$type], $class . '::'))
						{
							$token = self::$function[$type];
							if (false !== strpos($token, '(')) ++$i && $type = '(';
							else $type = lowerascii($token);
						}
					}

					switch ($type)
					{
					case 'resolvepath':
						// Append its third arg to resolvePath
						if (0<=$level) new __patchwork_preprocessor_path($this, true);
						break;

					case 't':
						if (0<=$level) new __patchwork_preprocessor_t($this, true);
						break;

					case 'is_a':
						if (0>$level) $type = $token = 'patchwork_is_a';

					default:
						if (!isset(self::$callback[$type])) break;

						$token = "((\$a{$T}=\$b{$T}=\$e{$T})||1?{$token}";
						$b = new __patchwork_preprocessor_marker($this, true);
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
						if (0>$level && in_array($type, array('interface_exists', 'class_exists'))) new __patchwork_preprocessor_classExists($this, true);
					}
				}
				else if (!(is_array($code[$i+1]) && T_DOUBLE_COLON === $code[$i+1][0])) switch ($type)
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
				else if ('self' === $type && $class_pool) $token = end($class_pool)->classname; // Replace every self::* by __CLASS__::*
				else if ('p' === $type) $token = 'patchwork';
				else if ('s' === $type) $token = 'SESSION';

				$token .= $c;

				break;

			case T_EVAL:
				$token .= $this->fetchSugar($code, $i);
				if ('(' === $code[$i--])
				{
					$token = "((\$a{$T}=\$b{$T}=\$e{$T})||1?{$token}";
					$b = new __patchwork_preprocessor_marker($this, true);
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
						$j = (string) $this->fetchConstant($code, $i, $codeLen);
						if ( '' !== $j)
						{
							eval("\$b=patchworkProcessedPath({$j});");
							$code[$i--] = array(
								T_CONSTANT_ENCAPSED_STRING,
								false !== $b ? patchwork_preprocessor::export($b, substr_count($j, "\n")) : "patchworkProcessedPath({$j})"
							);
						}
						else
						{
							$token .= 'patchworkProcessedPath(';
							new __patchwork_preprocessor_require($this, true);
						}
					}
					else
					{
						$token .= "((\$a{$T}=\$b{$T}=\$e{$T})||1?";
						$b = new __patchwork_preprocessor_require($this, true);
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
				isset($class_pool[$curly_level])
					&& $class_pool[$curly_level]->is_final
					&& $token .= "static \$hunter{$T};";

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

					if ($c->add_php5_construct) $token = $c->construct_source . '}';

					if ($c->add_constructStatic)
					{
						$token = "const __cS{$T}=" . (1 === $c->add_constructStatic ? "'{$c->classname}';" : "'';static function __constructStatic(){}") . $token;
					}

					if ($c->add_destructStatic)
					{
						$token = "const __dS{$T}=" . (1 === $c->add_destructStatic  ? "'{$c->classname}';" : "'';static function __destructStatic() {}") . $token;
					}

					$token .= "\$GLOBALS['c{$T}']['{$c->real_classname}']=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "';";

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
		return ($class ? 'isset($c' . PATCHWORK_PATH_TOKEN . "['" . lowerascii($class) . "'])||" : ('$e' . PATCHWORK_PATH_TOKEN . '=$b' . PATCHWORK_PATH_TOKEN . '=')) . '$a' . PATCHWORK_PATH_TOKEN . "=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "'";
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

	protected function fetchConstant(&$code, &$i, $codeLen)
	{
		if (DEBUG || !TURBO) return false;

		$new_code = array();
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
			else $type = $inString && '"' !== $token && '`' !== $token ? T_ENCAPSED_AND_WHITESPACE : $token;

			$close = '';

			switch ($type)
			{
			case '`': return false;

			case '"':             $inString = !$inString;  break;
			case T_START_HEREDOC: $inString = true;        break;
			case T_END_HEREDOC:   $inString = false;       break;
			case T_STRING:   if (!$inString) return false; break;

			case '?':
			case '(':
			case '{':
			case '[':
				++$bracket;
				break;

			case ':':
			case ')':
			case '}':
			case ']':
				$bracket-- || $close = true;
				break;

			case ',':
				$bracket   || $close = true;
				break;

			case T_AS:
			case T_CLOSE_TAG:
			case ';':
				$close = true;
				break;

			default:
				if (in_array($type, self::$variableType)) return false;
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

	static function export($a, $lf = 0)
	{
		if (is_array($a))
		{
			if ($a)
			{
				$b = array();
				foreach ($a as $k => &$a) $b[] = self::export($k) . '=>' . self::export($a);
				$b = 'array(' . implode(',', $b) . ')';
			}
			else return 'array()';
		}
		else if (is_object($a))
		{
			$b = array();
			$v = (array) $a;
			foreach ($v as $k => &$v)
			{
				if ("\0" === substr($k, 0, 1)) $k = substr($k, 3);
				$b[$k] =& $v;
			}

			$b = self::export($b);
			$b = get_class($a) . '::__set_state(' . $b . ')';
		}
		else if (is_string($a) && strspn($a, "\r\n\0"))
		{
			$b = '"'. str_replace(
				array(  "\\",   '"',   '$',  "\r",  "\n",  "\0"),
				array('\\\\', '\\"', '\\$', '\\r', '\\n', '\\0'),
				$a
			) . '"';
		}
		else $b = is_string($a) ? "'" . str_replace("'", "\\'", str_replace('\\', '\\\\', $a)) . "'" : (
			is_bool($a)   ? ($a ? 'true' : 'false') : (
			is_null($a)   ? 'null' : (string) $a
		));

		$lf && $b .= str_repeat("\n", $lf);

		return $b;
	}
}

class __patchwork_preprocessor_bracket__0
{
	protected

	$preproc,
	$registered = false,
	$first,
	$position,
	$bracket;


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
			if (1 === $this->bracket)
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

class __patchwork_preprocessor_construct__0 extends __patchwork_preprocessor_bracket
{
	protected

	$source,
	$proto = '',
	$args = '',
	$num_args = 0;


	function __construct($preproc, &$source)
	{
		$this->source =& $source;
		parent::__construct($preproc, true);
	}

	function filterBracket($type, $token)
	{
		if (T_VARIABLE === $type)
		{
			$this->proto .=  '$a' . $this->num_args;
			$this->args  .= '&$a' . $this->num_args . ',';

			++$this->num_args;
		}
		else $this->proto .= $token;

		return $token;
	}

	function onClose($token)
	{
		$this->source = 'function __construct(' . $this->proto . ')'
			. '{$a=array(' . $this->args . ');'
			. 'if(' . $this->num_args . '<func_num_args())$a+=func_get_args();'
			. 'call_user_func_array(array($this,"' . $this->source . '"),$a);}';

		return parent::onClose($token);
	}
}

class __patchwork_preprocessor_path__0 extends __patchwork_preprocessor_bracket
{
	function onClose($token)
	{
		return parent::onClose(1 === $this->position ? ',' . $this->preproc->level . $token : $token);
	}
}

class __patchwork_preprocessor_classExists__0 extends __patchwork_preprocessor_bracket
{
	function onReposition($token)
	{
		return 1 === $this->position ? $token . '(' : (2 === $this->position ? ')||1' . $token : $token);
	}

	function onClose($token)
	{
		return parent::onClose(1 === $this->position ? ')||1' . $token : $token);
	}
}

class __patchwork_preprocessor_t__0 extends __patchwork_preprocessor_bracket
{
	function filterBracket($type, $token)
	{
		if ('.' === $type) trigger_error(
"File {$this->preproc->source} line {$this->preproc->line}:
Usage of T() is potentially divergent.
Please use sprintf() instead of string concatenation."
		);

		return $token;
	}
}

class __patchwork_preprocessor_require__0 extends __patchwork_preprocessor_bracket
{
	public $close = ')';

	function filterPreBracket($type, $token) {return $this->filter($type, $token);}
	function filterBracket   ($type, $token) {return $this->filter($type, $token);}
	function onClose($token)                 {return $this->filter(')'  , $token);}

	function filter($type, $token)
	{
		switch ($type)
		{
		case '{':
		case '[':
		case '?': ++$this->bracket; break;
		case ',': if ($this->bracket) break;
		case '}':
		case ']':
		case ':': if ($this->bracket--) break;
		case ')': if (0<=$this->bracket) break;
		case T_AS: case T_CLOSE_TAG: case ';':
			$token = $this->close . $token;
			$this->popFilter();
		}

		return $token;
	}
}

class __patchwork_preprocessor_marker__0 extends __patchwork_preprocessor_require
{
	public

	$close = ':0)',
	$greedy = false,
	$curly = 0;


	function filter($type, $token)
	{
		if ($this->greedy) return parent::filter($type, $token);

		if (T_WHITESPACE === $type || T_COMMENT === $type || T_DOC_COMMENT === $type) ;
		else if (0<=$this->curly) switch ($type)
		{
			case '$': break;
			case '{': ++$this->curly; break;
			case '}': --$this->curly; break;
			default: 0<$this->curly || $this->curly = -1;
		}
		else
		{
			if ('?' === $type) --$this->bracket;
			$token = parent::filter($type, $token);
			if (':' === $type) ++$this->bracket;

			if (0<$this->bracket || !$this->registered) return $token;

			switch ($type)
			{
			case '}':
			case ']':
			case T_INC:
			case T_DEC:
				break;

			case '=':
			case T_DIV_EQUAL:
			case T_MINUS_EQUAL:
			case T_MOD_EQUAL:
			case T_MUL_EQUAL:
			case T_PLUS_EQUAL:
			case T_SL_EQUAL:
			case T_SR_EQUAL:
			case T_XOR_EQUAL:
			case T_AND_EQUAL:
			case T_OR_EQUAL:
			case T_CONCAT_EQUAL:
				$this->greedy = true;
				break;

			case T_OBJECT_OPERATOR:
				$this->curly = 0;
				break;

			case ')':
				$token .= $this->close;
				$this->popFilter();
				break;

			default:
				$token = $this->close . $token;
				$this->popFilter();
			}
		}

		return $token;
	}
}
