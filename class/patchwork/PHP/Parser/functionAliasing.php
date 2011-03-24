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


class patchwork_PHP_Parser_functionAliasing extends patchwork_PHP_Parser
{
    protected

    $alias     = array(),
    $callbacks = array(
        'tagVariableVar' => '(',
        'tagUseFunction' => T_USE_FUNCTION,
    ),
    $dependencies = array('stringInfo', 'classInfo' => array('class', 'namespace', 'nsResolved')),

    $varVarLead = '${patchwork_alias_resolve_ref(',
    $varVarTail = ",\$\x9D)}";


    // List of native functions that could trigger __autoload()

    protected static

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


    function __construct(parent $parent, $function_map)
    {
        $v = get_defined_functions();

        foreach ($v['user'] as $v)
        {
            if (0 === strncasecmp($v, '__patchwork_', 12))
            {
                $v = strtolower($v);
                $this->alias[substr($v, 12)] = $v;
            }
        }

        if (!$this->alias) $this->callbacks = array();

        parent::__construct($parent);

        foreach ($function_map as $k => $v)
        {
            '\\' === $k[0] && $k = substr($k, 1);
            '\\' === $v[0] && $v = substr($v, 1);
            function_exists('__patchwork_' . strtr($k, '\\', '_')) && $this->alias[strtolower($k)] = $v;
        }
    }

    protected function tagVariableVar(&$token)
    {
        if (   ('}' === $this->lastType || T_VARIABLE === $this->lastType)
            && !in_array($this->penuType, array(T_NEW, T_OBJECT_OPERATOR, T_DOUBLE_COLON)) )
        {
            $t =& $this->types;
            end($t);
            $i = key($t);

            if (T_VARIABLE === $this->lastType && '$' !== $this->penuType)
            {
                if ('$this' !== $a = $this->texts[$i])
                {
                    $this->texts[$i] = $this->varVarLead . $a . $this->varVarTail;
                }
            }
            else
            {
                if ('}' === $this->lastType)
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
        // Alias functions only if they are fully namespace resolved

        $a = strtolower($this->nsResolved);

        if ('\\' !== $this->nsResolved[0])
        {
            $e = isset($this->alias[$a]) || isset(self::$autoloader[$a]);
            $e || $a = substr(strtolower($this->namespace) . $a, 1);
            $e = $e || isset($this->alias[$a]) || isset(self::$autoloader[$a]);
            $e && $this->setError("Unresolved namespaced function call ({$this->nsResolved}), skipping aliasing", E_USER_WARNING);
            return;
        }

        $a = substr($a, 1);

        if (isset($this->alias[$a]))
        {
            $a = $this->alias[$a];
            $a = explode('::', $a, 2);

            if (1 === count($a)) $token[1] = $a[0];
            else if (empty($this->class->nsName) || strcasecmp(strtr($a[0], '\\', '_'), strtr($this->class->nsName, '\\', '_')))
            {
                $this->unshiftTokens(
                    array(T_STRING, $a[0]),
                    array(T_DOUBLE_COLON, '::'),
                    array(T_STRING, $a[1])
                );

                $a = $this->namespace && $this->unshiftTokens(array(T_NS_SEPARATOR, '\\'));
            }

            $this->dependencies['stringInfo']->removeNsPrefix();

            if (false === $a) return false;
        }
        else if (isset(self::$autoloader[$a]))
        {
            new patchwork_PHP_Parser_bracket_callback($this, self::$autoloader[$a], $this->alias);

            if ('&' === $this->lastType)
            {
                $a =& $this->types;
                end($a);
                $this->texts[key($a)] = '';
                unset($a[key($a)]);
            }
        }
    }
}
