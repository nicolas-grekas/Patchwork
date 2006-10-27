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


class extends iaForm_text
{
	protected $maxlength = 2;
	protected $maxint = 59;

	protected function get()
	{
		$a = parent::get();
		$a->onchange = "this.value=this.value/1||'';if(this.value<0||this.value>{$this->maxint})this.value=''";
		return $a;
	}
}
