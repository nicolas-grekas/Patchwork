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
	goQSelect[++$goSelectId] = $function;
	return setTimeout('goQSelect['+$goSelectId+']();delete goQSelect['+$goSelectId+']', $timeout);
}

function $onfocus()
{
	this.$focus = 1;
	this.form.$QSelectId = this.$QSelectId;
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
	var $this = $get(this);

	if ($this.$lastFocused)
	{
		$this.$hide();
		$this.$lastFocused.focus();
	}

	this.precheck = 0;

	return false;
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

		var $query = new RegExp('^' + RegExp.quote($input.value), 'gi'),
			$firstMatch = '',
			$start = $input.value.length,
			$end, $range, $i;
		
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

	$this.$autoReset = false;

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
			$divStyle.width = $divH.style.left = $width + 'px';

			$imgW.width = $width - 10;

			$select.size = $length < 7 ? ($length > 2 ? $length : 2) : 7;
			$select.style.width = $width + 'px';

			$divStyle.visibility = 'visible';
			$divStyle.display = '';

			$imgH.height = $select.offsetHeight - 10;
			$divW.style.top = $select.offsetHeight + 'px';

			$form.precheck = $precheck;
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

		$form.precheck = 0;
	}

	$this.$setValue = function($idx)
	{
		$input.value = $this.$value = $options[$idx].text;
		$input.select();
		$input.focus();
	}
}
})();

function QSelectSearch($data)
{
	this.search = function($query, $pushResult)
	{
		var $i = ACCENT.length - 1,
			$qLen = $query.length;

		$query = RegExp.quote($query);

		do $query = $query.replace(ACCENT_RX[$i], '['+ACCENT[$i]+']');
		while (--$i);

		$query = new RegExp(($qLen>1 ? '(^|[^0-9a-z'+ACCENT.join('')+'])' : '^') + $query, 'gi');

		for ($i = 1; $i < $data.length; ++$i) if ($data[$i].search($query)>=0) $pushResult($data[$i]);
	}
}
