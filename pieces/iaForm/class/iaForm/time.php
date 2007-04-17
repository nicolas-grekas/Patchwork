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


/* interface is out of date
class extends iaForm_text
{
	protected $maxlength = 2;
	protected $maxint = 23;
	protected $minute;

	protected function init(&$param)
	{
		$param['valid'] = 'int';
		$param[0] = 0; $param[1] = 23;
		parent::init($param);

		$this->minute = $form->add('minute', $name.'_minute', array('valid'=>'int', 0, 59));
	}

	function getValue()
	{
		return $this->status ? 60*(60*$this->value + ($this->minute->status ? $this->minute->value : 0)) : 0;
	}
}
*/
