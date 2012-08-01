<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class ptlCompiler_js extends ptlCompiler
{
    const

    OP_PIPE      = 0,
    OP_AGENT     = 1,
    OP_ECHO      = 2,
    OP_EVALECHO  = 3,
    OP_SET       = 4,
    OP_ENDSET    = 5,
    OP_JUMP      = 6,
    OP_IF        = 7,
    OP_LOOP      = 8,
    OP_NEXT      = 9;


    protected

    $watch = 'public/templates/js',
    $serverMode = false,
    $closeModifier = ')',

    $stack = array(),

    $jscode = array(),
    $modifiers = array(),
    $jsreserved = array(
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
        $code = '';

        if ($m = array_unique($this->modifiers))
        {
/**/        if (DEBUG)
/**/        {
                $m = implode('.', $m);
                array_unshift($this->jscode, self::OP_PIPE, jsquote($m));
/**/        }
/**/        else
/**/        {
                foreach ($m as $m)
                {
                    $code .= 'w.P$' . $m . '=';

                    if (class_exists('pipe_' . $m))
                    {
                        ob_start();
                        call_user_func(array('pipe_' . $m, 'js'));

                        $m = new JSqueeze;
                        $m = $m->squeeze(ob_get_clean());
                        $code .= trim($m, ';') . ';';
                    }
                    else
                    {
                        user_error('PTL Modifier Not Found: ' . $m);
                        $code .= "function(){return'؟'};";
                    }
                }
/**/        }
        }

        return "(function(w){{$code}return [" . implode(',', $this->jscode) . "]})(window)";
    }

    protected function makeModifier($name)
    {
        $this->modifiers[] = $name;
        return 'P$' . $name;
    }

    protected function addAGENT($limit, $inc, &$args, $is_exo)
    {
        if ($limit) return false;

        $this->pushCode('');

        $keys = false;
        $meta = $is_exo ? 3 : 2;

        if (preg_match('/^\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'$/s', $inc))
        {
            eval("\$inc=$inc;");

            list($appId, $base, $inc, $keys, $a) = Patchwork\AgentTrace::resolve($inc);

            $args = array_map('jsquote', $a) + $args;

            if (false !== $base)
            {
                if (!$is_exo)
                {
                    user_error("Template Security Restriction Error: an EXOAGENT ({$base}{$inc}) is called with AGENT on line " . $this->getLine());
                    exit;
                }

                $meta = "[{$appId}," . jsquote($base) . ']';
            }
            else if ($is_exo)
            {
                user_error("Template Security Restriction Error: an AGENT ({$inc}) is called with EXOAGENT on line " . $this->getLine());
                exit;
            }
            else $meta = 1;

            $keys = implode(',', array_map('jsquote', $keys));

            $inc = jsquote($inc);
        }

        $a = '';
        $comma = '';
        foreach ($args as $k => $v)
        {
            $a .= in_array($k, $this->jsreserved) ? "$comma'$k':$v" : "$comma$k:$v";
            $comma = ',';
        }
        $a = '{' . $a . '}';

        array_push(
            $this->jscode,
            self::OP_AGENT,
            3 === $meta || 2 === $meta ? "function(a,d,v,g,z,r){return {$inc}}" : $inc,
            "function(a,d,v,g,z,r){return {$a}}",
            false === $keys ? 0 : "[$keys]",
            $meta
        );

        return true;
    }

    protected function addSET($limit, $name, $type)
    {
        $this->pushCode('');

        if ($limit > 0)
        {
            $type = array_pop($this->setStack);
            $name = $type[0];
            $type = $type[1];

            switch ($type)
            {
            case 'd': $type = 2; break;
            case 'g': $type = 1; break;
            case 'a': $type = 0; break;
            default: $type = strlen($type) + 3;
            }

            array_push($this->jscode, self::OP_ENDSET, $type, "'" . $name . "'");
        }
        else
        {
            array_push($this->setStack, array($name, $type));
            array_push($this->jscode, self::OP_SET);
        }

        return true;
    }

    protected function addLOOP($limit, $var)
    {
        $this->pushCode('');

        if ($limit > 0)
        {
            $a = array_pop($this->stack);
            $b = count($this->jscode) - $a;
            if (-1 === $this->jscode[$a])
            {
                array_push($this->jscode, self::OP_NEXT, $b);
                $this->jscode[$a] = $b + 2;
            }
            else $this->jscode[$a] = $b;
        }
        else
        {
            array_push($this->stack, count($this->jscode) + 2);
            array_push($this->jscode, self::OP_LOOP, "function(a,d,v,g,z,r){return {$var}}", -1);
        }

        return true;
    }

    protected function addIF($limit, $elseif, $expression)
    {
        if ($elseif && $limit) return false;

        $this->pushCode('');

        if ($limit > 0)
        {
            $a = array_pop($this->stack);
            $b = count($this->jscode) - $a;
            if (-3 === $this->jscode[$a]) do
            {
                $this->jscode[$a] = $b;
                $b += $a;
                $a = array_pop($this->stack);
                $b -= $a;
            }
            while (-3 === $this->jscode[$a]);

            $this->jscode[$a] = $b;
        }
        else
        {
            if ($elseif) $this->addELSE(false);

            array_push($this->stack, count($this->jscode) + 2);
            array_push($this->jscode, self::OP_IF, "function(a,d,v,g,z,r){return {$expression}}", $elseif ? -3 : -2);
        }

        return true;
    }

    protected function addELSE($limit)
    {
        if ($limit) return false;

        $this->pushCode('');

        $a = array_pop($this->stack);
        $b = count($this->jscode) - $a;
        if (-1 === $this->jscode[$a])
        {
            array_push($this->stack, $a + $b + 3);
            array_push($this->jscode, self::OP_NEXT, $b, self::OP_JUMP, -2);
            $this->jscode[$a] = $b + 4;
        }
        else
        {
            array_push($this->stack, $a + $b + 1);
            array_push($this->jscode, self::OP_JUMP, $this->jscode[$a]);
            $this->jscode[$a] = $b + 2;
        }

        return true;
    }

    protected function getEcho($str)
    {
        if ("'" === $str[0] || (string) $str === (string) ($str-0))
        {
            if ("''" !== $str) array_push($this->jscode, self::OP_ECHO, $str);
        }
        else
        {
            $this->pushCode('');
            array_push($this->jscode, self::OP_EVALECHO, "function(a,d,v,g,z,r){return {$str}}");
        }

        return '';
    }

    protected function getConcat($array)
    {
        return implode('+', $array);
    }

    protected function getRawString($str)
    {
        $str = substr($str, 1, -1);

        false !== strpos($str, "\\'")  && $str = str_replace("\\'" , "'" , $str);
        false !== strpos($str, '<\\/') && $str = str_replace('<\\/', '</', $str);
        false !== strpos($str, '\n')   && $str = str_replace('\n'  , "\n", $str);
        false !== strpos($str, '\\\\') && $str = str_replace('\\\\', '\\', $str);

        return $str;
    }

    protected function getVar($name, $type, $prefix, $forceType)
    {
        if ((string) $name === (string) ($name-0)) return $name;

        switch ($type)
        {
            case "'":
                $result = jsquote($name);
                break;

            case '$':
                $result = 'v' . str_repeat('.$', substr_count($prefix, '$')) . $this->getJsAccess($name);
                break;

            case 'd':
            case 'a':
            case 'g':
                $result = '' !== (string) $prefix ? "z('$name',$prefix" . ('g' === $type ? ',1' : '') . ')' : ($type . $this->getJsAccess($name));
                if ('g.__BASE__' === $result) $result = 'r';
                break;

            case '':
                $result = 'v' . $this->getJsAccess($name);
                break;

            default:
                $result = $type . $this->getJsAccess($name);
        }

        switch ($forceType)
        {
        case 'number' : $result = "'" === $result[0] ? ($result-0) : "num($result)"  ; break;
        case 'unified': $result = "'" === $result[0] ?  $result    : "num($result,1)"; break;
        default: if ('concat' === $this->mode && "'" !== $result[0]) $result = "str($result)";
        }

        return $result;
    }

    protected function getJsAccess($name)
    {
        return strlen($name) ? ( preg_match('"[^a-zA-Z0-9_\$]"', $name) || in_array($name, $this->jsreserved) ? "['$name']" : ".$name" ) : '';
    }
}
