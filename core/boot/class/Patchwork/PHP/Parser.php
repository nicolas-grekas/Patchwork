<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

define('T_SEMANTIC',     1); // Primary type for semantic tokens
define('T_NON_SEMANTIC', 2); // Primary type for non-semantic tokens (whitespace and comment)

Patchwork_PHP_Parser::createToken(
    'T_CURLY_CLOSE', // Closing braces opened with T_CURLY_OPEN or T_DOLLAR_OPEN_CURLY_BRACES
    'T_STR_STRING'   // String index in interpolated string
);

defined('T_NS_SEPARATOR') || Patchwork_PHP_Parser::createToken('T_NS_SEPARATOR');

/**
 * Patchwork PHP Parser is a highly extensible framework for building high-performance PHP code
 * parsers around PHP's tokenizer extension.
 *
 * It does nothing on its own but implement an expert knowledge of tokenizer's special cases as well
 * as a predictable plugin mechanism for registering and dispatching tokens to a chain of parsers,
 * while remaining as fast and memory efficient as possible.
 *
 * It can be used for example to:
 * - compute static code analysis,
 * - verify coding practices for QA,
 * - backport some language features,
 * - extend the PHP language,
 * - build a code preprocessor,
 * - build an aspect weaver,
 * - etc.
 */
class Patchwork_PHP_Parser
{
    const T_OFFSET = 10000;

    protected

    // Declarations used by __construct()
    $serviceName = null,      // Fully qualified class identifier, defaults to get_class($this)
    $dependencies = array(    // (serviceName => shared properties) map before instanciation
                          ),  // (serviceName => service provider object) map after
    $callbacks = array(),     // Callbacks to register

    // Parse time state
    $index = 0,               // Index of the next token to be parsed
    $tokens = array(),        // To be parsed tokens, as returned by token_get_all()
    $types = array(),         // Types of already parsed tokens, excluding non-semantic tokens
    $texts = array(),         // Texts of already parsed tokens, including non-semantic tokens
    $line = 0,                // Line number of the current token
    $inString = 0,            // Odd/even when outside/inside string interpolation context
    $prevType,                // The last token type in $this->types
    $penuType,                // The penultimate token type in $this->types
    $tokenRegistry = array(); // (token type => callbacks) map


    private

    $parents = array(),
    $errors = array(),
    $nextRegistryIndex = 0,

    $parent,
    $registryIndex = 0,
    $haltCompilerTail = 4;


    private static

    $tokenNames = array(
        1 => 'T_SEMANTIC',
        2 => 'T_NON_SEMANTIC',
    );


    function __construct(self $parent = null)
    {
        $parent || $parent = __CLASS__ === get_class($this) ? $this : new self;

        $this->serviceName || $this->serviceName = get_class($this);
        $this->dependencies = (array) $this->dependencies;
        $this->parent = $parent;

        // Link shared properties of $parent and $this by reference

        if ($parent !== $this)
        {
            $v = array(
                'index',
                'tokens',
                'types',
                'texts',
                'line',
                'inString',
                'prevType',
                'penuType',
                'tokenRegistry',
                'parents',
                'errors',
                'nextRegistryIndex',
            );

            foreach ($v as $v) $this->$v =& $parent->$v;
        }
        else $this->nextRegistryIndex = -1 - PHP_INT_MAX;

        // Verify and set $this->dependencies to the (serviceName => service provider object) map

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
                user_error(get_class($this) . " failed dependency: {$v}", E_USER_WARNING);
                return;
            }

            $parent = $this->dependencies[$v] = $this->parents[$k];

            foreach ($c as $c => $k)
            {
                is_int($c) && $c = $k;

                if (!property_exists($parent, $c)) user_error(get_class($this) . " undefined parent property: {$v}->{$c}", E_USER_WARNING);
                if (!property_exists($this, $k)) user_error(get_class($this) . " undefined property: \$this->{$k}", E_USER_NOTICE);

                $this->$k =& $parent->$c;
            }
        }

        // Keep track of parents chained parsers

        $k = strtolower($this->serviceName);
        $this->parents[$k] = $this;

        // Keep parsers chaining order for callbacks ordering

        $this->registryIndex = $this->nextRegistryIndex;
        $this->nextRegistryIndex += 1 << (PHP_INT_SIZE << 2);

        $this->register($this->callbacks);
    }

    // Parse PHP source code

    function parse($code)
    {
        // Workaround mbstring overloading
        if (function_exists('mb_internal_encoding'))
        {
            $enc = mb_internal_encoding();
            mb_internal_encoding('8bit');
        }

        $this->tokens = $this->getTokens($code);
        $code = implode('', $this->parseTokens());

        function_exists('mb_internal_encoding') && mb_internal_encoding($enc);

        return $code;
    }

    // Get the errors emitted while parsing

    function getErrors()
    {
        ksort($this->errors);
        $e = array();
        foreach ($this->errors as $v) foreach ($v as $e[]) {}
        return $e;
    }

    // Enhanced token_get_all()

    protected function getTokens($code)
    {
        // Recursively traverse the inheritance chain defined by $this->parent

        if ($this->parent !== $this) return $this->parent->getTokens($code);

        // For binary safeness, check for unexpected characters (see http://bugs.php.net/54089)

        if (!$bin = PHP_VERSION_ID < 50300 && strpos($code, '\\'))
            for ($i = 0; $i < 32; ++$i)
                if ($i !== 0x09 && $i !== 0x0A && $i !== 0x0D && strpos($code, chr($i)))
                    if ($bin = true) break;

        $i = error_reporting(81);
        $t1 = token_get_all($code); // Warnings triggered here bypass any custom error handler
        error_reporting($i);

        if ($bin)
        {
            // Re-insert characters removed by token_get_all() as T_BAD_CHARACTER tokens
            $i = 0;
            $t0 = $t1;
            $t1 = array($t0[0]);
            $offset = strlen($t0[0][1]);

            while (isset($t0[++$i]))
            {
                $t = isset($t0[$i][1]) ? $t0[$i][1] : $t0[$i];

                if (isset($t[0]))
                    while ($t[0] !== $code[$offset])
                        $t1[] = array('\\' === $code[$offset] ? T_NS_SEPARATOR : T_BAD_CHARACTER, $code[$offset++]);

                $offset += strlen($t);
                $t1[] = $t0[$i];
                unset($t0[$i]);
            }

            $t0 = end($t1);

            if (T_HALT_COMPILER !== $t0[0])
                while (isset($code[$offset]))
                    $t1[] = array('\\' === $code[$offset] ? T_NS_SEPARATOR : T_BAD_CHARACTER, $code[$offset++]);
        }

        if (empty($t1)) return $t1;

        // Restore data after __halt_compiler()
        // workaround missed fix to http://bugs.php.net/54089

        $bin = end($t1);

        if (T_HALT_COMPILER === $bin[0])
        {
            if (!isset($offset) && !$offset = 0)
                foreach ($t1 as $t0) $offset += isset($t0[1]) ? strlen($t0[1]) : 1;

            if (isset($code[$offset]))
            {
                $code = $this->getTokens('<?php ' . substr($code, $offset));
                array_splice($code, 0, 1, $t1);
                $t1 = $code;
            }
        }

        return $t1;
    }

    // Parse raw tokens already loaded in $this->tokens

    protected function parseTokens()
    {
        // Recursively traverse the inheritance chain defined by $this->parent

        if ($this->parent !== $this) return $this->parent->parseTokens();

        // Alias properties to local variables, initialize them

        $line     =& $this->line;     $line     = 1;
        $i        =& $this->index;    $i        = 0;
        $inString =& $this->inString; $inString = 0;
        $types    =& $this->types;    $types    = array();
        $texts    =& $this->texts;    $texts    = array('');
        $prevType =& $this->prevType; $prevType = false;
        $penuType =& $this->penuType; $penuType = false;
        $tokens   =& $this->tokens;
        $reg      =& $this->tokenRegistry;

        $j         = 0;
        $curly     = 0;
        $curlyPool = array();

        while (isset($tokens[$i]))
        {
            $t =& $tokens[$i];    // Get the next token
            unset($tokens[$i++]); // Free memory and move $this->index forward

            // Set primary type and fix string interpolation context
            //
            // String interpolation is hard, especially before PHP 5.2.3.
            // See this thread on the PHP internals mailing-list for detailed background:
            // http://www.mail-archive.com/internals@lists.php.net/msg27154.html
            //
            // Since PHP before 5.2.3 is supported, many tokens have to be tagged
            // as T_ENCAPSED_AND_WHITESPACE when inside interpolated strings.
            //
            // Further than that, two gotchas remain inside string interpolation:
            // - tag closing braces as T_CURLY_CLOSE when they are opened with curly braces
            //   tagged as T_CURLY_OPEN or T_DOLLAR_OPEN_CURLY_BRACES, to make
            //   them easy to distinguish from regular code "{" / "}" pairs,
            // - tag arrays' or objects' string indexes as T_STR_STRING.

            $priType = 1; // T_SEMANTIC

            if (isset($t[1]))
            {
                if ($inString & 1) switch ($t[0])
                {
                case T_VARIABLE:
                case T_STR_STRING:
                case T_CURLY_OPEN:
                case T_CURLY_CLOSE:
                case T_END_HEREDOC:
                case T_ENCAPSED_AND_WHITESPACE:
                case T_DOLLAR_OPEN_CURLY_BRACES: break;
                case T_STRING:
                    if ('[' === $prevType || T_OBJECT_OPERATOR === $prevType)
                    {
                        $t[0] = T_STR_STRING;
                        break;
                    }
                case T_NUM_STRING: if ('[' === $prevType) break;
                case T_OBJECT_OPERATOR: if (T_VARIABLE === $prevType) break;
                default:
                    if ('[' === $prevType && preg_match("/^[_a-zA-Z]/", $t[1][0])) $t[0] = T_STR_STRING;
                    else $t[0] = T_ENCAPSED_AND_WHITESPACE;
                }
                else if ('b"' === $t) $t = array('"', 'b"'); // Binary string syntax b"..."
                else switch ($t[0])
                {
                case T_WHITESPACE:
                case T_COMMENT:
                case T_DOC_COMMENT:
                case T_BAD_CHARACTER: $priType = 2; // T_NON_SEMANTIC
                }
            }
            else
            {
                $t = array($t, $t);

                if ($inString & 1) switch ($t[0])
                {
                case '"':
                case '`': break;
                case ']': if (T_STR_STRING === $prevType || T_NUM_STRING === $prevType) break;
                case '[': if (T_VARIABLE   === $prevType && '[' === $t[0]) break;
                default: $t[0] = T_ENCAPSED_AND_WHITESPACE;
                }
                else if ('}' === $t[0] && !$curly) $t[0] = T_CURLY_CLOSE;
            }

            // Trigger callbacks

            if (isset($reg[$t[0]]) || isset($reg[$priType]))
            {
                $n = $t[0];
                $t[2] = array($priType => $priType);

                if (isset($reg[$priType])) $callbacks = $reg[$priType];
                else $callbacks = array();

                for (;;)
                {
                    $t[2][$n] = $n;

                    if (isset($reg[$n]))
                    {
                        $callbacks += $reg[$n];

                        // Callbacks triggering are always ordered:
                        // - first by parsers' instanciation order
                        // - then by callbacks' registration order
                        // - callbacks registered with a tilde prefix
                        //   are then called in reverse order.
                        ksort($callbacks);
                    }

                    foreach ($callbacks as $k => $c)
                    {
                        unset($callbacks[$k]);

                        // $t is the current token:
                        // $t = array(
                        //     0 => token's main type - a single character or a T_* constant,
                        //          as returned by token_get_all()
                        //     1 => token's text - its source code excerpt as a string
                        //     2 => an array of token's types and subtypes
                        // )

                        if ($k < 0)
                        {
                            $n = $c[0]->$c[1]($t);

                            // Non-tilde-prefixed callback can return:
                            // - false, which cancels the current token
                            // - a new token type, which is added to $t[2] and loads the
                            //   related callbacks in the current callbacks stack
                            // - or nothing (null)

                            if (false === $n) continue 3;
                            if ($n && empty($t[2][$n])) continue 2;
                        }
                        else if (null !== $c[0]->$c[1]($t))
                        {
                            user_error("No return value is expected for tilde-registered callback: " . get_class($c[0]) . '->' . $c[1] . '()', E_USER_NOTICE);
                        }
                    }

                    break;
                }
            }

            // Commit to $this->texts

            $texts[++$j] =& $t[1];

            if (2 === $priType) // T_NON_SEMANTIC
            {
                $line += substr_count($t[1], "\n");
                continue;
            }

            // For semantic tokens only: populate $this->types, $this->prevType and $this->penuType

            $penuType  = $prevType;
            $types[$j] = $prevType = $t[0];

            // Parsing context analysis related to string interpolation and line numbering

            if (isset($prevType[0])) switch ($prevType)
            {
            case '{': ++$curly; break;
            case '}': --$curly; break;
            case '"':
            case '`': $inString += ($inString & 1) ? -1 : 1;
            }
            else switch ($prevType)
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

            case T_HALT_COMPILER:
                4 === $this->haltCompilerTail && $this->register('tagHaltCompilerData');
                break;
            }
        }

        // Free memory thanks to copy-on-write
        $j = $texts;
        $types = $texts = $tokens = $reg = $this->parents = $this->parent = null;
        return $j;
    }


    // Set an error on input code inside parsers

    protected function setError($message, $type)
    {
        $this->errors[(int) $this->line][] = array(
            'type' => $type,
            'message' => $message,
            'line' => (int) $this->line,
            'parser' => get_class($this),
        );
    }

    // Register callbacks for the next tokens

    protected function register($method)
    {
        foreach ((array) $method as $method => $type)
        {
            if (empty($method[0]))
            {
                $method = $type;
                $type = 1; // T_SEMANTIC
            }

            if ('~' === $method[0])
            {
                $desc = -1;
                $method = substr($method, 1);
            }
            else $desc = 0;

            foreach ((array) $type as $type)
            {
                1 === $type && $s1 = 1; // T_SEMANTIC
                2 === $type && $s2 = 1; // T_NON_SEMANTIC
                $this->tokenRegistry[$type][++$this->registryIndex ^ $desc] = array($this, $method);
            }
        }

        isset($s1) && ksort($this->tokenRegistry[1]); // T_SEMANTIC
        isset($s2) && ksort($this->tokenRegistry[2]); // T_NON_SEMANTIC
    }

    // Unregister callbacks for the next tokens

    protected function unregister($method)
    {
        foreach ((array) $method as $method => $type)
        {
            if (empty($method[0]))
            {
                $method = $type;
                $type = 1; // T_SEMANTIC
            }

            if ('~' === $method[0])
            {
                $desc = -1;
                $method = substr($method, 1);
            }
            else $desc = 0;

            foreach ((array) $type as $type)
            {
                if (isset($this->tokenRegistry[$type]))
                {
                    foreach ($this->tokenRegistry[$type] as $k => $v)
                        if (array($this, $method) === $v && ($desc ? $k > 0 : $k < 0))
                            unset($this->tokenRegistry[$type][$k]);

                    if (!$this->tokenRegistry[$type]) unset($this->tokenRegistry[$type]);
                }
            }
        }
    }

    // Read-ahead the input token stream

    protected function &getNextToken(&$i = null)
    {
        static $ns = array( // Non-semantic types
            T_COMMENT => 1,
            T_WHITESPACE => 1,
            T_DOC_COMMENT => 1,
            T_BAD_CHARACTER => 1
        );

        null === $i && $i = $this->index;
        while (isset($this->tokens[$i], $ns[$this->tokens[$i][0]])) ++$i;
        isset($this->tokens[$i]) || $this->tokens[$i] = array(T_WHITESPACE, '');

        return $this->tokens[$i++];
    }

    // Inject tokens in the input stream

    protected function unshiftTokens()
    {
        $token = func_get_args();
        isset($token[1]) && $token = array_reverse($token);

        foreach ($token as $token)
            $this->tokens[--$this->index] = $token;

        return false;
    }

    // Skip 3 tokens: "(", ")" then ";" or T_CLOSE_TAG
    // then merge the remaining data in a single T_INLINE_HTML token
    // backports the fix to http://bugs.php.net/54089

    private function tagHaltCompilerData()
    {
        if (0 === --$this->haltCompilerTail)
        {
            $this->unregister(__FUNCTION__);
            $tokens =& $this->tokens;
            foreach ($tokens as &$t) isset($t[1]) && $t = $t[1];
            $tokens = array($this->index => array(T_INLINE_HTML, implode('', $tokens)));
        }
    }

    // Create new token sub-types

    static function createToken($name)
    {
        static $type = self::T_OFFSET;
        $name = func_get_args();
        foreach ($name as $name)
        {
            define($name, ++$type);
            self::$tokenNames[$type] = $name;
        }
    }

    // Get the symbolic name of a given PHP token's type or sub-type as created by self::createToken

    static function getTokenName($type)
    {
        if (is_string($type)) return $type;
        return $type < 3 || self::T_OFFSET < $type ? self::$tokenNames[$type] : token_name($type);
    }


    // Returns a parsable string representation of a variable
    // Similar to var_export() with these differencies:
    // - it can be used inside output buffering callbacks
    // - it always returns a single ligne of code
    //   even for arrays or when a string contains CR/LF
    // - it works only on static types (scalars and non-recursive arrays)

    static function export($a)
    {
        switch (true)
        {
        case true  === $a: return 'true';
        case false === $a: return 'false';
        case  INF  === $a: return  'INF';
        case -INF  === $a: return '-INF';
        case is_int($a):   return (string) $a;

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
            static $depth = -1;

            // For recursive arrays, rather than an infinite loop,
            // trigger a "PHP Fatal error: Nesting level too deep"
            ++$depth || $a === $a;

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

            --$depth;

            return 'array(' . implode(',', $b) . ')';

        case is_float($a):
            if (is_nan($a)) return 'NAN';
            $b = sprintf('%.14F', $a);
            $a = sprintf('%.17F', $a);
            return rtrim((float) $b === (float) $a ? $b : $a, '.0');

        default: return 'null';
        }
    }
}
