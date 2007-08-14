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


class extends agent
{
	const contentType = 'text/javascript';

	public $get = array('__0__', 'source:b');

	protected

	$maxage = -1,
	$watch = array('public/js');


	protected static $recursion = 0;


	function control()
	{
		$this->get->source && self::$recursion = 1;
		self::$recursion && $this->get->source = 1;

		if (DEBUG || $this->get->source)
		{
			$tpl = $this->get->__0__;

			if ($tpl !== '')
			{
				if ('.js' != substr($tpl, -3)) $tpl .= '.js';

				$tpl = str_replace('../', '/', strtr('js/' . $tpl, '\\', '/'));
			}

			$this->template = $tpl;
		}
	}

	function compose($o)
	{
		if (!DEBUG && !$this->get->source)
		{
			++self::$recursion;
			$source = patchwork_serverside::returnAgent(substr(get_class($this), 6), (array) $this->get);
			--self::$recursion;

			$parser = new jsqueez;
			$o->DATA = $parser->squeeze($source);
		}
		else
		{
			$o->cookie_path   = $CONFIG['session.cookie_path'];
			$o->cookie_domain = $CONFIG['session.cookie_domain'];
			$o->maxage = $CONFIG['maxage'];
		}

		return $o;
	}
}
