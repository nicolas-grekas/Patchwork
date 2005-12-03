function QSelectPrint($id, $attribute)
{
	document.write(
		'<div id="_d1' + $id + '" style="position:absolute;visibility:hidden;z-index:9">'
			+'<div id="_d2' + $id + '" style="position:absolute">'
				+'<img src="QSelect/tr.png" width="5" height="10"><br>'
				+'<img src="QSelect/r.png" width="5" height="5" id="_i1' + $id + '"><br>'
				+'<img src="QSelect/br.png" width="5" height="5">'
			+'</div>'
			+'<div id="_d3' + $id + '" style="position:absolute">'
				+'<img src="QSelect/bl.png" width="10" height="5">'
				+'<img src="QSelect/b.png" width="5" height="5" id="_i2' + $id + '">'
			+'</div>'
			+'<select name="_s' + $id + '" size="7"></select>'
		+'</div>'
		+'<span class="QSstyle">'
			+'<input autocomplete="off" ' + $attribute + '>'
			+'<img src="QSelect/b.gif" id="_i3' + $id + '" '
				+'onmouseover="this.src=\'QSelect/bh.gif\'" '
				+'onmouseout="this.src=\'QSelect/b.gif\'" '
				+'onmousedown="this.src=\'QSelect/bp.gif\'" '
				+'onmouseup="this.onmouseover()">'
		+'</span>'
	);
}

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

function $setTimeout($function, $timeout, $i)
{
	if ($function)
	{
		$i = ++$goSelectId;
		goQSelect[$i] = $function;
		return setTimeout('goQSelect['+$i+']();delete goQSelect['+$i+']', $timeout);
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
		if ($this.$listedValue || $this.$input.lock) $this.$value = $this.$input.value = $this.$listedValue;
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
	if (!$e.srcElement && $e.target && 'SELECT'==$e.target.tagName) return;
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
		$keyupid = $this.$lastKeyupid, $i,
		$caretPos = this.selectionStart;

	if (document.selection)
	{
		$caretPos = document.selection.createRange();
		try
		{
			$i = $caretPos.duplicate();
			$i.moveToElementText(this);
		}
		catch ($i)
		{
			$i = this.createTextRange();
		}

		$i.setEndPoint('EndToStart', $caretPos);

		$caretPos = $i.text.length;
	}

	$e = ($e || event).keyCode;

	$selectRange = $e && $e != 8 && $e != 46;

	if ($this.$value == this.value) return;

	$this.$value = this.value;
	$this.$listedValue = '';

	$setTimeout(function()
	{
		if ($this.$lastKeyupid!=$keyupid) return;

		$this.$callback($this.$value, $this.$onkeyup, $caretPos);
	}, 200);
}

function $onkeydown($e)
{

	var $this = $get(this),
		$select = $this.$select;

	++$this.$lastKeyupid;

	if ($select.$focus) return;

	$e = ($e || event).keyCode;

	if (13==$e || 9==$e)
	{
		if ('visible'==$this.$div.style.visibility)
		{
			if ( $select.selectedIndex!=-1 ) $this.$setValue( $select.selectedIndex );
			$this.$hide();
		}

		$this.$value = $this.$input.value = $this.$listedValue;
		if (13==$e) return false;
	}
	else if (27==$e || (8==$e && ''==$this.$value)) $this.$hide();
	else if (38==$e || 57373==$e || 40==$e || 57374==$e || 33==$e || 57371==$e || 34==$e || 57372==$e)
	{
		$this.$show();

		if ('visible'==$this.$div.style.visibility)
		{
			$select.focus();
			if (-1==$select.selectedIndex) $setTimeout(function(){$select.selectedIndex = 0}, 1);
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
		$imgB = $getById('_i3'+$id),
		$divH = $getById('_d2'+$id),
		$divW = $getById('_d3'+$id),

		$options = $select.options,
		$length = 0;

	$this.$callback = $callback;
	$this.$value = $input.value;
	$this.$listedValue = $input.value;
	$this.$div = $div;
	$this.$lastKeyupid = 0;
	$this.$onkeyup = function($result, $listedValue, $selectionStart, $selectionLength, $displayedValue)
	{
		$select.selectedIndex = -1;
		$length = 0;

		var $j = '[^' + ACCENT_ALPHANUM + ']',
			$i = ACCENT.length - 1,
			$query = $input.value;

		$query = $query.replace(new RegExp($j, 'g'), '#');

		do $query = $query.replace(ACCENT_RX[$i], '['+ACCENT[$i]+']');
		while (--$i);

		$query = new RegExp('^' + $query.replace(/#/g, $j + '+'), 'i');

		for ($i in $result)
		{
			$i = $result[$i];
			$options[$length++] = new Option($i, $i);

			if (!$listedValue && ($j = $i.match($query)))
			{
				$listedValue = $i;
				$j = $j[0].length;
				$selectionStart = $input.value;
				$displayedValue = $selectionStart + $i.substr($j);
				$selectionStart = $selectionStart.length;
			}
		}

		while ($options.length > $length) $options[--$options.length] = null;

		if ($listedValue>='') $this.$listedValue = $listedValue;
		if (!($displayedValue>='')) $displayedValue = $listedValue;

		if ($selectRange && $displayedValue)
		{
			$selectionLength = $selectionLength>='' ? $selectionStart + $selectionLength : $displayedValue.length;

			$this.$value = $input.value = $displayedValue;

			if ($input.setSelectionRange) $input.setSelectionRange($selectionStart, $selectionLength);
			else if ($input.createTextRange)
			{
				$i = $input.createTextRange();
				$i.collapse(true);
				$i.moveEnd('character', $selectionLength);
				$i.moveStart('character', $selectionStart);
				$i.select();
			}
			else
			{
				$this.$value = $input.value = $displayedValue.substr(0, $selectionStart);
				if ($this.$value != $this.$listedValue) $this.$listedValue = '';
			}
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
			$divStyle.width = $divH.style.left = $width + 'px';

			$select.size = $length < 7 ? ($length > 2 ? $length : 2) : 7;
			$select.style.width = $width + 'px';

			$divStyle.visibility = 'visible';
			$divStyle.display = '';

			$height = $select.offsetHeight;
			$imgW.width = $width - 10;
			$imgW.style.width = $width - 10 + 'px';
			$imgH.height = $height - 10;
			$imgH.style.height = $height - 10 + 'px';
			$divW.style.top = $height + 'px';
		}
		else $this.$hide();
	}

	$this.$hide = function()
	{
		if (!$autohide) return;

		var $divStyle = $div.style;

		if ('hidden'==$divStyle.visibility) return;

		$select.selectedIndex = -1;
		$divStyle.visibility = 'hidden';
		$divStyle.display = 'none';
	}

	$this.$setValue = function($idx)
	{
		$input.value = $this.$value = $this.$listedValue = $options[$idx].text;
		$input.select();
		$input.focus();
	}

	$imgB.$onmousedown = $imgB.onmousedown;
	$imgB.onmousedown = function()
	{
		this.$QSelectVisible = 'visible'==$div.style.visibility;
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

		$pushBack($result);
	}
}
