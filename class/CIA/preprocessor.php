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
class_exists('Reflection',false) || die('Extension "Reflection" is needed and not loaded.');


class CIA_preprocessor__0
{
	public $source;
	public $line = 1;
	public $level;
	public $class;
	public $marker;
	public $hereDoc = false;

	public $replaceFunction = array('header' => 'CIA::header');
	public $variableType = array(
		'', T_EVAL, '(', T_FILE, T_LINE, T_FUNC_C, T_CLASS_C, T_INCLUDE, T_REQUIRE,
		T_VARIABLE, T_INCLUDE_ONCE, T_REQUIRE_ONCE, T_DOLLAR_OPEN_CURLY_BRACES,
	);

	protected $tokenFilter = array();

	static function run($source, $destination, $level, $class)
	{
		$preproc = class_exists('CIA_preprocessor', false) ? 'CIA_preprocessor' : 'CIA_preprocessor__0';
		$preproc = new $preproc;
		$preproc->source = $source = realpath($source);
		$preproc->level = $level;
		$preproc->class = $class;
		$preproc->marker = '$_' . $GLOBALS['cia_paths_token'];

		$code = file_get_contents($source);

		if (!preg_match("''u", $code)) W("File {$source}:\nfile encoding is not valid UTF-8. Please convert your source code to UTF-8.");

		$preproc->antePreprocess($code);
		$code =& $preproc->preprocess($code);

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
		if (false !== strpos($code, '#>>>>>')) $code = preg_replace_callback("'^#>>>>>\s*^.*?^#<<<<<\s*$'ms", array($this, 'extractRxLF'), $code);
		if (DEBUG)
		{
			if (false !== strpos($code, '#>')) $code = preg_replace("'^#>([^>].*)$'m", '$1', $code);
		}
		else
		{
			if (false !== strpos($code, '#>>>')) $code = preg_replace_callback("'^#>>>\s*^.*?^#<<<\s*$'ms", array($this, 'extractRxLF'), $code);
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
		$antePrevTokenType = '';
		$prevTokenType = '';
		$new_code = array();
		$new_type = array();
		$new_code_length = 0;

		$curly_level = 0;
		$curly_starts_function = false;
		$class_pool = array();
		$remove_marker = array(0);
		$remove_marker_last =& $remove_marker[0];

		$bracket_level = 0;
		$bracket_pool = array();

		for ($i = 0; $i < $codeLen; ++$i)
		{
			$token = $code[$i];
			if (is_array($token))
			{
				$tokenType = $token[0];
				$token = $token[1];
			}
			else $tokenType = $token;

			switch ($tokenType)
			{
			case T_OPEN_TAG: // Normalize PHP open tag
				$token = '<?php ' . $this->extractLF($token);
				break;

			case T_OPEN_TAG_WITH_ECHO: // Normalize PHP open tag
				$token = '<?php echo ' . $this->extractLF($token);
				break;

			case T_CLOSE_TAG: // Normalize PHP close tag
				$token = $this->extractLF($token) . '?>';
				break;

			case T_START_HEREDOC: $this->hereDoc = true; break;
			case T_END_HEREDOC: $this->hereDoc = false; break;

			case T_CURLY_OPEN:
			case T_DOLLAR_OPEN_CURLY_BRACES:
				++$curly_level;
				break;

			case T_CLASS_C:
				if ($class_pool)
				{
					$token = "'" . end($class_pool)->classname . "'";
					$tokenType = T_CONSTANT_ENCAPSED_STRING;
				}
				break;

			case T_CLASS:
				$c = '';

				$final = T_FINAL == $prevTokenType;

				// Look forward
				$j = $this->seekSugar($code, $i);
				if (isset($code[$j]) && is_array($code[$j]) && T_STRING == $code[$j][0])
				{
					$token .= $this->fetchSugar($code, $i);

					$c = $code[$i][1];

					if ($final) $token .= $c;
					else
					{
						$c = preg_replace("'__[0-9]+$'", '', $c);
						$token .= $c . '__' . (0<=$level ? $level : '00');
					}

					$token .= $this->fetchSugar($code, $i);
				}

				if (!$c)
				{
					if ($class && 0<=$level)
					{
						$c = $class;
						$token .= ' ' . $c . (!$final ? '__' . $level : '');
					}

					$token .= $this->fetchSugar($code, $i);
				}

				$class_pool[$curly_level] = (object) array(
					'classname' => $c,
					'is_final' => $final,
					'has_php5_construct' => false,
					'construct_source' => '',
				);

				if ($c && isset($code[$i]) && is_array($code[$i]) && T_EXTENDS == $code[$i][0])
				{
					$token .= $code[$i][1];
					$token .= $this->fetchSugar($code, $i);
					if (isset($code[$i]) && is_array($code[$i]))
					{
						$token .= 0<=$level && 'self' == $code[$i][1] ? $c . '__' . ($level ? $level-1 : '00') : $code[$i][1];
					}
					else --$i;
				}
				else --$i;

				break;

			case T_VAR:
				if (0>$level)
				{
					$token = 'public';
					$tokenType = T_PUBLIC;
				}

				break;

			case T_PRIVATE:
				// "private static" methods or properties are problematic when considering application inheritance.
				// To work around this, we change them to "protected static", and warn about it
				// (except for files in the include path). Side effects exist but should be rare.
				if (isset($class_pool[$curly_level-1]) && !$class_pool[$curly_level-1]->is_final)
				{
					// Look backward for the "static" keyword
					if (T_STATIC == $prevTokenType) $j = true;
					else
					{
						// Look forward for the "static" keyword
						$j = $this->seekSugar($code, $i);
						$j = isset($code[$j]) && is_array($code[$j]) && T_STATIC == $code[$j][0];
					}

					if ($j)
					{
						$token = 'protected';
						$tokenType = T_PROTECTED;

						if (0<=$level) W("File {$source} line {$line}:\nprivate static methods or properties are fordidden.\nPlease use protected static ones instead.");
					}
				}

				break;

			case T_FUNCTION:
				$curly_starts_function = true;
				break;

			case T_NEW:
				$token .= $this->fetchSugar($code, $i);
				$variable = isset($code[$i]) && is_array($code[$i]) && T_STRING != $code[$i][0];
				--$i;

			case T_DOUBLE_COLON:
				if (
					   T_NEW == $tokenType
					|| (!$static_instruction
					&& is_string($prevTokenType)
					&& !isset($class_pool[$curly_level-1])
					&& !in_array($prevTokenType, array($class_pool ? strtolower(end($class_pool)->classname) : '', 'self', 'parent', 'this', 'static'))
				))
				{
					0>=$remove_marker_last || $remove_marker_last = -$remove_marker_last;;

					// Insert a marker at the beginning of the current instruction

					$b = $j = $new_code_length;
					$c = array();
					while (--$j) switch ($new_type[$j])
					{
					case '}':
						if (!$c)
						{
							$b = $j;
							do if ('}' == $new_type[$b]) $c[] = $b;
							while (--$b && in_array($new_type[$b], array('}', T_WHITESPACE, T_COMMENT, T_DOC_COMMENT)));
							if (';' == $new_type[$b]) break 2;
							$j = $b + 1;
							break;
						}

					case ')': $c[] = $j;
					case '': case '?': case ':': case ';': case ',':
					case T_DOUBLE_ARROW: case T_INCLUDE: case T_INCLUDE_ONCE:
					case T_REQUIRE: case T_REQUIRE_ONCE:
						if ($c) break;
						else    break 2;

					case '{':
					case '(':
						if ($c)
						{
							$b = array_pop($c);
							break;
						}
						break 2;

					case T_IF: case T_ELSEIF: case T_WHILE: case T_FOR: case T_FOREACH:
						$j = $b;

					case T_ELSE: case T_DO: case T_BREAK: case T_CONTINUE: case T_CASE:
					case T_DEFAULT: case T_ECHO: case T_RETURN: case T_THROW:
					case T_OPEN_TAG: case T_OPEN_TAG_WITH_ECHO:
						break 2;
					}

					$new_code[++$j] = "(({$this->marker}=__FILE__.'*" .  (T_NEW == $tokenType && $variable ? '-' : '') . mt_rand() . "')?" . $new_code[$j];
					$this->pushFilter(array(new CIA_preprocessor_new_($this), 'filterToken'));
				}

				break;

			case T_STRING:
				if ($this->hereDoc || T_DOUBLE_COLON == $prevTokenType || T_OBJECT_OPERATOR == $prevTokenType) break;

				$tokenType = strtolower($token);

				if (T_FUNCTION == $prevTokenType || ('&' == $prevTokenType && T_FUNCTION == $antePrevTokenType))
				{
					if (   isset($class_pool[$curly_level-1])
						&& ($c = $class_pool[$curly_level-1])
						&& !$c->has_php5_construct)
					{
						// If the currently parsed method is this class constructor
						// build a PHP5 constructor if needed.

						if ('__construct' == $tokenType) $c->has_php5_construct = true;
						else if ($tokenType == strtolower($c->classname))
						{
							$token .= $this->fetchSugar($code, $i);

							if ('(' == $code[$i])
							{
								$c->construct_source = $c->classname;
								$bracket_pool[$bracket_level+1] = new CIA_preprocessor_construct_($this, $c->construct_source);
							}

							--$i;
						}
					}

					break;
				}

				$c = $this->fetchSugar($code, $i);
				--$i;

				switch ($tokenType)
				{
				case '__cia_level__': if (0>$level) break;
					$token = $level;
					$tokenType = T_LNUMBER;
					break;

				case '__cia_file__': if (0>$level) break;
					$token = var_export($source, true);
					$tokenType = T_CONSTANT_ENCAPSED_STRING;
					break;

				case 'self':
					// Replace every self::* by <__CLASS__>::*
					if ($class_pool && is_array($code[$i+1]) && T_DOUBLE_COLON == $code[$i+1][0]) $token = end($class_pool)->classname;
					break;

				case 'resolvepath':
				case 'processpath': if (0>$level) break;
					// Automatically append their third arg to resolve|processPath
					if ('(' == $code[$i+1]) $bracket_pool[$bracket_level+1] = new CIA_preprocessor_path_($this);
					break;

				case 't': if (0>$level) break;
					if ('(' == $code[$i+1]) $bracket_pool[$bracket_level+1] = new CIA_preprocessor_t_($this);
					break;

				case 'interface_exists':
				case 'class_exists':
					// For files in the include_path, always set the 2nd arg of class|interface_exists() to true
					if (0>$level && '(' == $code[$i+1]) $bracket_pool[$bracket_level+1] = new CIA_preprocessor_classExists_($this);

				case 'set_exception_handler':
				case 'set_error_handler':
				case 'is_callable':
				case 'method_exists':
				case 'property_exists':
				case 'call_user_func':
				case 'call_user_func_array':
				case 'create_function':
					$remove_marker_last = 0;
				}

				if (
					isset($this->replaceFunction[$token])
					&& '(' == $code[$i+1]
					&& 0 !== stripos($this->replaceFunction[$token], $class . '::')
				) $token = $this->replaceFunction[$token] . $c;
				else $token .= $c;

				break;

			case T_EVAL: $remove_marker_last = 0; break;

			case T_REQUIRE_ONCE:
			case T_INCLUDE_ONCE:
			case T_REQUIRE:
			case T_INCLUDE:
				$remove_marker_last = 0;

				// Every require|include inside files in the include_path
				// is preprocessed thanks to processPath().
				if (0>$level) new CIA_preprocessor_adaptRequire_($this);
				break;

			case T_COMMENT: $tokenType = T_WHITESPACE;
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

			case ',':
				if (isset($bracket_pool[$bracket_level])) $token = $bracket_pool[$bracket_level]->incrementPosition($token);
				break;

			case '(':
				++$bracket_level;
				break;

			case ')':
				if (isset($bracket_pool[$bracket_level]))
				{
					$token = $bracket_pool[$bracket_level]->close($token);
					unset($bracket_pool[$bracket_level]);
				}

				--$bracket_level;

				break;

			case '{':
				++$curly_level;

				if ($curly_starts_function)
				{
					$curly_starts_function = false;
					$token .= "global {$this->marker};{$this->marker}=__FILE__.'*-" . mt_rand() . "';";
					$remove_marker_last =& $remove_marker[$curly_level];
					$remove_marker_last = $new_code_length;
				}

				break;

			case '}':
				if (isset($remove_marker[$curly_level]))
				{
					if ($remove_marker_last)
					{
						if (0>$remove_marker_last) 
						{
							$c = '$1';
							$remove_marker_last = -$remove_marker_last;
						}
						else $c = '';

						$new_code[$remove_marker_last] = preg_replace(
							"/(global \\{$this->marker};)\\{$this->marker}=__FILE__\.'\*-[0-9]+';/",
							$c,
							$new_code[$remove_marker_last]
						);
					}

					unset($remove_marker[$curly_level]);
					end($remove_marker);
					$remove_marker_last =& $remove_marker[key($remove_marker)];
				}

				--$curly_level;

				if (isset($class_pool[$curly_level]))
				{
					if (!$class_pool[$curly_level]->has_php5_construct) $token = $class_pool[$curly_level]->construct_source . '}';

					unset($class_pool[$curly_level]);
				}

				break;
			}

			if ($this->tokenFilter) foreach ($this->tokenFilter as $filter)
			{
				$token = call_user_func($filter, $tokenType, $token);
				if ('' === $token) continue 2;
			}

			$new_code[] = $token;
			$new_type[] = $tokenType;
			++$new_code_length;

			if (T_WHITESPACE != $tokenType && T_COMMENT != $tokenType && T_DOC_COMMENT != $tokenType)
			{
				$antePrevTokenType = $prevTokenType;
				$prevTokenType = $tokenType;
			}
		}

		$token =& $code[$codeLen - 1];

		if (!is_array($token) || (T_CLOSE_TAG != $token[0] && T_INLINE_HTML != $token[0])) $new_code[] = '?>';

		return $new_code;
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
}

abstract class CIA_preprocessor_bracket___0
{
	protected $preproc;
	protected $position = 0;

	function __construct($preproc)
	{
		$this->preproc = $preproc;
	}

	function onPositionUpdate($token) {return $token;}
	function onClose($token) {return $token;}

	function incrementPosition($token)
	{
		++$this->position;
		return $this->onPositionUpdate($token);
	}

	function close($token)
	{
		$token = $this->onClose($token);
		$this->position = 0;
		return $token;
	}
}

class CIA_preprocessor_construct___0 extends CIA_preprocessor_bracket_
{
	protected $source;
	protected $started = false;
	protected $num_args = 0;
	protected $proto = '';
	protected $args = '';


	function __construct($preproc, &$source)
	{
		$this->preproc = $preproc;
		$this->source =& $source;
		$preproc->pushFilter(array($this, 'filterToken'));
	}

	function filterToken($type, $token)
	{
		if ($this->started)
		{
			if (T_VARIABLE == $type)
			{
				$this->proto .=  '$p' . $this->num_args;
				$this->args  .= '&$p' . $this->num_args . ',';

				++$this->num_args;
			}
			else $this->proto .= $token;
		}
		else if ('(' == $type) $this->started = true;

		return $token;
	}

	function onClose($token)
	{
		$this->source = 'function __construct(' . $this->proto . ')'
			. '{$a=array(' . $this->args . ');'
			. 'if(' . $this->num_args . '<func_num_args()){$b=func_get_args();array_splice($a,0,' . $this->num_args . ',$b);}'
			. 'call_user_func_array(array($this,"' . $this->source . '"),$a);}';

		$this->preproc->popFilter();

		return $token;
	}
}

class CIA_preprocessor_path___0 extends CIA_preprocessor_bracket_
{
	protected $count = 0;
	protected $code = array();
	protected $is_const = true;
	protected $close = false;

	function __construct($preproc)
	{
		$this->preproc = $preproc;
		$preproc->pushFilter(array($this, 'filterToken'));
	}

	function filterToken(&$type, $token)
	{
		$this->code[] = $token;

		if ($this->close)
		{
			$type = '';
			$this->code = implode('', $this->code);
			$this->preproc->popFilter();

			if ($this->is_const && false !== @eval('$token=' . $this->code . ';') && $token)
			{
				$this->code = var_export($token, true) . str_repeat("\n", substr_count($this->code, "\n"));
				$type = T_CONSTANT_ENCAPSED_STRING;
			}

			return $this->code;
		}
		else if (2 < ++$this->count && in_array($type, $this->preproc->variableType)) $this->is_const = false;

		return '';
	}

	function onClose($token)
	{
		$this->close = true;
		return 1 == $this->position ? ',' . $this->preproc->level . $token : $token;
	}
}

class CIA_preprocessor_classExists___0 extends CIA_preprocessor_bracket_
{
	function onPositionUpdate($token)
	{
		return 1 == $this->position ? $token . '(' : (2 == $this->position ? ')||1' . $token : $token);
	}

	function onClose($token)
	{
		return 1 == $this->position ? ')||1' . $token : $token;
	}
}

class CIA_preprocessor_t___0 extends CIA_preprocessor_bracket_
{
	public $count = 0;

	function __construct($preproc)
	{
		$this->preproc = $preproc;
		$preproc->pushFilter(array($this, 'filterToken'));
	}

	function onClose($token)
	{
		$this->preproc->popFilter();
		return $token;
	}

	function filterToken($type, $token)
	{
		if (2 < ++$this->count && in_array($type, $this->preproc->variableType))
			W("File {$this->preproc->source} line {$this->preproc->line}:\nUsage of T() is potentially divergent.\nUse sprintf() instead of string concatenation.");

		return $token;
	}
}

class CIA_preprocessor_adaptRequire___0
{
	protected $preproc;
	protected $start = true;
	protected $code = array();
	protected $is_const = true;
	protected $bracket_level = 0;
	protected $hereDoc = false;

	function __construct($preproc)
	{
		$this->preproc = $preproc;
		$preproc->pushFilter(array($this, 'filterToken'));
	}

	function filterToken(&$type, $token)
	{
		if ($this->start)
		{
			$this->start = false;
			return $token;
		}
		else
		{
			$close = '';

			switch ($type)
			{
			case T_STRING: $this->preproc->hereDoc || $this->is_const = false; break;
			case '(': ++$this->bracket_level; break;
			case ')': $this->bracket_level-- || $close = $token; break;

			case '?': case ':': case ',':
				$this->bracket_level || $close = $token; break;

			case T_AS: case T_CLOSE_TAG: case ';':
				$close = $token; break;

			default:
				in_array($type, $this->preproc->variableType) && $this->is_const = false;
			}

			if ($close)
			{
				$type = '';
				$this->code = 'cia_adaptRequire(' . implode('', $this->code) . ')';
				$this->preproc->popFilter();

				if ($this->is_const && false !== @eval('$token=' . $this->code . ';'))
				{
					$this->code = var_export($token, true) . str_repeat("\n", substr_count($this->code, "\n"));
					$type = T_CONSTANT_ENCAPSED_STRING;
				}

				return ' ' . $this->code . $close;
			}
			else
			{
				$this->code[] = $token;
				return '';
			}
		}
	}
}

class CIA_preprocessor_new___0
{
	protected $preproc;
	protected $bracket_level = 0;

	function __construct($preproc)
	{
		$this->preproc = $preproc;
	}

	function filterToken($type, $token)
	{
		$close = '';

		switch ($type)
		{
		case '(': ++$this->bracket_level; break;
		case ')': $this->bracket_level-- || $close = $token; break;

		case '?': case ':': case ',':
			$this->bracket_level || $close = $token; break;

		case T_AS: case T_CLOSE_TAG: case ';':
			$close = $token; break;
		}

		if ($close)
		{
			$this->preproc->popFilter();
			$token = ':0)' . $close;
		}

		return $token;
	}
}
