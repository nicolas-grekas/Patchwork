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


class extends ptlCompiler
{
	protected

	$watch = 'public/templates/php',

	$closeModifier = '):0)',
	$setStack = array();


	protected function makeCode(&$code)
	{
		$a = "\n";

		$code = str_replace(
			array("\"'\"'o;$a\"'\"' ", "';$a\"'\"' ", ";;$a\"'\"' ", '"\'"\' '     , '"\'"\'\'', '"\'"\'o', '"\'"\''),
			array(',"\'"\''          , '\',"\'"\''  , ',"\'"\''    , 'echo "\'"\'' , '\''      , ''       , ''),
			implode($a, $code)
		);

		return $code;
	}

	protected function makeModifier($name)
	{
		return '((isset($c' . PATCHWORK_PATH_TOKEN . "['" . strtolower($name)
			. "'])||\$a" . PATCHWORK_PATH_TOKEN . "=__FILE__.'*" . mt_rand() . "')?pipe_{$name}::php";
	}

	protected function addAGENT($limit, $inc, &$args, $is_exo)
	{
		if ($limit) return false;

		if (preg_match('/^\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'$/s', $inc))
		{
			eval("\$base=$inc;");

			list(, $base, $limit) = patchwork_agentTrace::resolve($base);

			if (false !== $base)
			{
				if (!$is_exo)
				{
					W("Template Security Restriction Error: an EXOAGENT ({$base}{$limit}) is called with AGENT on line " . $this->getLine());
					exit;
				}
			}
			else if ($is_exo)
			{
				W("Template Security Restriction Error: an AGENT ({$limit}) is called with EXOAGENT on line " . $this->getLine());
				exit;
			}
		}

		$a = '';
		$comma = '';
		foreach ($args as $k => &$v)
		{
			$a .= "$comma'$k'=>" . $v;
			$comma = ',';
		}

/*<
		if (!strncmp($inc, '(isset(', 7))
		{
			$inc = substr($inc, 7, strpos($inc, ')', 7) - 7);
			$this->pushCode("isset($inc)?patchwork_serverside::loadAgent($inc,array($a)," . ($is_exo ? 1 : 0) . "):trigger_error('AGENT is undefined: $inc');");

			return true;
		}
>*/

		$this->pushCode("patchwork_serverside::loadAgent($inc,array($a)," . ($is_exo ? 1 : 0) . ");");

		return true;
	}

	protected function addSET($limit, $name, $type)
	{
		if ($limit > 0)
		{
			$type = array_pop($this->setStack);
			$name = $type[0];
			$type = $type[1];

			if ('d' !== $type && 'a' !== $type && 'g' !== $type)
			{
				$i = strlen($type);
				$type = 'v';
				if ($i) do $type .= '->{\'$\'}'; while (--$i);
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

	protected function addLOOP($limit, $var)
	{
		if ($limit > 0) $this->pushCode('}');
		else
		{
			$this->pushCode(
				'unset($p);$p=' . $var . ';if('
					. '($p instanceof loop||(0<($p=(int)$p)&&patchwork_serverside::makeLoopByLength($p)))'
					. '&&patchwork::string($v->{\'p$\'}=$p)'
					. '&&($v->{\'iteratorPosition$\'}=-1)'
					. '&&($p=(object)array(\'$\'=>&$v))'
					. '&&$v=&$p'
				. ')while('
					. '($p=&$v->{\'$\'}&&$v=$p->{\'p$\'}->loop(' . ($this->binaryMode ? '' : 'true') . '))'
					. '||($v=&$p&&0)'
				. '){'
				. '$v->{\'$\'}=&$p;'
				. '$v->iteratorPosition=++$p->{\'iteratorPosition$\'};'
			);
		}

		return true;
	}

	protected function addIF($limit, $elseif, $expression)
	{
		if ($elseif && $limit) return false;

		$this->pushCode($limit > 0 ? '}' : (($elseif ? '}else ' : '') . "if(($expression)){"));

		return true;
	}

	protected function addELSE($limit)
	{
		if ($limit) return false;

		$this->pushCode('}else{');

		return true;
	}

	protected function getEcho($str)
	{
		$str = "''" === substr($str, 0, 2) ? '' : "\"'\"' $str;";
		if (')' === substr($str, -2, 1)) $str .= ';';
		return $str;
	}

	protected function getConcat($array)
	{
		return str_replace('"\'"\'o', '', implode('.', $array));
	}

	protected function getRawString($str)
	{
		$str = str_replace('"\'"\'o', '', $str);
		eval("\$str=$str;");
		return $str;
	}

	protected function getVar($name, $type, $prefix, $forceType)
	{
		if ((string) $name === (string) ($name-0)) return $name . '"\'"\'o';

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
				$var = '' !== (string) $prefix ? "patchwork_serverside::increment('$name',$prefix,\$$type)" : "@\${$type}->$name";
				break;

			case '':
				$var = "@\$v->$name";
				break;

			default:
				$var = "@\${$type}->$name";
		}

		if ("'" !== $type)
		{
			if (!strlen($name))
			{
				$var = substr($var, 0, -2);
				if ($forceType) $var = "patchwork::string($var)";
			}
			else if ('@' === $var[0])
			{
				$var = substr($var, 1);
				$var = "(isset($var)?" . ($forceType ? "patchwork::string($var)" : $var) . ":'')";
			}
		}

		$var .= '"\'"\'o';

		return $var;
	}
}
