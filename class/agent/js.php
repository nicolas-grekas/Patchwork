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


class extends agent_bin
{
	public $argv = array('__0__', 'source:bool');

	protected $maxage = -1;

	protected $watch = array('public/js');

	function control()
	{
		CIA::header('Content-Type: text/javascript; charset=UTF-8');

		if (DEBUG || $this->argv->source)
		{
			$tpl = $this->argv->__0__;

			if ($tpl !== '')
			{
				if ('.js' != strtolower(substr($tpl, -3))) $tpl .= '.js';

				$tpl = str_replace(
					array('\\', '../'),
					array('/' , '/'),
					'js/' . $tpl
				);
			}

			$this->template = $tpl;
		}
	}

	function compose($o)
	{
		if (!DEBUG && !$this->argv->source)
		{
			$source = (array) $this->argv;
			$source['source'] = 1;

			$source = CIA_serverside::returnAgent(substr(get_class($this), 6), $source);

			$parser = new jsquiz;
			$parser->addJs($source);
			echo $parser->get();
		}

		return $o;
	}
}
