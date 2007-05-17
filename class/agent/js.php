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
	public $argv = array('__0__', 'source:bool');

	protected $maxage = -1;
	protected $watch = array('public/js');

	protected static $recursion = 0;

	function control()
	{
		$this->argv->source && self::$recursion = 1;
		self::$recursion && $this->argv->source = 1;

		if (DEBUG || $this->argv->source)
		{
			$tpl = $this->argv->__0__;

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
		if (!DEBUG && !$this->argv->source)
		{
			++self::$recursion;
			$source = patchwork_serverside::returnAgent(substr(get_class($this), 6), (array) $this->argv);
			--self::$recursion;

			$parser = new jsqueez;
			$o->DATA = $parser->squeeze($source);
		}
		else
		{
			$o->cookie_path   = $GLOBALS['CONFIG']['session.cookie_path'];
			$o->cookie_domain = $GLOBALS['CONFIG']['session.cookie_domain'];
			$o->maxage = $GLOBALS['CONFIG']['maxage'];
		}

		return $o;
	}
}
