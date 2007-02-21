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
	public static $tokenFilter = false;

	protected static $escapedSource;

	static function run($source, $destination, $level, $class)
	{
		$source = realpath($source);
		CIA_preprocessor::$escapedSource = "'" . str_replace(array('\\', "'"), array('\\\\', "\\'"), $source) . "'";

		$code = file_get_contents($source);
		CIA_preprocessor::antePreprocess($code, $level, $class);


		$code =& CIA_preprocessor::preprocess($code, $level, $class);

		$tmp = './' . uniqid(mt_rand(), true);

		file_put_contents($tmp, $code);

		if (CIA_WINDOWS)
		{
			$code = new COM('Scripting.FileSystemObject');
			$code->GetFile(CIA_PROJECT_PATH .'/'. $tmp)->Attributes |= 2; // Set hidden attribute
			file_exists($destination) && unlink($destination);
			@rename($tmp, $destination) || E('Failed rename');
		}
		else rename($tmp, $destination);
	}

	protected static function antePreprocess(&$code, $level, $class)
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

	protected static function &preprocess(&$code, $level, $class)
	{
		$code = token_get_all($code);
		$codeLen = count($code);

		$new_code = array();

		$curly_level = 0;
		$class_pool = array();

		$bracket_level = 0;
		$bracket_pool = array();

		$instructionSuffix = array();

		for ($i = 0; $i < $codeLen; ++$i)
		{
			$token = $code[$i];
			if (is_array($token))
			{
				$tokenType = $token[0];
				$token = $token[1];
			}
			else $tokenType = false;

			switch ($tokenType)
			{
			case T_OPEN_TAG: // Normalize PHP open tag
				$token = '<?php ' . CIA_preprocessor::extractLF($token);
				break;

			case T_OPEN_TAG_WITH_ECHO: // Normalize PHP open tag
				$token = '<?php echo ' . CIA_preprocessor::extractLF($token);
				break;

			case T_CLOSE_TAG: // Normalize PHP close tag
				if (isset($instructionSuffix[$bracket_level]))
				{
					$token = $instructionSuffix[$bracket_level] . CIA_preprocessor::extractLF($token) . '?>';
					unset($instructionSuffix[$bracket_level]);
				}
				else $token = CIA_preprocessor::extractLF($token) . '?>';

				break;

			case T_CURLY_OPEN:
			case T_DOLLAR_OPEN_CURLY_BRACES:
				++$curly_level;
				break;

			case T_CLASS_C:
				$token = $class_pool ? "'" . end($class_pool)->classname . "'" : $token;
				break;

			case T_CLASS:
				// Look backward for the "final" keyword
				$j = 0;
				do $t = isset($code[$i - (++$j)]) && is_array($code[$i-$j]) ? $code[$i-$j][0] : false;
				while (T_COMMENT == $t || T_WHITESPACE == $t || T_DOC_COMMENT == $t);

				$final = isset($code[$i-$j]) && is_array($code[$i-$j]) && T_FINAL == $code[$i-$j][0];


				$c = '';

				// Look forward
				$j = 0;
				do $t = isset($code[$i + (++$j)]) && is_array($code[$i+$j]) ? $code[$i+$j][0] : false;
				while (T_COMMENT == $t || T_WHITESPACE == $t || T_DOC_COMMENT == $t);

				if (isset($code[$i+$j]) && is_array($code[$i+$j]) && T_STRING == $code[$i+$j][0])
				{
					$token .= CIA_preprocessor::fetchSugar($code, $i);

					$c = $code[$i][1];

					if ($final) $token .= $c;
					else
					{
						$c = preg_replace("'__[0-9]+$'", '', $c);
						$token .= $c . '__' . (0<=$level ? $level : '00');
					}

					$token .= CIA_preprocessor::fetchSugar($code, $i);
				}

				if (!$c)
				{
					if ($class && 0<=$level)
					{
						$c = $class;
						$token .= ' ' . $c . (!$final ? '__' . $level : '');
					}

					$token .= CIA_preprocessor::fetchSugar($code, $i);
				}

				$class_pool[$curly_level] = (object) array(
					'classname' => $c,
					'has_php5_construct' => false,
					'construct_source' => '',
				);

				if ($c && isset($code[$i]) && is_array($code[$i]) && T_EXTENDS == $code[$i][0])
				{
					$token .= $code[$i][1];
					$token .= CIA_preprocessor::fetchSugar($code, $i);
					if (isset($code[$i]) && is_array($code[$i]))
					{
						$token .= 0<=$level && 'self' == $code[$i][1] ? $c . '__' . ($level ? $level-1 : '00') : $code[$i][1];
					}
					else --$i;
				}
				else --$i;

				break;

			case T_STRING:
				$lcToken = strtolower($token);

				if (!( isset($class_pool[$curly_level-1])
					&& ($c = $class_pool[$curly_level-1])
					&& !$c->has_php5_construct )) $c = false;

				switch ($lcToken)
				{
				case 'header':
					if (class_exists('CIA', false)) break;
					else break 2;

				case '__construct':
					if (!$c) break 2;
					else break;

				case '__cia_level__':
				case '__cia_file__':
				case 'resolvePath':
				case 'processPath':
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

				$j = 0;
				do $t = isset($code[$i - (++$j)]) && is_array($code[$i-$j]) ? $code[$i-$j][0] : false;
				while (T_COMMENT == $t || T_WHITESPACE == $t || T_DOC_COMMENT == $t);

				if (isset($code[$i-$j]))
				{
					if (is_array($code[$i-$j]))
					{
						if (T_DOUBLE_COLON == $code[$i-$j][0] || T_OBJECT_OPERATOR == $code[$i-$j][0]) break;
						else if ('&' == $code[$i-$j])
						{
							do $t = isset($code[$i - (++$j)]) && is_array($code[$i-$j]) ? $code[$i-$j][0] : false;
							while (T_COMMENT == $t || T_WHITESPACE == $t || T_DOC_COMMENT == $t);
						}
					}

					if (is_array($code[$i-$j]) && T_FUNCTION == $code[$i-$j][0])
					{
						// If the currently parsed method is this class constructor
						// build a PHP5 constructor if needed.

						if ($c)
						{
							if ('__construct' == $lcToken) $c->has_php5_construct = true;
							else if ($lcToken == strtolower($c->classname))
							{
								$token .= CIA_preprocessor::fetchSugar($code, $i);

								if ('(' == $code[$i])
								{
									$c->construct_source = $c->classname;
									$c = new CIA_preprocessor_construct_($c->construct_source);
									CIA_preprocessor::$tokenFilter = array($c, 'filterToken');
									$bracket_pool[$bracket_level+1] = CIA_preprocessor::bracket(array(array($c, 'close')));
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
				case '__cia_level__': $token = $level; break;
				case '__cia_file__':
					$token = CIA_preprocessor::$escapedSource;
					break;

				case 'self':
					if ($class_pool)
					{
						// Replace every self::* by <__CLASS__>::*
						$token = CIA_preprocessor::fetchSugar($code, $i);
						$token = (T_DOUBLE_COLON == $code[$i][0] ? end($class_pool)->classname : 'self') . $token;
						--$i;
					}

					break;

				case 'resolvepath':
				case 'processpath':
				case 'class_exists':
					$token .= CIA_preprocessor::fetchSugar($code, $i);

					if ('(' == $code[$i])
					{
						$bracket_pool[$bracket_level+1] = 'class_exists' == $lcToken
							  // For files in the include_path, set the 2nd arg of class_exists() to true
							? CIA_preprocessor::bracket(array(array($c = new CIA_preprocessor_classExists_, 'close')), array(array($c, 'position')))

							  // Automatically append their third arg to resolve|processPath
							: CIA_preprocessor::bracket(array(array(new CIA_preprocessor_path_, 'close'), $level));
					}

					--$i;
					break;
				}

				break;

			case T_REQUIRE_ONCE:
			case T_INCLUDE_ONCE:
			case T_REQUIRE:
			case T_INCLUDE:
				if (0>$level)
				{
					// Every require|include inside files in the include_path
					// is preprocessed thanks to processPath().

					$token .= ' cia_adaptRequire(';
					$instructionSuffix[$bracket_level] = ')';
				}

				break;

			case T_COMMENT:
			case T_WHITESPACE:
//			case T_DOC_COMMENT: // Preserve T_DOC_COMMENT for PHP's native Reflection API
				$token = substr_count($token, "\n");;
				$token = $token ? str_repeat("\n", $token) : ' ';
				break;

			case false:
				switch ($token)
				{
				case ',':
					if (isset($instructionSuffix[$bracket_level]))
					{
						$token = $instructionSuffix[$bracket_level] . $token;
						unset($instructionSuffix[$bracket_level]);
					}

					if (isset($bracket_pool[$bracket_level])) $token = $bracket_pool[$bracket_level]->incrementPosition($token);

					break;

				case '(':
					++$bracket_level;

					break;

				case ')':
					if (isset($instructionSuffix[$bracket_level]))
					{
						$token = $instructionSuffix[$bracket_level] . $token;
						unset($instructionSuffix[$bracket_level]);
					}

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
	
				case ';':
					if (isset($instructionSuffix[$bracket_level]))
					{
						$token = $instructionSuffix[$bracket_level] . $token;
						unset($instructionSuffix[$bracket_level]);
					}
					break;
				}
			}

			if (CIA_preprocessor::$tokenFilter) $token = call_user_func(CIA_preprocessor::$tokenFilter, $tokenType, $token);

			$new_code[] = $token;
		}

		$token =& $code[$codeLen - 1];

		if (!is_array($token) || (T_CLOSE_TAG != $token[0] && T_INLINE_HTML != $token[0])) $new_code[] = '?>';

		$new_code = implode('', $new_code);

		return $new_code;
	}

	protected static function fetchSugar(&$code, &$i)
	{
		$token = '';
		$nonEmpty = false;

		while (
			isset($code[++$i]) && is_array($code[$i]) && ($t = $code[$i][0])
			&& (T_COMMENT == $t || T_WHITESPACE == $t || T_DOC_COMMENT == $t)
		)
		{
			// Preserve T_DOC_COMMENT for PHP's native Reflection API
			$token .= T_DOC_COMMENT == $t ? $code[$i][1] : CIA_preprocessor::extractLF($code[$i][1]);
			$nonEmpty || $nonEmpty = true;
		}

		return $nonEmpty && '' === $token ? ' ' : $token;
	}

	protected static function extractLF($a)
	{
		return str_repeat("\n", substr_count($a, "\n"));
	}

	protected static function extractRxLF($a)
	{
		return CIA_preprocessor::extractLF($a[0]);
	}

	protected static function bracket($onClose = false, $onPositionUpdate = false)
	{
		$b = new CIA_preprocessor_bracket_;
		if ($onClose) $b->onClose = $onClose;
		if ($onPositionUpdate) $b->onPositionUpdate = $onPositionUpdate;
		return $b;
	}
}

class CIA_preprocessor_bracket___0
{
	public $onPositionUpdate = false;
	public $onClose = false;

	protected $position = 0;

	function incrementPosition($token)
	{
		++$this->position;
		return $this->onPositionUpdate ? $this->callback($token, $this->onPositionUpdate) : $token;
	}

	function close($token)
	{
		$token = $this->onClose ? $this->callback($token, $this->onClose) : $token;

		$this->position = 0;
		$this->onPositionUpdate = false;
		$this->onClose = false;

		return $token;
	}

	protected function callback($token, $c)
	{
		$callback = $c[0];
		$c[0] = $this->position;
		array_unshift($c, $token);

		return call_user_func_array($callback, $c);
	}
}

class CIA_preprocessor_construct___0
{
	protected $source;
	protected $started = false;
	protected $num_args = 0;
	protected $proto = '';
	protected $args = '';


	function __construct(&$source)
	{
		$this->source =& $source;
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
		else if (!$type && '(' == $token) $this->started = true;

		return $token;
	}

	function close($token)
	{
		$this->source = 'function __construct(' . $this->proto . ')'
			. '{$a=array(' . $this->args . ');'
			. 'if(' . $this->num_args . '<func_num_args()){$b=func_get_args();array_splice($a,0,' . $this->num_args . ',$b);}'
			. 'call_user_func_array(array($this,"' . $this->source . '"),$a);}';

		CIA_preprocessor::$tokenFilter = false;

		return $token;
	}
}

class CIA_preprocessor_path___0
{
	function close($token, $position, $level)
	{
		return 1 == $position ? ',' . $level . $token : $token;
	}
}

class CIA_preprocessor_classExists___0
{
	function position($token, $position)
	{
		return 1 == $position ? $token . '(' : (2 == $position ? ')||1' . $token : $token);
	}

	function close($token, $position)
	{
		return 1 == $position ? ')||1' . $token : $token;
	}
}
