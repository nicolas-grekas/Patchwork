<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/

Patchwork_PHP_Parser::createToken('T_CURLY_CLOSE');          // Closing braces opened with T_CURLY_OPEN or T_DOLLAR_OPEN_CURLY_BRACES
Patchwork_PHP_Parser::createToken('T_KEY_STRING');           // Array access in interpolated string
Patchwork_PHP_Parser::createToken('T_UNEXPECTED_CHARACTER'); // Unexpected character in input


class Patchwork_PHP_Parser
{
    protected

    // Declarations used by __construct()
    $dependencyName = null,    // Fully qualified class identifier, defaults to get_class($this)
    $dependencies   = array(), // (dependencyName => shared properties) map before instanciation, then (dependencyName => dependency object) map after
    $callbacks      = array(), // Callbacks to be registered

    // Parse time read-only (or know what you do) properties
    $line     = 0,               // Line number of the current token
    $index    = 0,               // Index of the next token in $this->tokens
    $inString = 0,               // Odd/even when outside/inside string interpolation context
    $tokens   = array(),         // To be parsed tokens, as returned by token_get_all()
    $types    = array(),         // Types of already parsed tokens, excluding sugar tokens
    $texts    = array(),         // Texts of already parsed tokens, including sugar tokens
    $lastType,                   // The last token type in $this->types
    $penuType,                   // The penultimate token type in $this->types
    $tokenRegistry    = array(), // (token type => callbacks) map
    $catchallRegistry = array(); // List of catch-all-excluding-sugar-tokens callbacks


    private

    $parents = array(),
    $errors  = array(),
    $nextRegistryIndex = 0,

    $parent,
    $registryIndex = 0;


    private static $tokenNames = array();


    function __construct(self $parent = null)
    {
        $parent || $parent = __CLASS__ === get_class($this) ? $this : new self;

        $this->dependencyName || $this->dependencyName = get_class($this);
        $this->dependencies = (array) $this->dependencies;
        $this->parent = $parent;

        // Link shared properties of $parent and $this by reference

        if ($parent !== $this)
        {
            $v = array(
                'line',
                'tokens',
                'inString',
                'index',
                'types',
                'texts',
                'lastType',
                'penuType',
                'tokenRegistry',
                'catchallRegistry',
                'parents',
                'errors',
                'nextRegistryIndex',
            );

            foreach ($v as $v) $this->$v =& $parent->$v;
        }
        else $this->nextRegistryIndex = -1 - PHP_INT_MAX;

        // Verify and set $this->dependencies to the (dependencyName => dependency object) map

        foreach ($this->dependencies as $k => $v)
        {
            unset($this->dependencies[$k]);

            if (is_string($k))
            {
                $c = (array) $v;
                $v = $k;
            }
            else $c = array();

            $k = strtolower('\\' !== $v[0] ? __CLASS__ . '_' . $v : substr($v, 1));

            if (!isset($this->parents[$k]))
            {
                return trigger_error(get_class($this) . ' failed dependency: ' . $v);
            }

            $this->dependencies[$v] = $this->parents[$k];

            foreach ($c as $c) $this->$c =& $this->parents[$k]->$c;
        }

        // Keep track of parents chained parsers

        $k = strtolower($this->dependencyName);
        $this->parents[$k] = $this;

        // Keep parsers chaining order for callbacks ordering

        $this->registryIndex = $this->nextRegistryIndex;
        $this->nextRegistryIndex += 1 << (PHP_INT_SIZE << 2);

        empty($this->callbacks) || $this->register();
    }

    function getErrors()
    {
        ksort($this->errors);
        $e = array();
        foreach ($this->errors as $v) foreach ($v as $e[]) {}
        return $e;
    }

    function parse($code)
    {
        $this->tokens = $this->getTokens($code);
        return implode('', $this->parseTokens());
    }

    protected function getTokens($code)
    {
        // Return token_get_all() after recursively traversing the inheritance chain defined by $this->parent

        if ($this->parent !== $this) return $this->parent->getTokens($code);

        // As token_get_all() is not binary safe, check for unexpected characters (see http://bugs.php.net/54089)

        if (!$bin = version_compare(PHP_VERSION, '5.3.0') < 0 && strpos($code, '\\'))
        {
            for ($i = 0; $i < 32; ++$i)
                if ($i !== 0x09 && $i !== 0x0A && $i !== 0x0D && strpos($code, chr($i)))
                    break $bin = true;
        }

        if (!$bin) return token_get_all($code);

        if (function_exists('mb_internal_encoding'))
        {
            // Workaround mbstring overloading
            $bin = @mb_internal_encoding();
            @mb_internal_encoding('8bit');
        }

        $t0     = @token_get_all($code);
        $t1     = array($t0[0]);
        $offset = strlen($t0[0][1]);
        $i      = 0;

        while (isset($t0[++$i]))
        {
            $t = isset($t0[$i][1]) ? $t0[$i][1] : $t0[$i];

            if (isset($t[0]))
                while ($t[0] !== $code[$offset])
                    $t1[] = array(T_UNEXPECTED_CHARACTER, $code[$offset++]);

            $offset += strlen($t);
            $t1[] = $t0[$i];
            unset($t0[$i]);
        }

        function_exists('mb_internal_encoding') && mb_internal_encoding($bin);

        return $t1;
    }

    protected function parseTokens()
    {
        // Parse raw tokens already loaded in $this->tokens after recursively traversing $this->parent

        if ($this->parent !== $this) return $this->parent->parseTokens();

        // Alias properties to local variables, initialize them

        $line     =& $this->line;     $line     = 1;
        $i        =& $this->index;    $i        = 0;
        $inString =& $this->inString; $inString = 0;
        $types    =& $this->types;    $types    = array();
        $texts    =& $this->texts;    $texts    = array('');
        $lastType =& $this->lastType; $lastType = false;
        $penuType =& $this->penuType; $penuType = false;
        $tokens   =& $this->tokens;
        $tkReg    =& $this->tokenRegistry;
        $caReg    =& $this->catchallRegistry;

        $j         = 0;
        $curly     = 0;
        $curlyPool = array();

        while (isset($tokens[$i]))
        {
            $t =& $tokens[$i];    // Get the next token
            unset($tokens[$i++]); // Free memory and move $this->index forward

            // Set parsing context related to sugar tokens and string interpolation

            $sugar = 0;

            if (isset($t[1]))
            {
                if ($inString & 1) switch ($t[0])
                {
                case T_VARIABLE:
                case T_KEY_STRING:
                case T_CURLY_OPEN:
                case T_CURLY_CLOSE:
                case T_END_HEREDOC:
                case T_DOLLAR_OPEN_CURLY_BRACES: break;
                case T_STRING:     if ('[' === $lastType) $t[0] = T_KEY_STRING;
                case T_NUM_STRING: if ('[' === $lastType) break;
                case T_OBJECT_OPERATOR: if (T_VARIABLE === $lastType) break;
                default: $t[0] = T_ENCAPSED_AND_WHITESPACE;
                }
                else switch ($t[0])
                {
                case T_WHITESPACE: // Here are all "sugar tokens"
                case T_COMMENT:
                case T_DOC_COMMENT:
                case T_UNEXPECTED_CHARACTER: $sugar = 1;
                }
            }
            else
            {
                $t = array($t, $t);

                if ($inString & 1) switch ($t[0])
                {
                case '"':
                case '`': break;
                case ']': if (T_KEY_STRING === $lastType || T_NUM_STRING === $lastType) break;
                case '[': if (T_VARIABLE   === $lastType && '[' === $t[0]) break;
                default: $t[0] = T_ENCAPSED_AND_WHITESPACE;
                }
                else if ('}' === $t[0] && !$curly) $t[0] = T_CURLY_CLOSE;
            }

            // Trigger callbacks

            if (isset($tkReg[$t[0]]) || ($caReg && !$sugar))
            {
                $t[2] = array();
                $k = $t[0];
                $callbacks = $sugar ? array() : $caReg;

                do
                {
                    $t[2][$k] = $k;

                    if (isset($tkReg[$k]))
                    {
                        $callbacks += $tkReg[$k];

                        // Callbacks triggering are always ordered:
                        // - first by parsers' instanciation order
                        // - then by callbacks' registration order
                        ksort($callbacks);
                    }

                    foreach ($callbacks as $k => $c)
                    {
                        unset($callbacks[$k]);

                        // $t is the current token:
                        // $t = array(
                        //     0 => token's main type - a single character or a T_* constant, as returned by token_get_all()
                        //     1 => token's text      - its source code excerpt as a string
                        //     2 => an array of token's types and subtypes
                        // )

                        $k = $c[0]->$c[1]($t);

                        // A callback can return:
                        // - false, which cancels the current token
                        // - a new token type, which is added to $t[2] and loads the
                        //   related callbacks in the current callbacks stack
                        // - or nothing (null)

                        if (false === $k) continue 3;
                        if ($k && empty($t[2][$k])) continue 2;
                    }

                    break;
                }
                while (1);
            }

            // Commit to $this->texts

            $texts[++$j] =& $t[1];

            if ($sugar)
            {
                $line += substr_count($t[1], "\n");
                continue;
            }

            // For non-sugar tokens only: populate $this->types, $this->lastType and $this->penuType

            $penuType  = $lastType;
            $types[$j] = $lastType = $t[0];

            // Parsing context analysis related to string interpolation

            if (isset($lastType[0])) switch ($lastType)
            {
            case '{': ++$curly; break;
            case '}': --$curly; break;
            case '"':
            case '`': $inString += ($inString & 1) ? -1 : 1;
            }
            else switch ($lastType)
            {
            case T_CONSTANT_ENCAPSED_STRING:
            case T_ENCAPSED_AND_WHITESPACE:
            case T_OPEN_TAG_WITH_ECHO:
            case T_INLINE_HTML:
            case T_CLOSE_TAG:
            case T_OPEN_TAG:
                $line += substr_count($t[1], "\n");
                break;

            case T_DOLLAR_OPEN_CURLY_BRACES:
            case T_CURLY_OPEN:    $curlyPool[] = $curly; $curly = 0;
            case T_START_HEREDOC: ++$inString; break;

            case T_CURLY_CLOSE:   $curly = array_pop($curlyPool);
            case T_END_HEREDOC:   --$inString; break;

            case T_HALT_COMPILER: break 2; // See http://bugs.php.net/54089
            }
        }

        // Free memory thanks to copy-on-write
        $j = $texts;
        $types = $texts = $tokens = $tkReg = $caReg = $this->parents = $this->parent = null;
        return $j;
    }


    protected function setError($message, $type)
    {
        $this->errors[(int) $this->line][] = array($message, (int) $this->line, get_class($this), $type);
    }

    protected function register($method = null)
    {
        null === $method && $method = $this->callbacks;

        foreach ((array) $method as $method => $type)
        {
            if (is_int($method))
            {
                isset($sort) || $sort = 1;
                $this->catchallRegistry[++$this->registryIndex] = array($this, $type);
            }
            else foreach ((array) $type as $type)
            {
                $this->tokenRegistry[$type][++$this->registryIndex] = array($this, $method);
            }
        }

        isset($sort) && ksort($this->catchallRegistry);
    }

    protected function unregister($method = null)
    {
        null === $method && $method = $this->callbacks;

        foreach ((array) $method as $method => $type)
        {
            if (is_int($method))
            {
                foreach ($this->catchallRegistry as $k => $v)
                    if (array($this, $type) === $v)
                        unset($this->catchallRegistry[$k]);
            }
            else foreach ((array) $type as $type)
            {
                if (isset($this->tokenRegistry[$type]))
                {
                    foreach ($this->tokenRegistry[$type] as $k => $v)
                        if (array($this, $method) === $v)
                            unset($this->tokenRegistry[$type][$k]);

                    if (!$this->tokenRegistry[$type]) unset($this->tokenRegistry[$type]);
                }
            }
        }
    }

    protected function &getNextToken(&$i = null)
    {
        static $sugar = array(T_WHITESPACE => 1, T_COMMENT => 1, T_DOC_COMMENT => 1, T_UNEXPECTED_CHARACTER => 1);

        null === $i && $i = $this->index;
        while (isset($this->tokens[$i], $sugar[$this->tokens[$i][0]])) ++$i;
        isset($this->tokens[$i]) || $this->tokens[$i] = array(T_WHITESPACE, '');

        return $this->tokens[$i++];
    }

    protected function unshiftTokens()
    {
        $token = func_get_args();
        isset($token[1]) && $token = array_reverse($token);

        foreach ($token as $token)
            $this->tokens[--$this->index] = $token;

        return false;
    }


    static function createToken($name)
    {
        static $type = 0;
        define($name, --$type);
        self::$tokenNames[$type] = $name;
    }

    static function getTokenName($type)
    {
        if (is_string($type)) return $type;
        return isset(self::$tokenNames[$type]) ? self::$tokenNames[$type] : token_name($type);
    }

    static function export($a)
    {
        switch (true)
        {
        default:           return (string) $a;
        case true  === $a: return 'true';
        case false === $a: return 'false';
        case null  === $a: return 'null';
        case  INF  === $a: return  'INF';
        case -INF  === $a: return '-INF';
        case NAN   === $a: return 'NAN';

        case is_string($a):
            return $a === strtr($a, "\r\n\0", '---')
                ? ("'" . str_replace(
                        array(  '\\',   "'"),
                        array('\\\\', "\\'"), $a
                    ) . "'")
                : ('"' . str_replace(
                        array(  "\\",   '"',   '$',  "\r",  "\n",  "\0"),
                        array('\\\\', '\\"', '\\$', '\\r', '\\n', '\\0'), $a
                    ) . '"');

        case is_array($a):
            $i = 0;
            $b = array();

            foreach ($a as $k => $a)
            {
                if (is_int($k) && 0 <= $k)
                {
                    $b[] = ($k !== $i ? $k . '=>' : '') . self::export($a);
                    $i = $k + 1;
                }
                else
                {
                    $b[] = self::export($k) . '=>' . self::export($a);
                }
            }

            return 'array(' . implode(',', $b) . ')';

        case is_object($a):
            return 'unserialize(' . self::export(serialize($a)) . ')';

        case is_float($a):
            $b = sprintf('%.14F', $a);
            $a = sprintf('%.17F', $a);
            return rtrim((float) $b === (float) $a ? $b : $a, '.0');
        }
    }
}
