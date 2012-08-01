<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

/**
 * The FunctionOverriding parser replaces function calls by other function calls.
 */
class Patchwork_PHP_Parser_FunctionOverriding extends Patchwork_PHP_Parser
{
    protected

    $newOverrides,
    $overrides = array(),
    $callbacks = array(
        'tagVariableVar' => '(',
        'tagUseFunction' => T_USE_FUNCTION,
    ),

    $scope, $class, $namespace, $nsResolved,
    $dependencies = array(
        'ConstantInliner' => 'scope',
        'ClassInfo' => array('class', 'namespace', 'nsResolved'),
    ),

    $varVarLead = '${patchwork_override_resolve_ref(',
    $varVarTail = ",\$\x9D)}";


    protected static

    $staticOverrides = array(),

    // List of native functions that could trigger __autoload()
    $autoloader = array(

        // No callback parameter involved or complex behaviour
        '__autoload'        => 0,
        'class_exists'      => 0,
        'class_parents'     => 0,
        'class_implements'  => 0,
        'constant'          => 0,
        'defined'           => 0,
        'get_class_methods' => 0,
        'get_class_vars'    => 0,
        'get_parent_class'  => 0,
        'interface_exists'  => 0,
        'method_exists'     => 0,
        'preg_replace'      => 0,
        'property_exists'   => 0,
        'spl_autoload'      => 0,
        'unserialize'       => 0,
        'assert_options'    => 0, // callback as second arg, but only if first arg is ASSERT_CALLBACK
        'curl_setopt'       => 0, // callback as third arg, but only if second arg is CURLOPT_*FUNCTION
        'curl_setopt_array' => 0, // same as multiple curl_setopt
        'filter_var'        => 0, // callback in third arg, but only if second arg is FILTER_CALLBACK
        'filter_var_array'  => 0, // same as multiple filter_var
        'ibase_set_event_handler'   => 0, // callback as first xor second arg
        'sqlite_create_function'    => 0, // don't introduce any difference with SQLiteDatabase->createFunction
        'sqlite_create_aggregate'   => 0, // don't introduce any difference with SQLiteDatabase->createAggregate
        'stream_context_create'     => 0, // callback may be in second arg
        'stream_context_set_params' => 0, // callback may be in second arg
        'stream_filter_register'    => 0,
        'stream_wrapper_register'   => 0,

        // Callback in the first parameter
        'array_map'                    => 1,
        'call_user_func'               => 1,
        'call_user_func_array'         => 1,
        'header_register_callback'     => 1,
        'is_callable'                  => 1,
        'newt_set_help_callback'       => 1,
        'newt_set_suspend_callback'    => 1,
        'ob_start'                     => 1,
        'readline_completion_function' => 1,
        'register_shutdown_function'   => 1,
        'register_tick_function'       => 1,
        'set_exception_handler'        => 1,
        'set_error_handler'            => 1,
        'spl_autoload_register'        => 1,
        'sybase_set_message_handler'   => 1,

        // Callback in the second parameter
        'array_filter'                           => 2,
        'array_reduce'                           => 2,
        'array_walk'                             => 2,
        'array_walk_recursive'                   => 2,
        'gupnp_service_info_get_introspection'   => 2,
        'newt_component_add_callback'            => 2,
        'newt_entry_set_filter'                  => 2,
        'pcntl_signal'                           => 2,
        'preg_replace_callback'                  => 2,
        'readline_callback_handler_install'      => 2,
        'runkit_sandbox_output_handler'          => 2,
        'usort'                                  => 2,
        'uksort'                                 => 2,
        'uasort'                                 => 2,
        'xml_set_character_data_handler'         => 2,
        'xml_set_default_handler'                => 2,
        'xml_set_element_handler'                => 2,
        'xml_set_end_namespace_decl_handler'     => 2,
        'xml_set_processing_instruction_handler' => 2,
        'xml_set_start_namespace_decl_handler'   => 2,
        'xml_set_notation_decl_handler'          => 2,
        'xml_set_external_entity_ref_handler'    => 2,
        'xml_set_unparsed_entity_decl_handler'   => 2,
        'xslt_set_error_handler'                 => 2,

        // Callback in the third or fourth parameter
        'gupnp_control_point_callback_set' => 3,
        'gupnp_context_timeout_add'        => 3,
        'gupnp_service_proxy_callback_set' => 3,
        'gupnp_device_action_callback_set' => 4,
        'gupnp_service_proxy_add_notify'   => 4,

        // Callback in the last parameter
        'array_diff_ukey'         => -1,
        'array_diff_uassoc'       => -1,
        'array_intersect_ukey'    => -1,
        'array_udiff_assoc'       => -1,
        'array_udiff'             => -1,
        'array_uintersect_assoc'  => -1,
        'array_uintersect'        => -1,

        // Callback in the two last parameter
        'array_udiff_uassoc'      => -2,
        'array_uintersect_uassoc' => -2,

        'session_set_save_handler' => -6, // 6 callback parameters
    );


    static function loadOverrides($overrides)
    {
        foreach ($overrides as $k => $v)
            if (function_exists('__patchwork_' . $k))
                self::$staticOverrides[strtolower($k)] = 0 === strcasecmp($k, $v) ? '__patchwork_' . $v : $v;

        return self::$staticOverrides;
    }

    function __construct(parent $parent, &$new_overrides = array())
    {
        parent::__construct($parent);
        $this->overrides = self::$staticOverrides;
        $this->newOverrides =& $new_overrides;
    }

    protected function tagVariableVar(&$token)
    {
        if ( ('}' === $this->prevType || T_VARIABLE === $this->prevType)
          && !in_array($this->penuType, array(T_NEW, T_OBJECT_OPERATOR, T_DOUBLE_COLON)) )
        {
            $t =& $this->types;
            end($t);
            $i = key($t);

            if (T_VARIABLE === $this->prevType && '$' !== $this->penuType)
            {
                if ('$this' !== $a = $this->texts[$i])
                {
                    $this->texts[$i] = $this->varVarLead . $a . $this->varVarTail;
                }
            }
            else
            {
                if ('}' === $this->prevType)
                {
                    $a = 1;
                    $b = array($i, 0);
                    prev($t);

                    while ($a > 0 && null !== $i = key($t))
                    {
                        if ('{' === $t[$i]) --$a;
                        else if ('}' === $t[$i]) ++$a;
                        prev($t);
                    }

                    $b[1] = $i;

                    if ('$' !== prev($t)) return;
                }
                else $a = $b = 0;

                do {} while ('$' === prev($t));

                if (in_array(pos($t), array(T_NEW, T_OBJECT_OPERATOR, T_DOUBLE_COLON))) return;

                $a =& $this->texts;

                $b && $a[$b[0]] = $a[$b[1]] = '';

                next($t);
                $a[key($t)] = $this->varVarLead;

                end($t);
                $a[key($t)] .= $this->varVarTail;
            }
        }
    }

    protected function tagUseFunction(&$token)
    {
        // Override functions only if they are fully namespace resolved

        $a = strtolower($this->nsResolved);

        if ('\\' !== $this->nsResolved[0])
        {
            $e = isset($this->overrides[$a]) || isset(self::$autoloader[$a]) || 0 === strcasecmp('function_exists', $a);
            $e || $a = substr(strtolower($this->namespace) . $a, 1);
            $e = $e || isset($this->overrides[$a]) || isset(self::$autoloader[$a]);
            $e && $this->setError("Unresolved namespaced function call ({$this->nsResolved}), skipping overriding", E_USER_WARNING);
            return;
        }

        $a = substr($a, 1);

        if ('patchwork\functionoverride' === $a)
        {
            if ($this->overrideFunction())
            {
                $this->dependencies['ClassInfo']->removeNsPrefix();
                return false;
            }
        }
        else if (isset($this->overrides[$a]))
        {
            $a = explode('::', $this->overrides[$a], 2);

            if (1 === count($a))
            {
                if (!$this->class && 0 === strcasecmp($a[0], $this->scope->funcC)) return;
            }
            else if (empty($this->class->nsName) || strcasecmp(strtr($a[0], '\\', '_'), strtr($this->class->nsName, '\\', '_')))
            {
                $this->unshiftTokens(
                    array(T_DOUBLE_COLON, '::'),
                    array(T_STRING, $a[1])
                );
            }
            else return;

            $this->dependencies['ClassInfo']->removeNsPrefix();
            $this->unshiftTokens(array(T_STRING, $a[0]));
            if ($this->namespace) $this->unshiftTokens(array(T_NS_SEPARATOR, '\\'));

            return false;
        }
        else if (isset(self::$autoloader[$a]) || (!$this->class && 0 === strcasecmp('function_exists', $a) && 0 !== strcasecmp('patchwork_override_resolve', $this->scope->funcC)))
        {
            new Patchwork_PHP_Parser_Bracket_Callback($this, isset(self::$autoloader[$a]) ? self::$autoloader[$a] : 1, $this->overrides);

            if ('&' === $this->prevType)
            {
                $a =& $this->types;
                end($a);
                $this->texts[key($a)] = '';
                unset($a[key($a)]);
            }
        }
    }

    protected function overrideFunction()
    {
        $u = array(array(T_FUNCTION, 'function'), array(T_WHITESPACE, ' '));

        $this->getNextToken($i); // this is an opening bracket

        if ('&' === $n =& $this->getNextToken($i))
        {
            $u[] = $n;
            $n = array(T_WHITESPACE, '');
            $n =& $this->getNextToken($i);
        }

        if (T_STRING !== $n[0]) return;
        $this->replacedFunction = $n[1];
        $n[1] = '__patchwork_' . $n[1];
        $u[] = $n;
        $n = array(T_WHITESPACE, '');

        if (',' === $n =& $this->getNextToken($i))
        {
            $n = array(T_WHITESPACE, '');
            call_user_func_array(array($this, 'unshiftTokens'), $u);
            $this->register(array('catchOverride' => array(T_USE_CONSTANT, T_USE_CLASS)));
            return true;
        }
    }

    protected function catchOverride(&$token)
    {
        $this->unregister(array('catchOverride' => array(T_USE_CONSTANT, T_USE_CLASS)));

        $this->bracket = 0;
        $this->arguments = array();
        $this->replacementFunction = ltrim($this->nsResolved, '\\');

        $n =& $this->getNextToken($i);

        if (T_DOUBLE_COLON === $n[0])
        {
            $this->replacementFunction .= '::';
            $n = array(T_WHITESPACE, '');
            $n =& $this->getNextToken($i);
            if (T_STRING !== $n[0]) return;
            $this->replacementFunction .= $n[1];
            $n = array(T_WHITESPACE, '');
            $n =& $this->getNextToken($i);
        }
        else if (0 < strpos($this->nsResolved, '\\', 1))
        {
            $this->replacementFunction .= '::' . $this->replacedFunction;
        }

        switch ($n)
        {
        case ',':
            $n = array(T_WHITESPACE, '');
            $this->register(array('catchArguments' => T_VARIABLE));
            // no break;
        case ')':
            $this->register(array('catchBrackets' => array('(', ')')));
            $this->dependencies['ClassInfo']->removeNsPrefix();
            return false;
        }
    }

    protected function catchArguments(&$token)
    {
        if (0 === $this->bracket) switch ($this->prevType)
        {
            case '(':
            case ',':
            case '&':
                $this->arguments[] = ',';
                $this->arguments[] = $token;
        }
    }

    protected function catchBrackets(&$token)
    {
        if ('(' === $token[0]) ++$this->bracket;
        else if (')' === $token[0] && 0 > --$this->bracket)
        {
            $this->unregister(array('catchBrackets' => array('(', ')')));
            $this->unregister(array('catchArguments' => T_VARIABLE));

            $tag = sprintf('/*fo/%010d*/', mt_rand());
            $this->arguments[0] = '(';
            $u = array('{', array(T_COMMENT, $tag), array(T_RETURN, 'return'), array(T_NS_SEPARATOR, '\\'), array(T_STRING, $this->replacementFunction));
            $u = array_merge($u, $this->arguments, array(')', ';', '}'));
            call_user_func_array(array($this, 'unshiftTokens'), $u);
            $this->arguments = array();

            $this->newOverrides[$this->replacedFunction][$tag] = $this->replacementFunction;
        }
    }
}
