<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


abstract class ptlCompiler
{
    protected

    $Xlvar = '\\{',
    $Xrvar = '\\}',

    $blockSplit = ' --><!-- ',
    $Xlblock = '<!--\s*',
    $Xrblock = '\s*-->\n?',
    $Xcomment = '\\{\*.*?\*\\}\n?',

    $Xvar = '(?:(?:[dag][-+]\d+|\\$*|[dag])?\\$)',
    $XpureVar = '[a-zA-Z_\x80-\xFFFFFFFF][a-zA-Z_\d\x80-\xFFFFFFFF]*',

    $Xblock = '[A-Z]+\b',
    $XblockBegin = 'BEGIN:',
    $XblockEnd = 'END:',

    $Xstring = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'',
    $Xnumber,
    $XvarNconst,
    $Xmath,
    $Xexpression,
    $XfullVar,
    $Xmodifier,
    $XmodifierPipe,

    $code,
    $codeLast,
    $concat,
    $concatLast,
    $source,

    $offset = 0,
    $blockStack  = array(),
    $setStack    = array(),
    $loadedStack = array(),
    $mode = 'echo',

    $watch,
    $serverMode,
    $closeModifier;


    function __construct($template)
    {
        $this->source = $template;

        Patchwork::watch($this->watch);

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

    function compile()
    {
        $this->source = $this->load($this->source);

        $this->code = array('');
        $this->codeLast = 0;

        $this->makeBlocks($this->source);

        $this->offset = strlen($this->source);
        if ($this->blockStack) $this->endError('$end', array_pop($this->blockStack));

        if (!($this->codeLast%2)) $this->code[$this->codeLast] = $this->getEcho( $this->makeVar("'" . $this->code[$this->codeLast]) );

        $template = $this->makeCode($this->code);

        false !== strpos($template, $this->blockSplit) && $template = str_replace($this->blockSplit, '', $template);

        return $template;
    }

    protected function getLine()
    {
        $a = substr($this->source, 0, $this->offset);

        return substr_count($a, "\n") + substr_count($a, "\r") + 1;
    }

    protected function load($template, $path_idx = 0)
    {
        $a = '\\' === DIRECTORY_SEPARATOR ? strtolower($template) : $template;
        $a = preg_replace("'[\\/]+'", '/', $a);

        if (isset($this->loadedStack[$a]) && $this->loadedStack[$a] >= $path_idx)
        {
            $path_idx = $this->loadedStack[$a] + 1;
        }

        $source = Patchwork::resolvePublicPath($template . '.ptl', $path_idx);

        if (!$source && 0 !== strcasecmp('.ptl', substr($template, -4)))
        {
            $path_idx = 0;
            $source = Patchwork::resolvePublicPath($template, $path_idx);
        }

        if (!$source) return '{$DATA}';

        $template = $a;
        $source = file_get_contents($a = $source);
        strncmp($source, "\xEF\xBB\xBF", 3) || $source = substr($source, 3); // Remove UTF-8 BOM

        if (!preg_match('//u', $source)) user_error("Template file {$a}:\nfile encoding is not valid UTF-8. Please convert your source code to UTF-8.");

        $source = rtrim($source);
        if (false !== strpos($source, "\r")) $source = strtr(str_replace("\r\n", "\n", $source), "\r", "\n");

        if ('.ptl' !== strtolower(substr($a, -4)))
        {
            $a = stripslashes($this->Xlvar . "'$0'" . $this->Xrvar);
            $source = preg_replace("'(?:{$this->Xlvar}|{$this->Xlblock})'", $a, $source);

            return $source;
        }

        $source = preg_replace_callback("'" . $this->Xcomment . "'su", array($this, 'preserveLF'), $source);
        $source = preg_replace_callback(
            "/({$this->Xlblock}(?:{$this->XblockEnd})?{$this->Xblock})((?".">{$this->Xstring}|.)*?)({$this->Xrblock})/su",
            array($this, 'autoSplitBlocks'),
            $source
        );

        if ($this->serverMode)
        {
            false !== strpos($source, 'CLIENTSIDE') && $source = preg_replace_callback(
                "'{$this->Xlblock}(?:{$this->XblockBegin})?CLIENTSIDE{$this->Xrblock}.*?{$this->Xlblock}{$this->XblockEnd}CLIENTSIDE{$this->Xrblock}'su",
                array($this, 'preserveLF'),
                $source
            );

            false !== strpos($source, 'SERVERSIDE') && $source = preg_replace_callback(
                "'{$this->Xlblock}(?:{$this->XblockBegin}|{$this->XblockEnd})?SERVERSIDE{$this->Xrblock}'su",
                array($this, 'preserveLF'),
                $source
            );
        }
        else
        {
            false !== strpos($source, 'SERVERSIDE') && $source = preg_replace_callback(
                "'{$this->Xlblock}(?:{$this->XblockBegin})?SERVERSIDE{$this->Xrblock}.*?{$this->Xlblock}{$this->XblockEnd}SERVERSIDE{$this->Xrblock}'su",
                array($this, 'preserveLF'),
                $source
            );

            false !== strpos($source, 'CLIENTSIDE') && $source = preg_replace_callback(
                "'{$this->Xlblock}(?:{$this->XblockBegin}|{$this->XblockEnd})?CLIENTSIDE{$this->Xrblock}'su",
                array($this, 'preserveLF'),
                $source
            );
        }

        unset($this->loadedStack[$template]);
        $this->loadedStack[$template] = $path_idx;

        $a = '[-_a-zA-Z\d\x80-\xFFFFFFFF][-_a-zA-Z\d\x80-\xFFFFFFFF\.]*';
        false !== strpos($source, 'INLINE') && $source = preg_replace_callback(
            "'{$this->Xlblock}INLINE\s+($a(?:[\\/]$a)*)(:-?\d+)?\s*{$this->Xrblock}'su",
            array($this, 'INLINEcallback'),
            $source
        );

        unset($this->loadedStack[$template]);

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
            $a[$i] = preg_replace("'\n\s*(?:{$this->XblockBegin}|{$this->XblockEnd})?{$this->Xblock}(?!\s*=)'su", $this->blockSplit . '$0', $a[$i]);
            $i += 2;
        }

        return $m[1] . implode($a) . $m[3];
    }

    protected function INLINEcallback($m)
    {
/**/    if (DEBUG)
            Patchwork::watch('debugSync');

        $a = isset($m[2]) ? substr($m[2], 1) : PATCHWORK_PATH_LEVEL;
        $a = $a < 0 ? end($this->loadedStack) - $a : (PATCHWORK_PATH_LEVEL - $a);

        if ($a < 0)
        {
            user_error("Template error: Invalid level (resolved to $a) in \"{$m[0]}\"");
            return $m[0];
        }
        else
        {
            if ($a > PATCHWORK_PATH_LEVEL) $a = PATCHWORK_PATH_LEVEL;

            return $this->load($m[1], $a);
        }
    }

    abstract protected function makeCode(&$code);
    abstract protected function addAGENT($limit, $inc, &$args, $is_exo);
    abstract protected function addSET($limit, $name, $type);
    abstract protected function addLOOP($limit, $var);
    abstract protected function addIF($limit, $elseif, $expression);
    abstract protected function addELSE($limit);
    abstract protected function getEcho($str);
    abstract protected function getConcat($array);
    abstract protected function getRawString($str);
    abstract protected function getVar($name, $type, $prefix, $forceType);
    abstract protected function makeModifier($name);

    protected function makeVar($name, $forceType = false)
    {
        $type = $prefix = '';
        if ("'" === $name[0])
        {
            $type = "'";
            $name = $this->filter(substr($name, 1));
        }
        else if (false !== $pos = strrpos($name, '$'))
        {
            $type = $name[0];
            $prefix = substr($name, 1, '$' === $type ? $pos : $pos-1);
            $name = substr($name, $pos+1);
        }
        else $type = '';

        return $this->getVar($name, $type, $prefix, $forceType);
    }

    protected function pushText($a)
    {
        if ('concat' === $this->mode)
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

    protected function pushCode($a)
    {
        if ($this->codeLast % 2) $this->code[$this->codeLast] .= $a;
        else
        {
            $this->code[$this->codeLast] = $this->getEcho( $this->makeVar("'" . $this->code[$this->codeLast]) );
            $this->code[++$this->codeLast] = $a;
        }
    }


    protected function filter($a)
    {
        if (false !== strpos($a, "\r")) $a = str_replace("\r", '', $a);

        return $a;
    }

    protected function makeBlocks($a)
    {
        $a = preg_split("/({$this->Xlblock}{$this->Xblock}(?".">{$this->Xstring}|.)*?{$this->Xrblock})/su", $a, -1, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_DELIM_CAPTURE);

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

    protected function makeVars(&$a)
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

    protected function compileBlock(&$a)
    {
        $blockname = false;
        $limit = 0;

        if (preg_match("/^{$this->Xlblock}{$this->XblockEnd}({$this->Xblock}).*?{$this->Xrblock}$/su", $a, $block))
        {
            $blockname = $block[1];
            $block = false;
            $limit = 1;
        }
        else if (preg_match("/^{$this->Xlblock}((?:{$this->XblockBegin})?)({$this->Xblock})(.*?){$this->Xrblock}$/su", $a, $block))
        {
            $limit = $block[1] ? -1 : 0;
            $blockname = $block[2];
            $block = trim($block[3]);
        }

        if (false !== $blockname)
        {
            switch ($blockname)
            {
            case 'EXOAGENT':
            case 'AGENT':
                $is_exo = 'EXOAGENT' === $blockname;

                if ($limit > 0)
                {
                    if ($this->addAGENT(1, '', array(), $is_exo))
                    {
                        $block = array_pop($this->blockStack);
                        if ($block !== $blockname) $this->endError($blockname, $block);
                    }
                    else $this->pushText($a);
                }
                else if (preg_match("/^({$this->Xstring}|{$this->Xvar})(?:\s+{$this->XpureVar}\s*=\s*(?:{$this->XvarNconst}))*$/su", $block, $block))
                {
                    $inc = $this->evalVar($block[1]);

                    if ("''" !== $inc)
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

                        if ($this->addAGENT($limit, $inc, $args, $is_exo)) $limit < 0 && $this->blockStack[] = $blockname;
                        else $this->pushText($a);
                    }
                    else $this->pushText($a);
                }
                else $this->pushText($a);
                break;

            case 'SET':
                if ($limit > 0)
                {
                    if ($this->addSET(1, '', ''))
                    {
                        $block = array_pop($this->blockStack);
                        if ($block !== $blockname) $this->endError($blockname, $block);
                    }
                    else $this->pushText($a);
                }
                else if (preg_match("/^([dag]|\\$*)\\$({$this->XpureVar})$/su", $block, $block))
                {
                    $type = $block[1];
                    $block = $block[2];

                    if ($this->addSET(-1, $block, $type)) $this->blockStack[] = $blockname;
                    else $this->pushText($a);
                }
                else $this->pushText($a);

                break;

            case 'LOOP':

                $block = preg_match("/^{$this->Xexpression}$/su", $block, $block)
                    ? preg_replace_callback("/{$this->XvarNconst}/su", array($this, 'evalVar_callback'), $block[0])
                    : '';

                $block = preg_replace("/\s+/su", '', $block);

                if (!$this->addLOOP($limit > 0 ? 1 : -1, $block)) $this->pushText($a);
                else if ($limit > 0)
                {
                    $block = array_pop($this->blockStack);
                    if ($block !== $blockname) $this->endError($blockname, $block);
                }
                else $this->blockStack[] = $blockname;
                break;

            case 'IF':
            case 'ELSEIF':
                if ($limit > 0)
                {
                    if (!$this->addIF(1, 'ELSEIF' === $blockname, $block)) $this->pushText($a);
                    else
                    {
                        $block = array_pop($this->blockStack);
                        if ($block !== $blockname) $this->endError($blockname, $block);
                    }
                    break;
                }
                else if ($limit < 0 && 'ELSEIF' === $blockname)
                {
                    $this->pushText($a);
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
                    array('/<<+/', '/>>+/', '/[a-zA-Z_0-9\xF7-\xFF]\(/'),
                    array(';'    , ';'    , ';'),
                    $testCode
                );
                $testCode = str_replace(
                    array('#a#', '#o#'),
                    array('&&' , '||'),
                    $testCode
                );

                if (preg_match("'[^=!<>]=[^=]'", $testCode))
                {
                    $i = "unexpected '='";
                }
                else
                {
                    set_error_handler(array(__CLASS__, 'nullErrorHandler'));
                    $len = error_reporting(81);

                    if (false === eval("($testCode);") && $i = error_get_last())
                        user_error("PTL parse error: {$i['message']} on line " . $this->getLine());

                    error_reporting($len);
                    restore_error_handler();
                }

                $block = preg_split('/(\\$a\d+b) /su', $testCode, -1, PREG_SPLIT_DELIM_CAPTURE);

                $expression = $block[0];

                $i = 1;
                $len = count($block);
                while ($i < $len)
                {
                    $expression .= $this->evalVar($var[ $block[$i++] ], false, 'unified');
                    $expression .= $block[$i++];
                }

                if (!$this->addIF($limit, 'ELSEIF' === $blockname, $expression)) $this->pushText($a);
                else if ('ELSEIF' !== $blockname) $this->blockStack[] = $blockname;

                break;

            default:
                if (!(method_exists($this, 'add'.$blockname) && $this->{'add'.$blockname}($limit, $block))) $this->pushText($a);
            }
        }
        else $this->pushText($a);
    }

    protected function compileVar($var, $pipe)
    {
        $detail = array();

        preg_match_all("/({$this->Xexpression}|{$this->Xmodifier}|(?<=:)(?:{$this->Xexpression})?)/su", $var, $match);
        $detail[] = $match[1];

        preg_match_all("/{$this->XmodifierPipe}/su", $pipe, $match);
        foreach ($match[0] as &$match)
        {
            preg_match_all("/(?:^\\|{$this->Xmodifier}|:(?:{$this->Xexpression})?)/su", $match, $match);
            foreach ($match[0] as &$j) $j = ':' === $j ? "''" : substr($j, 1);
            unset($j);
            $detail[] = $match[0];
        }

        $Estart = '';
        $Eend = '';

        $i = count($detail);
        while (--$i)
        {
            class_exists('pipe_' . $detail[$i][0]) || user_error("Template warning: pipe_{$detail[$i][0]} does not exist");
            $Estart .= $this->makeModifier($detail[$i][0]) . '(';
            $Eend = $this->closeModifier . $Eend;

            $j = count($detail[$i]);
            while (--$j) $Eend = ',' . $this->evalVar($detail[$i][$j], true) . $Eend;
        }

        if (isset($detail[0][1]))
        {
            $Eend = $this->closeModifier . $Eend;

            $j = count($detail[0]);
            while (--$j) $Eend = ',' . $this->evalVar($detail[0][$j], true) . $Eend;

            $Eend[0] = '(';
            class_exists('pipe_' . $detail[0][0]) || user_error("Template warning: pipe_{$detail[0][0]} does not exist");
            $Estart .= $this->makeModifier($detail[0][0]);
        }
        else $Estart .= $this->evalVar($detail[0][0], true);

        if ("'" === $Estart[0])
        {
            $Estart = $this->getRawString($Estart);
            $this->pushText($Estart);
        }
        else if ('concat' === $this->mode)
        {
            $this->concat[++$this->concatLast] = $Estart . $Eend;
        }
        else $this->pushCode( $this->getEcho($Estart . $Eend) );
    }

    protected function evalVar($a, $translate = false, $forceType = false)
    {
        if ( '' === $a) return "''";
        if ('~' === $a) $a = 'g$__BASE__';
        if ('/' === $a) $a = 'g$__HOST__';

        if ('"' === $a[0] || "'" === $a[0])
        {
            $b = '"' === $a[0];

            if (!$b) $a = '"' . substr(preg_replace('/(?<!\\\\)((?:\\\\\\\\)*)"/su', '$1\\\\"', $a), 1, -1) . '"';
            $a = preg_replace("/(?<!\\\\)\\\\((?:\\\\\\\\)*)'/su", '$1\'', $a);
            $a = preg_replace('/(?<!\\\\)((\\\\?)(?:\\\\\\\\)*)\\$/su', '$1$2\\\\$', $a);
            $a = eval("return $a;");

            if ($b && '' !== trim($a))
            {
                if ($translate)
                {
                    $a = TRANSLATOR::get($a, Patchwork::__LANG__(), false);
/**/                if (DEBUG)
                        Patchwork::watch('debugSync');
                }
                else
                {
                    $this->mode = 'concat';
                    $this->concat = array('');
                    $this->concatLast = 0;

                    $this->makeVars($a);

                    if (!$this->concatLast)
                    {
                        $this->concat[0] = TRANSLATOR::get($this->concat[0], Patchwork::__LANG__(), false);
/**/                    if (DEBUG)
                            Patchwork::watch('debugSync');
                    }

                    for ($i = 0; $i<=$this->concatLast; $i+=2)
                    {
                        if ('' !== $this->concat[$i]) $this->concat[$i] = $this->makeVar("'" . $this->concat[$i]);
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

                $b = $i > 1 && '-' === $a[$i][0] && '' === $a[$i-1];

                $a[$i] = $this->evalVar($a[$i], false, 'number');

                if ($b && '0' === $a[$i]) $a[$i-1] = '-';

                $i += 2;
            }

            $a = implode($a);
            return $a;
        }

        return $this->makeVar($a, $forceType);
    }

    protected function evalVar_callback($m) {return $this->evalVar($m[0]);}

    protected function endError($unexpected, $expected)
    {
        user_error("PTL parse error: unexpected END:$unexpected" . ($expected ? ", expecting END:$expected" : '') . " on line " . $this->getLine());
    }

    static function nullErrorHandler()
    {
        // Nothing here
    }
}
