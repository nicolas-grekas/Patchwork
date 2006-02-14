<?php

abstract class iaCompiler
{
	private $Xlvar = '\\{';
	private $Xrvar = '\\}';

	private $Xlblock = '<!--\s+';
	private $Xrblock = '\s+-->';

	private $Xvar = '(?:(?:[ag][-+]\d+|\\$*|[ag])?\\$)';
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
	private $template;

	private $offset = 0;
	private $blockStack = array();

	protected $mode = 'echo';
	protected $binaryMode = true;
	protected $serverMode = true;

	public function __construct($binaryMode)
	{
		$this->binaryMode = $binaryMode;
		$this->Xvar .= $this->XpureVar;

		$dnum = '(?:(?:\d*\.\d+)|(?:\d+\.\d*))';
		$this->Xnumber = "-?(?:(?:\d+|$dnum)[eE][+-]?\d+|$dnum|[1-9]\d*|0[xX][\da-fA-F]+|0[0-7]*)(?!\d)";
		$this->XvarNconst = "(?<!\d)(?:{$this->Xstring}|{$this->Xnumber}|{$this->Xvar}|[ag]\\$|\\$+)";

		$this->Xmath = "\(*(?:{$this->Xnumber}|{$this->Xvar})\)*";
		$this->Xmath = "(?:{$this->Xmath}\s*[-+*\/%]\s*)*{$this->Xmath}";
		$this->Xexpression = "(?<!\d)(?:{$this->Xstring}|(?:{$this->Xmath})|[ag]\\$|\\$+)";

		$this->Xmodifier = $this->XpureVar;
		$this->XmodifierPipe = "\\|{$this->Xmodifier}(?::(?:{$this->Xexpression})?)*";

		$this->XfullVar = "({$this->Xexpression}|{$this->Xmodifier}(?::(?:{$this->Xexpression})?)+)((?:{$this->XmodifierPipe})*)";

	}
	
	final public function compile($template)
	{
		$this->template = $template;
		$this->source = $this->load($template);
		$this->source = $this->preprocessing($this->source);

		$this->code = array('');
		$this->codeLast = 0;

		$this->makeBlocks($this->source);

		$this->offset = mb_strlen($this->source);
		if ($this->blockStack) $this->endError('$end', array_pop($this->blockStack));

		if (!($this->codeLast%2)) $this->code[$this->codeLast] = $this->getEcho( $this->makeVar("'" . $this->code[$this->codeLast]) );

		return $this->makeCode($this->code);
	}

	protected function preprocessing($source)
	{
		if ($this->binaryMode)
		{
			$source = str_replace(
				array("-->\r", "-->\n"),
				array("-->"  , "-->"),
				$source
			);
		}
		else
		{
			$source = str_replace(
				array("\r\n", "\r", "-->\n"),
				array("\n"  , "\n", "-->"),
				$source
			);
		}

		if (substr($source, -1) == "\n") $source = substr($source, 0, -1);

		return preg_replace("'<!--\*.*?\*-->'su", '', $source);
}

	private function load($template)
	{
		$source = @file_get_contents('public/' . CIA_LANG . "/$template", true);
		if ($source === false) $source = file_get_contents("public/__/$template", true);

		if ($this->serverMode)
		{
			$source = preg_replace("'<!--\s+CLIENTSIDE\s+-->.*?<!--\s+END:CLIENTSIDE\s+-->'su", '', $source);
			$source = preg_replace("'<!--\s+(END:)?SERVERSIDE\s+-->'su", '', $source);
		}
		else
		{
			$source = preg_replace("'<!--\s+SERVERSIDE\s+-->.*?<!--\s+END:SERVERSIDE\s+-->'su", '', $source);
			$source = preg_replace("'<!--\s+(END:)?CLIENTSIDE\s+-->'su", '', $source);
		}

		if (preg_match("'<!--\s+PARENT\s+-->'su", $source))
		{
			$include_path = get_include_path();
			set_include_path(substr(strstr($include_path, PATH_SEPARATOR), 1));

			$source = preg_replace("'<!--\s+PARENT\s+-->'su", $this->load($template), $source);

			set_include_path($include_path);
		}

		return $source;
	}

	abstract protected function makeCode(&$code);
	abstract protected function addAGENT($end, $inc, &$args);
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
		if ($name{0}=="'" )
		{
			$type = "'";
			$name = $this->filter(substr($name, 1));
		}
		else if (($pos = strrpos($name, '$'))!==false)
		{
			$type = $name{0};
			$prefix = substr($name, 1, $type=='$' ? $pos : $pos-1);
			$name = substr($name, $pos+1);
		}
		else $type = '';

		return $this->getVar($name, $type, $prefix, $forceType);
	}

	final protected function pushText($a)
	{
		if ($this->mode == 'concat')
		{
			if ($this->concatLast%2) $this->concat[++$this->concatLast] = $a;
			else $this->concat[$this->concatLast] .= $a;
		}
		else
		{
			if ($this->codeLast%2) $this->code[++$this->codeLast] = $a;
			else $this->code[$this->codeLast] .= $a;
		}
	}

	final protected function pushCode($a)
	{
		if ($this->codeLast%2) $this->code[$this->codeLast] .= $a;
		else
		{
			$this->code[$this->codeLast] = $this->getEcho( $this->makeVar("'" . $this->code[$this->codeLast]) );
			$this->code[++$this->codeLast] = $a;
		}
	}


	private function filter($a)
	{
		return $this->binaryMode ? $a : preg_replace("/\s{2,}/seu", 'mb_strpos(\'$0\', "\n")===false ? " " : "\n"', $a);
	}

	private function makeBlocks($a)
	{
		$a = preg_split("/({$this->Xlblock}{$this->Xblock}(?>{$this->Xstring}|.)*?{$this->Xrblock})/su", $a, -1, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_DELIM_CAPTURE);

		$this->makeVars($a[0][0]);

		$i = 1;
		$len = count($a);
		while ($i<$len)
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
		while ($i<$len)
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
			case 'AGENT':
				if (preg_match("/^({$this->XvarNconst})(?:\s+{$this->XpureVar}\s*=\s*(?:{$this->XvarNconst}))*$/su", $block, $block))
				{
					$inc = $this->evalVar($block[1]);

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
					
					if (!$this->addAGENT($blockend, $inc, $args)) $this->pushText($a);
				}
				else $this->pushText($a);
				break;

			case 'SET':
				if (preg_match("/^([ag]|\\$*)\\$({$this->XpureVar})$/su", $block, $block))
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
					"/({$this->Xvar})/su",
					$block, -1, PREG_SPLIT_DELIM_CAPTURE
				);
				$testCode = preg_replace("'\s+'u", '', $block[0]);
				$var = array();

				$i = $j = 1;
				$len = count($block);
				while ($i<$len)
				{
					$var['$a' . $j . 'b'] = $block[$i++];
					$testCode .= '$a' . $j++ . 'b ' . preg_replace("'\s+'u", '', $block[$i++]);
				}

				$testCode = preg_replace('/\s+/su', ' ', $testCode);
				$testCode = str_replace(
					array('#', '&&' , '||' , '[', ']', '{', '}', '^', '~', '?', ':', '&', '|', ',', '<>'),
					array(';', '#a#', '#o#', ';', ';', ';', ';', ';', ';', ';', ';', ';', ';', ';', ';' ),
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
					while ($i<$len)
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
		foreach ($match[0] as $match)
		{
			preg_match_all("/(?:^\\|{$this->Xmodifier}|:(?:{$this->Xexpression})?)/su", $match, $match);
			foreach ($match[0] as $i => $j) $match[0][$i] = $j == ':' ? "''" : substr($j, 1);
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

			$Eend{0} = '(';
			$Estart .= $this->makeModifier($detail[0][0]);
		}
		else $Estart .= $this->evalVar($detail[0][0], true);

		if ($Estart{0}=="'")
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

		if ($a{0}=='"' || $a{0}=="'")
		{
			$b = $a{0}=='"';

			if (!$b) $a = '"' . substr(preg_replace('/([^\\\\](?:\\\\\\\\)*)"/su', '$1\\\\"', $a), 1, -1) . '"';
			$a = preg_replace("/([^\\\\])\\\\((?:\\\\\\\\)*)'/su", '$1$2\'', $a);
			$a = preg_replace('/([^\\\\](\\\\?)(?:\\\\\\\\)*)\\$/su', '$1$2\\\\$', $a);
			$a = eval("return $a;");

			if ($b && trim($a)!=='')
			{
				if ($translate) $a = T($a, false);
				else
				{
					$this->mode = 'concat';
					$this->concat = array('');
					$this->concatLast = 0;

					$this->makeVars($a);

					if ($this->concatLast == 0) $this->concat[0] = T($this->concat[0], false);
					
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
		else if ($a!=='' && !preg_match("/^(?:{$this->Xvar}|[ag]\\$|\\$+)$/su", $a))
		{
			$a = preg_split("/({$this->Xvar}|{$this->Xnumber})/su", $a, -1, PREG_SPLIT_DELIM_CAPTURE);
			$i = 1;

			while ($i<count($a))
			{
				$a[$i-1] = trim($a[$i-1]);

				$b = $i > 1 && $a[$i]{0} == '-' && '' === $a[$i-1];

				$a[$i] = $this->evalVar($a[$i], false, 'number');

				if ($b && '0' === $a[$i]) $a[$i-1] = '-';

				$i += 2;
			}

			$a = implode($a);
			return $this->serverMode ? "@($a)": $a;
		}

		return $this->makeVar($a, $forceType);
	}

	private function endError($unexpected, $expected)
	{
		E("Template Parse Error: Unexpected END:$unexpected" . ($expected ? ", expecting END:$expected" : '') . " line " . (mb_substr_count(substr($this->source, 0, $this->offset), "\n") + 1));
	}
}
