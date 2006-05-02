if (!self.QSelect)
{

$getById = document.getElementById ? function($id) {return document.getElementById($id)} : function($id) {return document.all[$id]};

function $onfocus($this)
{
	this.$focus = 1;

	$this = QSelect.$get(this);
	$this.$select.$QSelectId = $this.$form.$QSelectId = this.$QSelectId;
	$this.$select.onchange = $this.$onchange;
	$this.$lastFocused = this;
}

function $onblur($this)
{
	this.$focus = 0;

	$this = QSelect.$get(this);
	$this.$lastFocused = 0;
	$this.$select.onchange = null;

	QSelect.$setTimeout(function()
	{
		if (!$this.$lastFocused) $this.$hide();
		$this.sync($this.$listedValue, 1);
	}, 1);
}

function $onmouseup($e)
{
	var $select = this,
		$this = QSelect.$get($select);

	$e = $e || event;
	if (!$e.srcElement && $e.target && 'SELECT'==$e.target.tagName) return;
	QSelect.$setTimeout(function()
	{
		if ($this.$setValue()) $this.$hide();
	}, 1);
}

function $onkeyup($e)
{
	var $this = QSelect.$get(this),
		$keyupid = $this.$lastKeyupid, $i,
		$caretPos = getCaret(this);

	$e = ($e || event).keyCode;

	$selectRange = $e && $e != 8 && $e != 46;

	if ($this.$value == this.value) return;

	$this.$value = this.value;
	$this.$listedValue = '';

	QSelect.$setTimeout(function()
	{
		if ($this.$lastKeyupid!=$keyupid) return;

		$this.$search($this.$value, $this.$onkeyup, $caretPos);
	}, 200);
}

function $onkeydown($e)
{

	var $this = QSelect.$get(this),
		$select = $this.$select;

	++$this.$lastKeyupid;

	if ($select.$focus) return;

	$e = ($e || event).keyCode;

	if (13==$e || 9==$e)
	{
		$this.sync($this.$listedValue, 1);

		if ('visible'==$this.$div.style.visibility)
		{
			$this.$setValue();
			$this.$hide();
			if (13==$e || $this.$fixTab) return false;
		}
	}
	else if (27==$e || (8==$e && ''==$this.$value)) $this.$hide();
	else if (38==$e || 57373==$e || 40==$e || 57374==$e || 33==$e || 57371==$e || 34==$e || 57372==$e)
	{
		$this.$show();

		if ('visible'==$this.$div.style.visibility)
		{
			$select.focus();
			if (-1==$select.selectedIndex) QSelect.$setTimeout(function(){$select.selectedIndex = 0}, 1);
		}
	}
}

function $precheck()
{
	var $this = QSelect.$get(this);

	if ($this.$lastFocused)
	{
		$this.$hide();
		$this.$lastFocused.focus();
	}

	this.precheck = 0;

	return false;
}

QSelect = (function()
{

var $selectRange,

	$win = self,
	$getById = $win.$getById,
	$onfocus = $win.$onfocus,
	$onblur = $win.$onblur,
	$onmouseup = $win.$onmouseup,
	$onkeyup = $win.$onkeyup,
	$onkeydown = $win.$onkeydown,
	$precheck = $win.$precheck,

	$select = $getById('_QSs'),
	$options = $select.options,
	$div  = $getById('_QSd1'),
	$imgH = $getById('_QSi1'),
	$imgW = $getById('_QSi2'),
	$divH = $getById('_QSd2'),
	$divW = $getById('_QSd3');

$select.onfocus = $onfocus;
$select.onblur = $onblur;
$select.onmouseup = $onmouseup;


return function($input, $driver)
{
	var $this = {},
		$form = $input.form,
		$id = $input.name,
		$imgB = $getById($input.tag + '_QSb' + $id) || {},

		$length = 0,

		$driver = $driver($this, $input, $select, $options);

	QSelect.$QSelect[$id] = $this;

	$this.$search = $driver.search;
	$this.$setValue = $driver.setValue;
	$this.$fixTab = $driver.fixTab;
	$this.$value = $input.value;
	$this.$listedValue = $input.value;
	$this.$div = $div;
	$this.$lastKeyupid = 0;

	$this.$select = $select;
	$this.$form = $form;
	$this.$onchange = $driver.onchange;

	$input.$QSelectId = $id;
	$input.onfocus = $onfocus;
	$input.onblur  = $onblur;
	$input.onkeyup = $onkeyup;
	$input.onkeydown = $onkeydown;
	$input.onkeypress = $onkeydown;

	$this.$onkeyup = function($result, $listedValue, $selectionStart, $selectionLength, $displayedValue)
	{
		$select.selectedIndex = -1;
		$length = 0;

		var $j = '[^' + ACCENT_ALPHANUM + ']+',
			$i = ACCENT.length - 1,
			$query = $input.value;

		$query = $query.replace(new RegExp($j, 'g'), '#');

		if ('#' == $query || '' == $query) $query = /^$/g;
		else
		{
			do $query = $query.replace(ACCENT_RX[$i], '['+ACCENT[$i]+']');
			while (--$i);

			$query = new RegExp('^' + $query.replace(/#/g, $j), 'i');
		}

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

		if (t($listedValue)) $this.$listedValue = $listedValue;
		if (!t($displayedValue)) $displayedValue = $listedValue;

		if ($selectRange && $displayedValue)
		{
			$selectionLength = t($selectionLength) ? $selectionStart + $selectionLength : $displayedValue.length;

			$this.$value = $input.value = $displayedValue;

			if (!setSel($input, $selectionStart, $selectionLength))
			{
				$this.$value = $input.value = $displayedValue.substr(0, $selectionStart);
				if ($this.$value != $this.$listedValue) $this.$listedValue = '';
			}
		}

		$this.$show();
	}

	$this.$show = function()
	{
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

			$divStyle.display = '';

			$height = $select.offsetHeight;
			$imgW.width = $width - 10;
			$imgW.style.width = $width - 10 + 'px';
			$imgH.height = $height - 10;
			$imgH.style.height = $height - 10 + 'px';
			$divW.style.top = $height + 'px';

			$form.precheck = $precheck;

			$parent = document.body.scrollTop;
			$height = $parent + self.innerHeight - $height - $top;

			if ($height < $select.offsetHeight && $height < $top - $parent) $divStyle.top = ($top - $select.offsetHeight) + 'px';

			$divStyle.visibility = 'visible';
		}
		else $this.$hide();
	}

	$this.$hide = function()
	{
		var $divStyle = $div.style;

		if ('hidden'==$divStyle.visibility) return;

		$select.selectedIndex = -1;
		$divStyle.visibility = 'hidden';
		$divStyle.display = 'none';

		$form.precheck = 0;
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

		this.$QSelectVisible ? $this.$hide() : $this.$search('*', $this.$onkeyup);
	}

	$imgB = 0;

	$this.sync = function($value, $onlock)
	{
		if (!$onlock || ($this.$listedValue || $input.lock))
			$input.value = $this.$value = $this.$listedValue = $value;
	}
}
})();

QSelect.$QSelect = {};
QSelect.$get = function($elt) {return QSelect.$QSelect[$elt.$QSelectId]}

QSelect.$setTimeoutId = 0;
QSelect.$setTimeoutPool = [];
QSelect.$setTimeout = function($function, $timeout, $i)
{
	if ($function)
	{
		$i = ++QSelect.$setTimeoutId;
		QSelect.$setTimeoutPool[$i] = $function;
		return setTimeout('QSelect.$setTimeoutPool['+$i+']();QSelect.$setTimeoutPool['+$i+']=null', $timeout);
	}
}

function QSelectSearch($data)
{
	if ($data && 0 == $data[$data.length-1] - 0) $data.length--;

	return function($this, $input, $select, $options)
	{
		return {
			fixTab: 0,

			search: function($query, $pushBack)
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
			},

			onchange: function() {$input.select(); $input.focus()},

			setValue: function()
			{
				var $idx = $select.selectedIndex;

				if ($idx>=0)
				{
					$this.sync($options[$idx].text);
					$input.select();
					$input.focus();

					return 1;
				}

				return 0;
			}
		};
	}
}

}
