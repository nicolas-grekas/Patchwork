QSelect = window.QSelect || (function()
{
goQSelect = [];
var $QSelect = {},
	$goSelectId = 0,
	$selectRange;

function $get($elt)
{
	return $QSelect[$elt.$QSelectId];
}

function $setTimeout($function, $timeout)
{
	if ($function)
	{
		goQSelect[++$goSelectId] = $function;
		return setTimeout('goQSelect['+$goSelectId+']();delete goQSelect['+$goSelectId+']', $timeout);
	}
}

function $onfocus()
{
	this.$focus = 1;
	this.form.$QSelectId = this.$QSelectId;
	this.form.precheck = $precheck;
	$get(this).$lastFocused = this;
}

function $onblur()
{
	this.$focus = 0;

	var $this = $get(this);

	$this.$lastFocused = 0;

	$setTimeout(function()
	{
		if (!$this.$lastFocused) $this.$hide();
	}, 1);
}

function $onchange()
{
	var $input = $get(this).$input;
	$input.select();
	$input.focus();
}

function $onchange2()
{
	$get(this).$setValue(this.selectedIndex)
}

function $onmouseup($e)
{
	var $select = this,
		$this = $get($select),

	$e = $e || event;
	if (!$e.srcElement && $e.target && $e.target.tagName=='SELECT') return;
	$setTimeout(function()
	{
		if ($select.selectedIndex!=-1)
		{
			$this.$setValue($select.selectedIndex);
			$this.$hide();
		}
	}, 1);
}

function $onkeyup($e)
{
	var $this = $get(this),
		$keyupid;

	$e = ($e || event).keyCode;

	$selectRange = $e && $e != 8 && $e != 46;

	if ($this.$value == this.value) return;

	$this.$value = this.value;

	$keyupid = ++$this.$lastKeyupid;

	$setTimeout(function()
	{
		if ($this.$lastKeyupid!=$keyupid) return;

		$this.$callback($this.$value, $this.$onkeyup);
	}, 200);
}

function $onkeydown($e)
{
	var $this = $get(this),
		$select = $this.$select;

	if ($select.$focus) return;

	$e = ($e || event).keyCode;

	if ($e==13 || $e==9)
	{
		if ($this.$div.style.visibility=='visible')
		{
			if ( $select.selectedIndex!=-1 ) $this.$setValue( $select.selectedIndex );
			if ($e==9) $this.$hide();
		}
	}
	else if ($e==27 || ($e==8 && $this.$value=='')) $this.$hide();
	else if ($e==38 || $e==57373 || $e==40 || $e==57374 || $e==33 || $e==57371 || $e==34 || $e==57372)
	{
		$this.$show();

		if ($this.$div.style.visibility=='visible')
		{
			$select.focus();
			if ($select.selectedIndex==-1) $setTimeout(function(){$select.selectedIndex = 0}, 1);
		}
	}
}

function $precheck()
{
	var $this = $get(this),
		$input = $this.$input;

/*
	var $this = $get(this),
		$input = $this.$input,
		$firstOption = $this.$select,
		$selectedText = $firstOption[$firstOption.selectedIndex];

	$firstOption = $firstOption[0];

//XXX : lancer une recherche sur la valeur du champ !
	if ($input.lock && $input.value != '' && !(($firstOption && $input.value == $firstOption.text) || ($selectedText && $input.value == $selectedText.text)))
	{
		$input.select();
		$input.focus();
		return false;
	}
*/

	if (!$this.$lastFocused && $this.$div.style.visibility=='visible')
	{
		$this.$hide();
		$input.focus();
		return false;
	}

	return true;
}

return function($input, $callback, $autohide)
{
	var $this = {},
		$form = $input.form,
		$getById = function($id)
		{
			return document.getElementById ? document.getElementById($id) : document.all[$id];
		},
		$id = $input.name,
		$select = $form['_s'+$id],
		$div = $getById('_d1'+$id),
		$imgH = $getById('_i1'+$id),
		$imgW = $getById('_i2'+$id),
		$imgB = $getById('_i3'+$id),
		$divH = $getById('_d2'+$id),
		$divW = $getById('_d3'+$id),

		$k,

		$options = $select.options,
		$length = 0;

	$this.$callback = $callback;
	$this.$value = $input.value;
	$this.$div = $div;
	$this.$lastKeyupid = 0;
	$this.$onkeyup = function($result)
	{
		$select.selectedIndex = -1;
		$length = 0;

		var $query = new RegExp('^' + RegExp.quote($input.value, $input.lock), 'gi'),
			$firstMatch = '',
			$start = $input.value.length,
			$end, $range, $i;

		if ($input.value != '' && $input.lock && !$result.length) return;

		for ($i in $result)
		{
			$i = $result[$i];
			$options[$length++] = new Option($i, $i);
			if (!$firstMatch && $i.search($query)>=0) $firstMatch = $i;
		}

		$options.length = $length;

		if ($selectRange && $length && $firstMatch)
		{
			$end = $firstMatch.length,
			$this.$value = $input.value = $firstMatch;

			if ($input.setSelectionRange) $input.setSelectionRange($start, $end);
			else if ($input.createTextRange)
			{
				$range = $input.createTextRange();
				$range.collapse(true);
				$range.moveEnd('character', $end);
				$range.moveStart('character', $start);
				$range.select();
			}
			else $this.$value = $input.value = $firstMatch.substr(0, $start);
		}

		$this.$show();
	}

	$this.$select = $select;
	$this.$input = $input;
	
	$QSelect[$id] = $this;

	$select.$QSelectId = $input.$QSelectId = $id;
	$select.$isSelect = 1;

	$select.onfocus = $input.onfocus = $onfocus;
	$select.onblur = $input.onblur = $onblur;
	$select.onchange = $autohide ? $onchange : $onchange2;
	$select.onmouseup = $onmouseup;
	$input.onkeyup = $onkeyup;
	$input.onkeydown = $onkeydown;
	$input.onkeypress = $onkeydown;

	$this.$show = function()
	{
		if (!$autohide) return;

		if ($length>0)
		{
			var $left = $input.offsetLeft,
				$top = $input.offsetTop,
				$width = $input.offsetWidth,
				$height = $input.offsetHeight - 1,
				$parent = $input.offsetParent,
				$divStyle = $div.style;

			while ($parent)
			{
				$left += $parent.offsetLeft;
				$top += $parent.offsetTop;
				$parent = $parent.offsetParent;
			}

			$divStyle.left = $left + 'px';
			$divStyle.top = ($top+$height) + 'px';

			$select.size = $length < 7 ? ($length > 2 ? $length : 2) : 7;
			$select.style.width = 'auto';

			$divStyle.visibility = 'visible';
			$divStyle.display = '';

			$setTimeout(function()
			{
				$imgW.width = $width = Math.max($width, $select.offsetWidth) - 10;
				$imgH.height = $height = $select.offsetHeight - 10;

				$imgW.style.width = $width + 'px';
				$imgH.style.height = $height + 'px';

				$select.style.width = $divH.style.left = $divStyle.width= $width + 10 + 'px';
				$divW.style.top = $height + 10 + 'px';

			}, 0);
		}
		else $this.$hide();
	}

	$this.$hide = function()
	{
		if (!$autohide) return;

		var $divStyle = $div.style;

		if ($divStyle.visibility == 'hidden') return;

		$select.selectedIndex = -1;
		$divStyle.visibility = 'hidden';
		$divStyle.display = 'none';
	}

	$this.$setValue = function($idx)
	{
		$input.value = $this.$value = $options[$idx].text;
		$input.select();
		$input.focus();
	}

	$imgB.$onmousedown = $imgB.onmousedown;
	$imgB.onmousedown = function()
	{
		this.$QSelectVisible = $div.style.visibility == 'visible';
		this.$onmousedown();
	}
	
	$imgB.onclick = function()
	{
		$input.select();
		$input.focus();

		this.$QSelectVisible ? $this.$hide() : ($input.value ? $this.$show() : $this.$callback('*', $this.$onkeyup));
	}

	$imgB = 0;
}
})();

function QSelectSearch($data)
{
	$data.length--;

	return function($query, $pushBack)
	{
		if ('*' == $query) return $pushBack($data);

		var $result = [],
			$i = 0,
			$qLen = $query.length;

		if ($query)
		{
			$query = RegExp.quote($query, 1);
			$query = new RegExp(($qLen>1 ? '(^|[^0-9a-z'+ACCENT.join('')+'])' : '^') + $query, 'gi');

			for (; $i < $data.length; ++$i) if ($data[$i].search($query)>=0) $result[$result.length] = $data[$i];
		}

		return $pushBack($result);
	}
}
