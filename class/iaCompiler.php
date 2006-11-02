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


abstract class
{
	protected $watch;

	private $Xlvar = '\\{';
	private $Xrvar = '\\}';

	private $Xlblock = '<!--\s*';
	private $Xrblock = '\s*-->';
	private $Xcomment = '\\{\*.*?\*\\}';

	private $Xvar = '(?:(?:[dag][-+]\d+|\\$*|[dag])?\\$)';
	private $XpureVar = '[a-zA-Z_][a-zA-Z_\d]*';

	private $Xblock = '[A-Z]+\b';
	private $XblockEnd = 'END:';

	private $Xstring = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'';
	private $Xnumber;
	private $XvarNconst;
	private $Xmath;
	private $Xexpression;
	private $XfullVar;
	private $Xmodifier;
	private $XmodifierPipe;

	private $code;
	private $codeLast;
	private $concat;
	private $concatLast;
	private $source;

	private $path_idx;
	private $template;

	private $offset = 0;
	private $blockStack = array();

	protected $mode = 'echo';
	protected $binaryMode = true;
	protected $serverMode = true;

	function __construct($binaryMode)
	{
		CIA::watch($this->watch);

		$this->binaryMode = $binaryMode;
		$this->Xvar .= $this->XpureVar;

		$dnum = '(?:(?:\d*\.\d+)|(?:\d+\.\d*))';
		$this->Xnumber = "-?(?:(?:\d+|$dnum)[eE][+-]?\d+|$dnum|[1-9]\d*|0[xX][\da-fA-F]+|0[0-7]*)(?!\d)";
		$this->XvarNconst = "(?<!\d)(?:{$this->Xstring}|{$this->Xnumber}|{$this->Xvar}|[dag]\\$|\\$+)";

		$this->Xmath = "\(*(?:{$this->Xnumber}|{$this->Xvar})\)*";
		$this->Xmath = "(?:{$this->Xmath}\s*[-+*\/%]\s*)*{$this->Xmath}";
		$this->Xexpression = "(?<!\d)(?:{$this->Xstring}|(?:{$this->Xmath})|[dag]\\$|\\$+|[\/~])";

		$this->Xmodifier = $this->XpureVar;
		$this->XmodifierPipe = "\\|{$this->Xmodifier}(?::(?:{$this->Xexpression})?)*";

		$this->XfullVar = "({$this->Xexpression}|{$this->Xmodifier}(?::(?:{$this->Xexpression})?)+)((?:{$this->XmodifierPipe})*)";
	}

	final public function compile($template)
	{
		$this->source = $this->load($template);

		$this->code = array('');
		$this->codeLast = 0;

		$this->makeBlocks($this->source);

		$this->offset = mb_strlen($this->source);
		if ($this->blockStack) $this->endError('$end', array_pop($this->blockStack));

		if (!($this->codeLast%2)) $this->code[$this->codeLast] = $this->getEcho( $this->makeVar("'" . $this->code[$this->codeLast]) );

		return $this->makeCode($this->code);
	}

	final protected function getLine()
	{
		$a = substr($this->source, 0, $this->offset);

		return substr_count($a, "\n") + substr_count($a, "\r") + 1;
	}

	private function load($template, $path_idx = 0)
	{
		if ($path_idx >= count($GLOBALS['cia_paths'])) return '';

		$path = $GLOBALS['cia_paths'][$path_idx] . '/public/';
		$lang = CIA::__LANG__() . '/';
		$l_ng = '__/';

		if (
			   !file_exists($source = $path . $lang . $template)
			&& !file_exists($source = $path . $l_ng . $template)
		) return $this->load($template, $path_idx + 1);

		$source = file_get_contents($source);

		$source = rtrim($source);
		if (false !== strpos($source, "\r")) $source = str_replace(array("\r\n", "\r"), array("\n" , "\n"), $source);
		$source = preg_replace_callback("'" . $this->Xcomment . "\n?'su", array($this, 'preserveLF'), $source);
		$source = preg_replace("'({$this->Xrblock})\n'su", "\n$1", $source);
		$source = preg_replace_callback(
			"/({$this->Xlblock}(?:{$this->XblockEnd})?{$this->Xblock})((?>{$this->Xstring}|.)*?)({$this->Xrblock})/su",
			array($this, 'autoSplitBlocks'),
			$source
		);

		if ($this->serverMode)
		{
			$source = preg_replace_callback(
				"'{$this->Xlblock}CLIENTSIDE{$this->Xrblock}.*?{$this->Xlblock}{$this->XblockEnd}CLIENTSIDE{$this->Xrblock}'su",
				array($this, 'preserveLF'),
				$source
			);

			$source = preg_replace_callback(
				"'{$this->Xlblock}({$this->XblockEnd})?SERVERSIDE{$this->Xrblock}'su",
				array($this, 'preserveLF'),
				$source
			);
		}
		else
		{
			$source = preg_replace_callback(
				"'{$this->Xlblock}SERVERSIDE{$this->Xrblock}.*?{$this->Xlblock}{$this->XblockEnd}SERVERSIDE{$this->Xrblock}'su",
				array($this, 'preserveLF'),
				$source
			);

			$source = preg_replace_callback(
				"'{$this->Xlblock}({$this->XblockEnd})?CLIENTSIDE{$this->Xrblock}'su",
				array($this, 'preserveLF'),
				$source
			);
		}

		$rx = '[-_a-zA-Z\d][-_a-zA-Z\d\.]*';

		$this->template = CIA_WINDOWS ? strtolower($template) : $template;
		$this->path_idx = $path_idx;
		$source = preg_replace_callback("'{$this->Xlblock}INCLUDE\s+($rx(?:[\\/]$rx)*)(:-?\d+)?\s*{$this->Xrblock}'su", array($this, 'INCLUDEcallback'), $source);

		return $source;
	}

	protected function preserveLF($m)
	{
		return str_repeat("\r", substr_count($m[0], "\n"));
	}

	protected function autoSplitBlocks($m)
	{
		$a =& $m[2];
		$a = preg_split("/({$this->Xstring})/su", $a, -1, PREG_SPLIT_DELIM_CAPTURE);

		$i = 0;
		$len = count($a);
		while ($i < $len)
		{
			$a[$i] = preg_replace("'\n\s*(?:{$this->XblockEnd})?{$this->Xblock}(?!\s*=)'su", ' --><!-- $0', $a[$i]);
			$i += 2;
		}

		return $m[1] . implode($a) . $m[3];
	}

	protected function INCLUDEcallback($m)
	{
		$path_count = count($GLOBALS['cia_paths']);

		$template = (CIA_WINDOWS ? strtolower($m[1]) : $m[1]) . '.tpl';

		$a = str_replace('\\', '/', $template) == preg_replace("'[\\/]+'", '/', $this->template);
		$a = isset($m[2]) ? substr($m[2], 1) : ($a ? -1 : $path_count - $this->path_idx - 1);
		$a = $a < 0 ? $this->path_idx - $a : ($path_count - $a - 1);

		if ($a < 0)
		{
			E("Template error: Invalid level (resolved to $a) in \"{$m[0]}\"");
			return $m[0];
		}
		else
		{
			if ($a >= $path_count) $a = $path_count - 1;
			return $this->load($template, $a);
		}
	}

	abstract protected function makeCode(&$code);
	abstract protected function addAGENT($end, $inc, &$args, $is_exo);
	abstract protected function addSET($end, $name, $type);
	abstract protected function addLOOP($end, $var);
	abstract protected function addIF($end, $elseif, $expression);
	abstract protected function addELSE($end);
	abstract protected function getEcho($str);
	abstract protected function getConcat($array);
	abstract protected function getVar($name, $type, $prefix, $forceType);
	abstract protected function makeModifier($name);

	final protected function makeVar($name, $forceType = false)
	{
		$type = $prefix = '';
		if ("'" == $name[0])
		{
			$type = "'";
			$name = $this->filter(substr($name, 1));
		}
		else if (($pos = strrpos($name, '$'))!==false)
		{
			$type = $name[0];
			$prefix = substr($name, 1, $type=='$' ? $pos : $pos-1);
			$name = substr($name, $pos+1);
		}
		else $type = '';

		return $this->getVar($name, $type, $prefix, $forceType);
	}

	final protected function pushText($a)
	{
		if ('concat' == $this->mode)
		{
			if ($this->concatLast % 2) $this->concat[++$this->concatLast] = $a;
			else $this->concat[$this->concatLast] .= $a;
		}
		else
		{
			if ($this->codeLast % 2) $this->code[++$this->codeLast] = $a;
			else $this->code[$this->codeLast] .= $a;
		}
	}

	final protected function pushCode($a)
	{
		if ($this->codeLast % 2) $this->code[$this->codeLast] .= $a;
		else
		{
			$this->code[$this->codeLast] = $this->getEcho( $this->makeVar("'" . $this->code[$this->codeLast]) );
			$this->code[++$this->codeLast] = $a;
		}
	}


	private function filter($a)
	{
		if (false !== strpos($a, "\r")) $a = str_replace("\r", '', $a);

		return $this->binaryMode ? $a : preg_replace("/\s{2,}/seu", 'strpos(\'$0\', "\n")===false ? " " : "\n"', $a);
	}

	private function makeBlocks($a)
	{
		$a = preg_split("/({$this->Xlblock}{$this->Xblock}(?>{$this->Xstring}|.)*?{$this->Xrblock})/su", $a, -1, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_DELIM_CAPTURE);

		$this->makeVars($a[0][0]);

		$i = 1;
		$len = count($a);
		while ($i < $len)
		{
			$this->offset = $a[$i][1];
			$this->compileBlock($a[$i++][0]);
			$this->makeVars($a[$i++][0]);
		}
	}

	private function makeVars(&$a)
	{
		$a = preg_split("/{$this->Xlvar}{$this->XfullVar}{$this->Xrvar}/su", $a, -1, PREG_SPLIT_DELIM_CAPTURE);

		$this->pushText($a[0]);

		$i = 1;
		$len = count($a);
		while ($i < $len)
		{
			$this->compileVar($a[$i++], $a[$i++]);
			$this->pushText($a[$i++]);
		}
	}

	private function compileBlock(&$a)
	{
		$blockname = $blockend = false;

		if (preg_match("/^{$this->Xlblock}{$this->XblockEnd}({$this->Xblock}).*?{$this->Xrblock}$/su", $a, $block))
		{
			$blockname = $block[1];
			$block = false;
			$blockend = true;
		}
		else if (preg_match("/^{$this->Xlblock}({$this->Xblock})(.*?){$this->Xrblock}$/su", $a, $block))
		{
			$blockname = $block[1];
			$block = trim($block[2]);
		}

		if ($blockname!==false)
		{
			switch ($blockname)
			{
			case 'EXOAGENT':
			case 'AGENT':
				$is_exo = 'EXOAGENT' == $blockname;

				if (preg_match("/^({$this->Xstring}|{$this->Xvar})(?:\s+{$this->XpureVar}\s*=\s*(?:{$this->XvarNconst}))*$/su", $block, $block))
				{
					$inc = $this->evalVar($block[1]);

					if ("''" != $inc)
					{
						$args = array();
						if (preg_match_all("/\s+({$this->XpureVar})\s*=\s*({$this->XvarNconst})/su", $block[0], $block))
						{
							$i = 0;
							$len = count($block[0]);
							while ($i < $len)
							{
								$args[ $block[1][$i] ] = $this->evalVar($block[2][$i]);
								$i++;
							}
						}

						if (!$this->addAGENT($blockend, $inc, $args, $is_exo)) $this->pushText($a);
					}
					else $this->pushText($a);
				}
				else $this->pushText($a);
				break;

			case 'SET':
				if (preg_match("/^([dag]|\\$*)\\$({$this->XpureVar})$/su", $block, $block))
				{
					$type = $block[1];
					$block = $block[2];

					if ($this->addSET($blockend, $block, $type)) $this->blockStack[] = $blockname;
					else $this->pushText($a);
				}
				else if ($blockend)
				{
					if ($this->addSET($blockend, '', ''))
					{
						$block = array_pop($this->blockStack);
						if ($block != $blockname) $this->endError($blockname, $block);
					}
					else $this->pushText($a);
				}
				else $this->pushText($a);

				break;

			case 'LOOP':

				$block = preg_match("/^{$this->Xexpression}$/su", $block, $block)
					? preg_replace("/{$this->XvarNconst}/sue", '$this->evalVar(\'$0\')', $block[0])
					: '';

				$block = preg_replace("/\s+/su", '', $block);

				if (!$this->addLOOP($blockend, $block)) $this->pushText($a);
				else if ($blockend)
				{
					$block = array_pop($this->blockStack);
					if ($block != $blockname) $this->endError($blockname, $block);
				}
				else $this->blockStack[] = $blockname;
				break;

			case 'IF':
			case 'ELSEIF':
				if ($blockend)
				{
					if (!$this->addIF(true, $blockname=='ELSEIF', $block)) $this->pushText($a);
					else
					{
						$block = array_pop($this->blockStack);
						if ($block != $blockname) $this->endError($blockname, $block);
					}
					break;
				}

				$block = preg_split(
					"/({$this->Xstring}|{$this->Xvar})/su",
					$block, -1, PREG_SPLIT_DELIM_CAPTURE
				);
				$testCode = preg_replace("'\s+'u", '', $block[0]);
				$var = array();

				$i = $j = 1;
				$len = count($block);
				while ($i < $len)
				{
					$var['$a' . $j . 'b'] = $block[$i++];
					$testCode .= '$a' . $j++ . 'b ' . preg_replace("'\s+'u", '', $block[$i++]);
				}

				$testCode = preg_replace('/\s+/su', ' ', $testCode);
				$testCode = strtr($testCode, '#[]{}^~?:,', ';;;;;;;;;;');
				$testCode = str_replace(
					array('&&' , '||' , '&', '|', '<>'),
					array('#a#', '#o#', ';', ';', ';' ),
					$testCode
				);
				$testCode = preg_replace(
					array('/<<+/u', '/>>+/u', '/[a-zA-Z_\d]\(/u'),
					array(';'     , ';'     , ';'),
					$testCode
				);
				$testCode = str_replace(
					array('#a#', '#o#'),
					array('&&' , '||'),
					$testCode
				);

				$i = @eval("($testCode);");
				if ($i!==false) while (--$j) if (isset(${'a'.$j.'b'})) $i = false;

				if ($i!==false)
				{
					$block = preg_split('/(\\$a\db) /su', $testCode, -1, PREG_SPLIT_DELIM_CAPTURE);

					$expression = $block[0];

					$i = 1;
					$len = count($block);
					while ($i < $len)
					{
						$expression .= $this->evalVar($var[ $block[$i++] ], false, 'string');
						$expression .= $block[$i++];
					}

					if (!$this->addIF(false, $blockname=='ELSEIF', $expression)) $this->pushText($a);
					else if ($blockname!='ELSEIF') $this->blockStack[] = $blockname;
				}
				else $this->pushText($a);
				break;

			default:
				if (!(method_exists($this, 'add'.$blockname) && $this->{'add'.$blockname}($blockend, $block))) $this->pushText($a);
			}
		}
		else $this->pushText($a);
	}

	private function compileVar($var, $pipe)
	{
		$detail = array();

		preg_match_all("/({$this->Xexpression}|{$this->Xmodifier}|(?<=:)(?:{$this->Xexpression})?)/su", $var, $match);
		$detail[] = $match[1];

		preg_match_all("/{$this->XmodifierPipe}/su", $pipe, $match);
		foreach ($match[0] as &$match)
		{
			preg_match_all("/(?:^\\|{$this->Xmodifier}|:(?:{$this->Xexpression})?)/su", $match, $match);
			foreach ($match[0] as &$j) $j = $j == ':' ? "''" : substr($j, 1);
			unset($j);
			$detail[] = $match[0];
		}

		$Estart = '';
		$Eend = '';

		$i = count($detail);
		while (--$i)
		{
			$Estart .= $this->makeModifier($detail[$i][0]) . '(';
			$Eend = ')' . $Eend;

			$j = count($detail[$i]);
			while (--$j) $Eend = ',' . $this->evalVar($detail[$i][$j], true) . $Eend;
		}

		if (isset($detail[0][1]))
		{
			$Eend = ')' . $Eend;

			$j = count($detail[0]);
			while (--$j) $Eend = ',' . $this->evalVar($detail[0][$j], true) . $Eend;

			$Eend[0] = '(';
			$Estart .= $this->makeModifier($detail[0][0]);
		}
		else $Estart .= $this->evalVar($detail[0][0], true);

		if ("'" == $Estart[0])
		{
			$Estart = $this->getConcat(array($Estart));
			eval("\$Estart=$Estart;");
			$this->pushText($Estart);
		}
		else if ($this->mode == 'concat')
		{
			$this->concat[++$this->concatLast] = $Estart . $Eend;
		}
		else $this->pushCode( $this->getEcho($Estart . $Eend) );
	}

	private function evalVar($a, $translate = false, $forceType = false)
	{
		if ($a === '') return "''";
		if ('~' == $a) $a = 'g$__HOME__';
		if ('/' == $a) $a = 'g$__HOST__';

		if ('"' == $a[0] || "'" == $a[0])
		{
			$b = '"' == $a[0];

			if (!$b) $a = '"' . substr(preg_replace('/([^\\\\](?:\\\\\\\\)*)"/su', '$1\\\\"', $a), 1, -1) . '"';
			$a = preg_replace("/([^\\\\])\\\\((?:\\\\\\\\)*)'/su", '$1$2\'', $a);
			$a = preg_replace('/([^\\\\](\\\\?)(?:\\\\\\\\)*)\\$/su', '$1$2\\\\$', $a);
			$a = eval("return $a;");

			if ($b && trim($a)!=='')
			{
				if ($translate) $a = TRANSLATE::get($a, CIA::__LANG__(), false);
				else
				{
					$this->mode = 'concat';
					$this->concat = array('');
					$this->concatLast = 0;

					$this->makeVars($a);

					if ($this->concatLast == 0) $this->concat[0] = TRANSLATE::get($this->concat[0], CIA::__LANG__(), false);

					for ($i = 0; $i<=$this->concatLast; $i+=2)
					{
						if ($this->concat[$i] !== '') $this->concat[$i] = $this->makeVar("'" . $this->concat[$i]);
						else unset($this->concat[$i]);

					}

					$this->mode = 'echo';
					return count($this->concat)>1 ? $this->getConcat($this->concat) : current($this->concat);
				}
			}

			$a = "'" . $a;
		}
		else if (preg_match("/^{$this->Xnumber}$/su", $a)) $a = eval("return \"'\" . $a;");
		else if (!preg_match("/^(?:{$this->Xvar}|[dag]\\$|\\$+)$/su", $a))
		{
			$a = preg_split("/({$this->Xvar}|{$this->Xnumber})/su", $a, -1, PREG_SPLIT_DELIM_CAPTURE);

			$i = 1;
			$len = count($a);
			while ($i < $len)
			{
				$a[$i-1] = trim($a[$i-1]);

				$b = $i > 1 && $a[$i][0] == '-' && '' === $a[$i-1];

				$a[$i] = $this->evalVar($a[$i], false, 'number');

				if ($b && '0' === $a[$i]) $a[$i-1] = '-';

				$i += 2;
			}

			$a = implode($a);
			return $a;
		}

		return $this->makeVar($a, $forceType);
	}

	private function endError($unexpected, $expected)
	{
		E("Template Parse Error: Unexpected END:$unexpected" . ($expected ? ", expecting END:$expected" : '') . " line " . $this->getLine());
	}
}
