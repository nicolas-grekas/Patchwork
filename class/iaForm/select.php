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


class extends iaForm_hidden
{
	protected $type = 'select';
	protected $item = array();
	protected $firstItem = false;
	protected $length = -1;

	protected function init(&$param)
	{
		if (isset($param['firstItem'])) $this->firstItem = $param['firstItem'];

		if (isset($param['item'])) $this->item =& $param['item'];
		else if (isset($param['sql']))
		{
			$db = DB();
			$this->item = array();
			$result =& $db->query($param['sql']);

			$this->length = 0;

			while ($row =& $result->fetchRow())
			{
				if (isset($param['filter'])) $row = call_user_func_array($param['filter'], array(&$row));
				if ('' !== (string) @$row->G)
				{
					if (isset($this->item[ $row->G ])) $this->item[ $row->G ][ $row->K ] =& $row->V;
					else
					{
						$this->item[ $row->G ] = array($row->K => &$row->V);
						$this->length += 2;
					}
				}
				else $this->item[ $row->K ] =& $row->V;

				$this->length += 1;
			}

			$result->free();
		}

		if (!isset($param['valid']))
		{
			$param['valid'] = 'in_array';
			$param[0] = array();

			$this->length = 0;

			foreach ($this->item as $k => &$v)
			{
				if (is_array($v))
				{
					$param[0] += array_keys($v);
					$this->length += count($v) - 1;
				}
				else $param[0][] = $k;

				$this->length += 1;
			}
		}

		parent::init($param);
	}

	protected function get()
	{
		$a = parent::get();

		if ($this->multiple) $a->multiple = 'multiple';

		if ($this->item || $this->firstItem !== false)
		{
			if ($this->firstItem !== false)
			{
				$a->_firstItem = true;
				$a->_firstCaption = $this->firstItem;
			}

			$a->_option = new loop_iaForm_selectOption__($this->item, $this->value, $this->length);
		}

		unset($a->value);

		return $a;
	}
}

class loop_iaForm_selectOption__ extends loop
{
	protected $item;
	protected $value;
	protected $length;
	protected $group = false;

	function __construct(&$item, &$value, $length)
	{
		$this->item =& $item;
		$this->value = array_flip((array) $value);
		$this->length = $length;
	}

	protected function prepare()
	{
		reset($this->item);

		if ($this->length >= 0) return $this->length;

		$this->length = 0;
		foreach ($this->item as &$v)
		{
			if (is_array($v)) $this->length += count($v) - 1;
			$this->length += 1;
		}

		reset($this->item);

		return $this->length;
	}

	protected function next()
	{
		if (is_array($this->group))
		{
			if (!(list($key, $caption) = each($this->group)))
			{
				$this->group = false;
				return (object) array('_groupOff' => 1);
			}
		}
		else
		{
			if (!(list($key, $caption) = each($this->item))) return false;

			if (is_array($caption))
			{
				reset($caption);
				$this->group =& $caption;
				return (object) array(
					'_groupOn' => 1,
					'label' => $key
				);
			}
		}

		if (is_object($caption))
		{
				$a = $caption;
				$a->value = $key;
		}
		else $a = (object) array(
			'value' => $key,
			'caption' => $caption
		);

		if (isset($this->value[(string) $key]))
		{
			$a->selected = 'selected';
			$a->checked = 'checked';
		}

		return $a;
	}
}
