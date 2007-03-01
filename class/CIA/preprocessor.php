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

	static $variableType = array(
		T_EVAL, '(', T_FILE, T_LINE, T_FUNC_C, T_CLASS_C, T_INCLUDE, T_REQUIRE,
		T_VARIABLE, T_INCLUDE_ONCE, T_REQUIRE_ONCE, T_DOLLAR_OPEN_CURLY_BRACES,
	);

	protected $tokenFilter = array();

	static function run($source, $destination, $level, $class)
	{
		$preproc = new CIA_preprocessor;
		$preproc->source = $source = realpath($source);
		$code = file_get_contents($source);

		if (!preg_match("''u", $code)) W("File {$source}:\nfile encoding is not valid UTF-8. Please convert your source code to UTF-8.");

		$preproc->antePreprocess($code, $level, $class);


		$code =& $preproc->preprocess($code, $level, $class);

		$tmp = './' . uniqid(mt_rand(), true);

		file_put_contents($tmp, $code);

		if (CIA_WINDOWS)
		{
			$code = new COM('Scripting.FileSystemObject');
			$code->GetFile(CIA_PROJECT_PATH .'/'. $tmp)->Attributes |= 2; // Set hidden attribute
			file_exists($destination) && unlink($destination);
			rename($tmp, $destination);
		}
		else rename($tmp, $destination);
	}

	function pushFilter($filter)
	{
		array_unshift($this->tokenFilter, $filter);
	}

	function popFilter()
	{
		array_shift($this->tokenFilter);
	}

	protected function antePreprocess(&$code, $level, $class)
	{
		if (false !== strpos($code, "\r")) $code = strtr(str_replace("\r\n", "\n", $code), "\r", "\n");
		if (false !== strpos($code, '#>>>>>')) $code = preg_replace_callback("'^#>>>>>\s*^.*?^#<<<<<\s*$'ms", array('CIA_preprocessor', 'extractRxLF'), $code);
		if (DEBUG)
		{
			if (false !== strpos($code, '#>')) $code = preg_replace("'^#>([^>].*)$'m", '$1', $code);
		}
		else
		{
			if (false !== strpos($code, '#>>>')) $code = preg_replace_callback("'^#>>>\s*^.*?^#<<<\s*$'ms", array('CIA_preprocessor', 'extractRxLF'), $code);
		}

	}

	protected function &preprocess(&$code, $level, $class)
	{
		$source =  $this->source;
		$line   =& $this->line;
		$line   = 1;

		$code = token_get_all($code);
		$codeLen = count($code);

		$new_code = array();

		$curly_level = 0;
		$class_pool = array();

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

			case T_CURLY_OPEN:
			case T_DOLLAR_OPEN_CURLY_BRACES:
				++$curly_level;
				break;

			case T_CLASS_C:
				$token = $class_pool ? "'" . end($class_pool)->classname . "'" : $token;
				break;

			case T_CLASS:
				$c = '';

				// Look backward for the "final" keyword
				$j = $this->seekSugar($code, $i, true);
				$final = isset($code[$j]) && is_array($code[$j]) && T_FINAL == $code[$j][0];

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

			case T_PRIVATE:
				// "private static" methods or properties are problematic when considering application inheritance.
				// To work around this, we change them to "protected static", and warn about it
				// (except for files in the include path). Side effects exist but should be rare.
				if (isset($class_pool[$curly_level-1]) && !$class_pool[$curly_level-1]->is_final)
				{
					// Look backward for the "static" keyword
					$j = $this->seekSugar($code, $i, true);
					if (isset($code[$j]) && is_array($code[$j]) && T_STATIC == $code[$j][0]) $j = true;
					else
					{
						// Look forward for the "static" keyword
						$j = $this->seekSugar($code, $i);
						$j = isset($code[$j]) && is_array($code[$j]) && T_STATIC == $code[$j][0];
					}

					if ($j)
					{
						$token = 'protected';

						if (0<=$level) W("File {$source} line {$line}:\nprivate static methods or properties are fordidden.\nPlease use protected static ones instead.");
					}
				}

				break;

			case T_STRING:
				$lcToken = strtolower($token);

				if (!( isset($class_pool[$curly_level-1])
					&& ($c = $class_pool[$curly_level-1])
					&& !$c->has_php5_construct )) $c = false;

				switch ($lcToken)
				{
				case 'header':
					if ($class_pool && 'CIA' == end($class_pool)->classname) break 2;
					else break;

				case '__construct':
					if (!$c) break 2;
					else break;

				case 't':
				case '__cia_level__':
				case '__cia_file__':
				case 'resolvepath':
				case 'processpath':
				case 'self':
					if (0>$level) break 2;
					else break;

				case 'class_exists':
					if (0<=$level) break 2;
					else break;

				default:
					if ($c && $lcToken == strtolower($c->classname)) break;
					break 2;
				}

				$j = $this->seekSugar($code, $i, true);
				if (isset($code[$j]))
				{
					if (is_array($code[$j]))
					{
						if (T_DOUBLE_COLON == $code[$j][0] || T_OBJECT_OPERATOR == $code[$j][0]) break;
					}
					else if ('&' == $code[$j]) $j = $this->seekSugar($code, $j, true);

					if (is_array($code[$j]) && T_FUNCTION == $code[$j][0])
					{
						// If the currently parsed method is this class constructor
						// build a PHP5 constructor if needed.

						if ($c)
						{
							if ('__construct' == $lcToken) $c->has_php5_construct = true;
							else if ($lcToken == strtolower($c->classname))
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
				}

				switch ($lcToken)
				{
				case 'header': $token = 'CIA::header'; break;

				case 't':
					$token .= $this->fetchSugar($code, $i);
					if ('(' == $code[$i]) $bracket_pool[$bracket_level+1] = new CIA_preprocessor_t_($this);
					--$i;
					break;

				case '__cia_level__': $token = $level; break;
				case '__cia_file__':
					$token = "'" . str_replace(array('\\', "'"), array('\\\\', "\\'"), $source) . "'";
					break;

				case 'self':
					if ($class_pool)
					{
						// Replace every self::* by <__CLASS__>::*
						$token = $this->fetchSugar($code, $i);
						$token = (T_DOUBLE_COLON == $code[$i][0] ? end($class_pool)->classname : 'self') . $token;
						--$i;
					}

					break;

				case 'resolvepath':
				case 'processpath':
				case 'class_exists':
					$token .= $this->fetchSugar($code, $i);

					if ('(' == $code[$i])
					{
						$bracket_pool[$bracket_level+1] = 'class_exists' == $lcToken
							  // For files in the include_path, always set the 2nd arg of class_exists() to true
							? new CIA_preprocessor_classExists_($this)

							  // Automatically append their third arg to resolve|processPath
							: new CIA_preprocessor_path_($this, $level);
					}

					--$i;
					break;
				}

				break;

			case T_REQUIRE_ONCE:
			case T_INCLUDE_ONCE:
			case T_REQUIRE:
			case T_INCLUDE:
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

			case '{': ++$curly_level; break;
			case '}':
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
		}

		$token =& $code[$codeLen - 1];

		if (!is_array($token) || (T_CLOSE_TAG != $token[0] && T_INLINE_HTML != $token[0])) $new_code[] = '?>';

		return $new_code;
	}

	protected function seekSugar(&$code, $i, $backward = false)
	{
		$backward = $backward ? -1 : 1;

		while (
			isset($code[$i += $backward]) && is_array($code[$i]) && ($t = $code[$i][0])
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
	protected $level;
	protected $count = 0;
	protected $code = array();
	protected $is_const = true;
	protected $close = false;

	function __construct($preproc, $level)
	{
		$this->preproc = $preproc;
		$this->level = $level;
		$preproc->pushFilter(array($this, 'filterToken'));
	}

	function filterToken(&$type, $token)
	{
		$this->code[] = $token;

		if ($this->close)
		{
			$type = false;
			$this->code = implode('', $this->code);
			$this->preproc->popFilter();

			if ($this->is_const && false !== @eval('$token=' . $this->code . ';') && $token)
			{
				$this->code = var_export($token, true) . str_repeat("\n", substr_count($this->code, "\n"));
				$type = T_CONSTANT_ENCAPSED_STRING;
			}

			return $this->code;
		}
		else if (2 < ++$this->count && in_array($type, CIA_preprocessor::$variableType)) $this->is_const = false;

		return '';
	}

	function onClose($token)
	{
		$this->close = true;
		return 1 == $this->position ? ',' . $this->level . $token : $token;
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
		if (2 < ++$this->count && in_array($type, CIA_preprocessor::$variableType))
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
			case '(': ++$this->bracket_level; break;
			case ',': $this->bracket_level || $close = ','; break;
			case ')':
				if (!--$this->bracket_level)
				{
					$this->code[] = $token;
					$close = true;
				}
				break;

			case T_CLOSE_TAG:
			case ';': $close = $token; break;

			case T_START_HEREDOC: $this->hereDoc = true; break;
			case T_END_HEREDOC: $this->hereDoc = false; break;
			case T_STRING: $this->hereDoc || $this->is_const = false; break;

			default:
				in_array($type, CIA_preprocessor::$variableType) && $this->is_const = false;
			}

			if ($close)
			{
				if (true === $close) $close = '';

				$type = false;
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
