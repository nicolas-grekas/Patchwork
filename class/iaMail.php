<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class
{
	static function send($headers, $body, $options = null)
	{
		return self::put(array(
			'headers' => &$headers,
			'body' => &$body,
			'options' => &$options,
		));
	}

	static function sendAgent($headers, $agent, $argv = array(), $options = null)
	{
		return self::put(array(
			'headers' => &$headers,
			'agent' => &$agent,
			'argv' => &$argv,
			'options' => &$options,
		));
	}

	protected static function put($data)
	{
#>		$data['headers']['X-Original-To'] = $data['headers']['To'];
#>		$data['headers']['To'] = $GLOBALS['CONFIG']['debug_email'];

		return iaMail_queue::put(
			$data,
			isset($data['options']['delay']) ? $data['options']['delay'] : 0,
			isset($data['options']['archive']) && $data['options']['archive']
		);
	}
}
