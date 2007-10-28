<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends loop
{
	protected $lang, $alt;

	protected function prepare()
	{
		if (PATCHWORK_I18N)
		{
			$this->lang = p::__LANG__();

			if (!isset($this->alt))
			{
				$base = preg_quote($_SERVER['PATCHWORK_BASE'], "'");
				$base = explode('__', $base, 2);
				$base = "'^({$base[0]}).+?({$base[1]})(.*)$'D";

				if (preg_match($base, p::__URI__(), $base))
				{
					unset($base[0]);
					$a = array();

					foreach ($GLOBALS['CONFIG']['i18n.lang_list'] as $k => $v)
					{
						if ('' === $k) continue;

						$v = $base[1] . $v . $base[2] . ($this->lang === $k ? $base[3] : p::translateRequest($base[3], $k));

						$a[] = (object) array(
							'lang' => $k,
							'title' => $k,
							'href'  => $v,
						);
					}

					$this->alt =& $a;
				}
				else
				{
					W('Something is wrong between p::__URI__() and PATCHWORK_BASE');

					$this->alt = array(array());
				}
			}

			return count($this->alt) - 1;
		}
		else return 0;
	}

	protected function next()
	{
		if (list(, $a) = each($this->alt))
		{
			$a->lang === $this->lang && list(, $a) = each($this->alt);
			return $a;
		}
		else reset($this->alt);
	}
}
