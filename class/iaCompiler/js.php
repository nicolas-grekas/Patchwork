<?php

define('pC_PIPE',	'0');
define('pC_AGENT',	'1');
define('pC_ECHO',	'2');
define('pC_EVALECHO',	'3');
define('pC_SET',	'4');
define('pC_ENDSET',	'5');
define('pC_JUMP',	'6');
define('pC_IF',		'7');
define('pC_LOOP',	'8');
define('pC_NEXT',	'9');

class extends iaCompiler
{
	protected $watch = 'public/templates/js';

	protected $serverMode = false;
	protected $setStack = array();
	protected $stack = array();

	protected $code = array();
	protected $modifiers = array();
	protected $jsreserved = array(
		'abstract','boolean','break','byte',
		'case','catch','char','class',
		'const','continue','default','delete',
		'do','double','else','export',
		'extends','false','final','finally',
		'float','for','function','goto',
		'if','implements','in','instanceof',
		'int','long','native','new',
		'null','package','private','protected',
		'public','return','short','static',
		'super','switch','synchronized','this',
		'throw','throws','transient','true',
		'try','typeof','var','void',
		'while','with',
	);

	protected function makeCode(&$code)
	{
		if ($m = array_unique($this->modifiers))
		{
			sort($m);
			$m = implode('.', $m);
			array_unshift($this->code, pC_PIPE, $this->quote($m));
		}

		return implode(',', $this->code);
	}

	protected function makeModifier($name)
	{
		$this->modifiers[] = $name;
		return 'P$' . $name;
	}

	protected function addAGENT($end, $inc, &$args, $is_exo)
	{
		if ($end) return false;

		$this->pushCode('');

		$keys = false;
		$meta = $is_exo ? 3 : 2;

		if (preg_match('/^\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'$/su', $inc))
		{
			eval("\$inc=$inc;");

			list($CIApID, $home, $inc, $keys, $a) = CIA::resolveAgentTrace($inc);

			foreach ($a as $k => &$v) $args[$k] = $this->quote($v);

			if (false !== $home)
			{
				if (!$is_exo)
				{
					E("Template Security Restriction Error: an EXOAGENT ({$home}{$inc}) is called with AGENT on line " . $this->getLine());
					exit;
				}

				$meta = array($CIApID, $this->quote($home));
				$meta = '[' . implode(',', $meta) . ']';
			}
			else if ($is_exo)
			{
				E("Template Security Restriction Error: an AGENT ({$inc}) is called with EXOAGENT on line " . $this->getLine());
				exit;
			}
			else $meta = 1;

			array_walk($keys, array($this, 'quote'));
			$keys = implode(',', $keys);

			$this->quote($inc);
		}

		$a = '';
		$comma = '';
		foreach ($args as $k => &$v)
		{
			$a .= in_array($k, $this->jsreserved) ? "$comma'$k':$v" : "$comma$k:$v";
			$comma = ',';
		}
		$a = '{' . $a . '}';

		array_push($this->code, pC_AGENT, $this->quote($inc), $this->quote($a), $keys===false ? 0 : "[$keys]", $meta);

		return true;
	}

	protected function addSET($end, $name, $type)
	{
		$this->pushCode('');

		if ($end)
		{
			$type = array_pop($this->setStack);
			$name = $type[0];
			$type = $type[1];

			if ($type == 'g') $type = 1;
			else if ($type == 'a') $type = 0;
			else $type = strlen($type) + 2;

			array_push($this->code, pC_ENDSET, $type, "'" . $name . "'");
		}
		else
		{
			array_push($this->setStack, array($name, $type));
			array_push($this->code, pC_SET);
		}

		return true;
	}

	protected function addLOOP($end, $var)
	{
		$this->pushCode('');

		if ($end)
		{
			$a = array_pop($this->stack);
			$b = count($this->code) - $a;
			if ($this->code[$a]==-1)
			{
				array_push($this->code, pC_NEXT, $b);
				$this->code[$a] = $b + 2;
			}
			else $this->code[$a] = $b;
		}
		else
		{
			array_push($this->stack, count($this->code) + 2);
			array_push($this->code, pC_LOOP, $this->quote($var), -1);
		}

		return true;
	}

	protected function addIF($end, $elseif, $expression)
	{
		if ($elseif && $end) return false;

		$this->pushCode('');

		if ($end)
		{
			$a = array_pop($this->stack);
			$b = count($this->code) - $a;
			if ($this->code[$a]==-3) do
			{
				$this->code[$a] = $b;
				$b += $a;
				$a = array_pop($this->stack);
				$b -= $a;
			}
			while ($this->code[$a]==-3);

			$this->code[$a] = $b;
		}
		else
		{
			if ($elseif) $this->addELSE(false);

			array_push($this->stack, count($this->code) + 2);
			array_push($this->code, pC_IF, $this->quote($expression), $elseif ? -3 : -2);
		}

		return true;
	}

	protected function addELSE($end)
	{
		if ($end) return false;

		$this->pushCode('');

		$a = array_pop($this->stack);
		$b = count($this->code) - $a;
		if ($this->code[$a]==-1)
		{
			array_push($this->stack, $a + $b + 3);
			array_push($this->code, pC_NEXT, $b, pC_JUMP, -2);
			$this->code[$a] = $b + 4;
		}
		else
		{
			array_push($this->stack, $a + $b + 1);
			array_push($this->code, pC_JUMP, $this->code[$a]);
			$this->code[$a] = $b + 2;
		}

		return true;
	}

	protected function getEcho($str)
	{
		if ($str{0}=="'" || (string) $str === (string) ($str-0))
		{
			if ($str!="''") array_push($this->code, pC_ECHO, $str);
		}
		else
		{
			$this->pushCode('');
			array_push($this->code, pC_EVALECHO, $this->quote($str));
		}

		return '';
	}

	protected function getConcat($array)
	{
		return implode('+', $array);
	}

	protected function getVar($name, $type, $prefix, $forceType)
	{
		if ((string) $name === (string) ($name-0)) return $name;

		switch ($type)
		{
			case "'":
				$result = "'" . jsquote($name, false) . "'";
				break;

			case '$':
				$result = 'v' . str_repeat('.$', substr_count($prefix, '$')) . $this->getJsAccess($name);
				break;

			case 'd':
			case 'a':
			case 'g':
				$result = ''!==(string) $prefix ? "z('$name',$prefix" .( $type=='g' ? ',1' : '' ). ')' : ($type . $this->getJsAccess($name));
				if ('g.__HOME__' == $result) $result = 'r';
				break;

			case '':
				$result = 'v' . $this->getJsAccess($name);
				break;

			default:
				$result = $type . $this->getJsAccess($name);
		}

		if ($forceType == 'number') $result = "num($result)";
		else if ($this->mode == 'concat' && $result{0} != "'") $result = "str($result)";

		return $result;
	}

	protected function getJsAccess($name)
	{
		return strlen($name) ? ( in_array($name, $this->jsreserved) ? "['$name']" : ".$name" ) : '';
	}

	protected function quote(&$a)
	{
		return $a = jsquote($a, true, '"');
	}
}
