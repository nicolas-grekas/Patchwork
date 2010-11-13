<?php /*********************************************************************
 *
 *   Copyright : (C) 2010 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class patchwork_tokenizer_aliasing extends patchwork_tokenizer
{
	protected

	$functionAlias = array(),
	$classAlias    = array(),
	$callbacks     = array(
		'tagVariableFunction' => '(',
		'tagUseFunction'      => T_USE_FUNCTION,
	),
	$depends = array(
		'patchwork_tokenizer_classInfo',
		'patchwork_tokenizer_stringInfo',
	);


	// List of native functions that could trigger __autoload()

	static $autoloader = array(
		// No callback parameter involved or complex behaviour
		'__autoload'        => 0,
		'class_exists'      => 0,
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


	function __construct(parent $parent, $function_map, $class_map)
	{
		foreach ($class_map as $k => $v)
		{
			$this->classAlias[strtolower($k)] = $v;
		}

		$v = get_defined_functions();

		foreach ($v['user'] as $v)
		{
			if (0 === strncasecmp($v, '__patchwork_', 12))
			{
				$v = strtolower($v);
				$this->functionAlias[substr($v, 12)] = $v;
			}
		}

		if (!$this->functionAlias) $this->callbacks = array();
		if ($this->classAlias) $this->callbacks['tagUseClass'] = T_USE_CLASS;

		$this->initialize($parent);

		foreach ($function_map as $k => $v)
		{
			function_exists('__patchwork_' . $k) && $this->functionAlias[strtolower($k)] = $v;
		}
	}

	function tagVariableFunction(&$token)
	{
		if (   ('}' === $this->prevType || T_VARIABLE === $this->prevType)
			&& !in_array($this->anteType, array(T_NEW, T_OBJECT_OPERATOR, T_DOUBLE_COLON)) )
		{
			$T = PATCHWORK_PATH_TOKEN;
			$t =& $this->tokens;
			$i = count($t) - 1;

			if (T_VARIABLE === $this->prevType && '$' !== $this->anteType)
			{
				if ('this' !== $a = substr($t[$i][1], 1))
				{
					$t[$i][1] = "\${is_string(\${$a})&&function_exists(\$v{$T}='__patchwork_'.\${$a})?'v{$T}':'{$a}'}";
				}
			}
			else
			{
				if ($a = '}' === $this->prevType ? 1 : 0)
				{
					$b = array($i, 0);

					while ($a > 0 && isset($t[--$i]))
					{
						if ('{' === $t[$i][0]) --$a;
						else if ('}' === $t[$i][0]) ++$a;
					}

					$b[1] = $i;
					--$i;

					if ('$' !== $t[$i][0]) return;
				}
				else $b = 0;

				while (isset($t[--$i]) && '$' === $t[$i][0]) ;

				if (in_array($t[$i][0], array(T_NEW, T_OBJECT_OPERATOR, T_DOUBLE_COLON))) return;

				++$i;

				$b && $t[$b[0]][1] = $t[$b[1]][1] = '';

				$t[$i][1] = "\${is_string(\$k{$T}=";
				$t[count($t)-1] .= ")&&function_exists(\$v{$T}='__patchwork_'.\$\$k{$T})?'v{$T}':\$k{$T}}";
			}
		}
	}

	function tagUseFunction(&$token)
	{
		$a = strtolower($token[1]);

		if (isset($this->functionAlias[$a]))
		{
			$a = $this->functionAlias[$a];
			$a = explode('::', $a, 2);

			if (1 === count($a)) $token[1] = $a[0];
			else if (empty($this->class->name) || strcasecmp($a[0], $this->class->nsName))
			{
				$this->code[--$this->position] = array(T_STRING, $a[1]);
				$this->code[--$this->position] = array(T_DOUBLE_COLON, '::');
				$this->code[--$this->position] = array(T_STRING, $a[0]);

				return false;
			}
		}
		else if (isset(self::$autoloader[$a]))
		{
			new patchwork_tokenizer_bracket_callback($this, self::$autoloader[$a]);

			if ('&' === $this->prevType)
			{
				$a = count($this->tokens) - 1;
				$this->tokens[$a][0] = T_WHITESPACE;
				$this->tokens[$a][1] = ' ';
			}
		}
	}

	function tagUseClass(&$token)
	{
		if (isset($this->classAlias[strtolower($token[1])]))
		{
			$token[1] = $this->classAlias[strtolower($token[1])];
		}
	}
}
