<?php /*********************************************************************
 *
 *   Copyright : (C) 2010 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This library is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Lesser General Public
 *   License as published by the Free Software Foundation; either
 *   version 3 of the License, or (at your option) any later version.
 *
 ***************************************************************************/


class patchwork_alias_stream
{
	static function context_create($opt = array(), $params = INF)
	{
		$opt = stream_context_create($opt);
		INF !== $params && self::context_set_params($opt, $params);
		return $opt;
	}

	static function context_set_params($context, $params)
	{
		isset($params['notification'])
			&& is_string($params['notification'])
			&& function_exists('__patchwork_' . $params['notification'])
			&& $params['notification'] = '__patchwork_' . $params['notification'];

		return stream_context_set_params($context, $params);
	}
}
