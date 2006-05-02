<?php

class iaCompiler_php extends iaCompiler
{
	protected $watch = 'public/templates/php';

	protected $serverMode = true;
	protected $setStack = array();

	protected function makeCode(&$code)
	{
		$a = "\n";

		$code = str_replace(
			array("\"'\"'o;$a\"'\"' ", "';$a\"'\"' ", ";;$a\"'\"' ", "\"'\"' "     , "\"'\"''", "\"'\"'o", "\"'\"'"),
			array(",\"'\"'"          , "',\"'\"'"   , ",\"'\"'"    , "echo \"'\"'" , "'"      , ''       , ''),
			implode($a, $code)
		);

		return ( $this->binaryMode ? '' : 'IA_php::escape($v);' ) . $code;
	}


	protected function makeModifier($name)
	{
		return 'pipe_' . $name . '::php';
	}

	protected function addAGENT($end, $inc, &$args, $is_exo)
	{
		if ($end) return false;

		if (preg_match('/^\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'$/su', $inc))
		{
			eval("\$home=$inc;");

			list(, $home, $end) = CIA::resolveAgentTrace($home);

			if (false !== $home)
			{
				if (!$is_exo)
				{
					E("Template Security Restriction Error: an EXOAGENT ({$home}{$end}) is called with AGENT on line " . $this->getLine());
					exit;
				}
			}
			else if ($is_exo)
			{
				E("Template Security Restriction Error: an AGENT ({$end}) is called with EXOAGENT on line " . $this->getLine());
				exit;
			}
		}

		$a = '';
		$comma = '';
		foreach ($args as $k => $v)
		{
			$a .= "$comma'$k'=>" . $v;
			$comma = ',';
		}

		$this->pushCode("IA_php::loadAgent($inc,array($a)," .( $is_exo ? 1 : 0 ). ");");

		return true;
	}

	protected function addSET($end, $name, $type)
	{
		if ($end)
		{
			$type = array_pop($this->setStack);
			$name = $type[0];
			$type = $type[1];
			if ($type != 'd' && $type != 'a' && $type != 'g')
			{
				$type = 'v';
				$i = strlen($type);
				while (--$i) $type .= '->{"$"}';
			}
			$this->pushCode("\${$type}->{$name}=ob_get_clean();");
		}
		else
		{
			array_push($this->setStack, array($name, $type));
			$this->pushCode('ob_start();');
		}

		return true;
	}

	protected function addLOOP($end, $var)
	{
		if ($end) $this->pushCode('}');
		else
		{
			$this->pushCode(
				'unset($p);$p=' . $var . ';if('
					. '($p instanceof loop||(0<($p=(int)$p)&&IA_php::makeLoopByLength($p)))'
					. '&&CIA::string($v->{"p$"}=$p)'
					. '&&($v->{"iteratorPosition$"}=-1)'
					. '&&($p=(object)array("$"=>&$v))'
					. '&&$v=&$p'
				. ')while('
					. '($p=&$v->{"$"}&&$v=$p->{"p$"}->compose())'
					. '||($v=&$p&&0)'
				. '){'
				.( $this->binaryMode ? '' : 'IA_php::escape($v);' )
				. '$v->{"$"}=&$p;'
				. '$v->iteratorPosition=++$p->{"iteratorPosition$"};'
			);
		}

		return true;
	}

	protected function addIF($end, $elseif, $expression)
	{
		if ($elseif && $end) return false;

		$this->pushCode($end ? '}' : (($elseif ? '}else ' : '') . "if(($expression)){"));

		return true;
	}

	protected function addELSE($end)
	{
		if ($end) return false;

		$this->pushCode('}else{');

		return true;
	}

	protected function getEcho($str)
	{
		$str = substr($str, 0, 2)=="''" ? '' : "\"'\"' $str;";
		if (substr($str, -2, 1)==')') $str .= ';';
		return $str;
	}

	protected function getConcat($array)
	{
		return str_replace("\"'\"'o", '', implode('.', $array));
	}

	protected function getVar($name, $type, $prefix, $forceType)
	{
		if ((string) $name === (string) ($name-0)) return $name . "\"'\"'o";

		switch ($type)
		{
			case "'":
				$var = var_export($name, true);
				break;

			case '$':
				$var = '@$v' . str_repeat('->{"$"}', substr_count($prefix, '$')) . "->$name" ;
				break;

			case 'd':
			case 'a':
			case 'g':
				$var = ''!==(string) $prefix ? "IA_php::increment('$name',$prefix,\$$type)" : "@\${$type}->$name";
				break;

			case '':
				$var = "@\$v->$name";
				break;

			default:
				$var = "@\${$type}->$name";
		}

		if ($type != "'")
		{
			if (!strlen($name)) $var = substr($var, 0, -2);
			if ($forceType) $var = "CIA::string($var)";
		}

		$var .= "\"'\"'o";

		return $var;
	}
}
