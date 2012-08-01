<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class ptlCompiler_php extends ptlCompiler
{
    protected

    $watch = 'public/templates/php',
    $serverMode = true,
    $closeModifier = '):0)',

    $binaryMode;


    function __construct($template, $binaryMode)
    {
        $this->binaryMode = $binaryMode;

        parent::__construct($template);
    }

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
        return "((isset(\$c\x9D['" . strtolower($name)
            . "'])||\$a\x9D=__FILE__.'*" . mt_rand() . "')?pipe_{$name}::php";
    }

    protected function addAGENT($limit, $inc, &$args, $is_exo)
    {
        if ($limit) return false;

        if (preg_match('/^\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'$/s', $inc))
        {
            eval("\$base=$inc;");

            list(, $base, $limit) = Patchwork\AgentTrace::resolve($base);

            if (false !== $base)
            {
                if (!$is_exo)
                {
                    user_error("Template Security Restriction Error: an EXOAGENT ({$base}{$limit}) is called with AGENT on line " . $this->getLine());
                    exit;
                }
            }
            else if ($is_exo)
            {
                user_error("Template Security Restriction Error: an AGENT ({$limit}) is called with EXOAGENT on line " . $this->getLine());
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

/**/    if (DEBUG)
/**/    {
            if (!strncmp($inc, '(isset(', 7))
            {
                $k = substr($inc, 7, strpos($inc, ')', 7) - 7);
                $this->pushCode("isset($k)?Patchwork_Serverside::loadAgent($inc,array($a)," . ($is_exo ? 1 : 0) . "):user_error('$k is undefined in AGENT name');");

                return true;
            }
/**/    }

        $this->pushCode("Patchwork_Serverside::loadAgent($inc,array($a)," . ($is_exo ? 1 : 0) . ");");

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
                    . '($p instanceof loop||(0<($p=(int)$p)&&Patchwork_Serverside::makeLoopByLength($p)))'
                    . '&&((string)$v->{\'p$\'}=$p)'
                    . '&&($v->{\'iteratorPosition$\'}=-1)'
                    . '&&($p=(object)array(\'$\'=>&$v))'
                    . '&&$v=&$p'
                . ')while('
                    . '($p=&$v->{\'$\'}&&$v=Patchwork_Serverside::getLoopNext($p->{\'p$\'}))'
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
                $var = var_export((string) $name, true);
                break;

            case '$':
                $var = '@$v' . str_repeat('->{"$"}', substr_count($prefix, '$')) . "->$name" ;
                break;

            case 'd':
            case 'a':
            case 'g':
                $var = '' !== (string) $prefix ? "Patchwork_Serverside::increment('$name',$prefix,\$$type)" : "@\${$type}->$name";
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
                if ($forceType) $var = "(string)$var";
            }
            else if ('@' === $var[0])
            {
                $var = substr($var, 1);
                $var = "(isset($var)?" . ($forceType ? "(string)$var" : $var) . ":'')";
            }
        }

        $var .= '"\'"\'o';

        return $var;
    }

    protected function filter($a)
    {
        $a = parent::filter($a);
        $this->binaryMode || $a = preg_replace_callback("/\s{2,}/su", array(__CLASS__, 'filter_callback'), $a);
        return $a;
    }

    protected static function filter_callback($m)
    {
        return false === strpos($m[0], "\n") ? ' ' : "\n";
    }
}
