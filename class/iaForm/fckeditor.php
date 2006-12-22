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


class extends iaForm_textarea
{
	protected $basePath = '/FCKeditor/';
	protected $toolbarSet;
	protected $config;

	protected function init(&$param)
	{
		if (isset($this->form->rawValues[$this->name]))
		{
			$value =& $this->form->rawValues[$this->name];

			if (false !== strpos($value, "\r")) $value = str_replace(array("\r\n", "\r"), array("\n"  , "\n"), $value);

			$value = preg_replace("'(?<!>)\n'", "<br />\n", $value);

			require_once 'HTML/Safe.php';

			$parser = new HTML_Safe;
			$value = $parser->parse($value);
		}

		parent::init($param);

		if (isset($param['toolbarSet'])) $this->toolbarSet = $param['toolbarSet'];
		if (isset($param['config'])) $this->config = $param['config'];
	}

	protected function get()
	{
		$a = parent::get();

		$this->agent = 'form/fckeditor';

		$a->_basePath = $this->basePath;

		if (isset($this->toolbarSet)) $a->_toolbarSet = $this->toolbarSet;
		if (isset($this->config)) $a->_config = $this->config;

		return $a;
	}
}
