if (!window.lF) 
{

function valid($element, $type, $args)
{
	if ($element.disabled) return 1;
	if ($element.value == '') return '';

	$args = [$args];
	for (var i=3; i<arguments.length; i++) $args[$args.length] = arguments[i];
	return window['valid_'+$type] ? window['valid_'+$type]($element.value, $args) : 1;
}

valid_int = function($value, $args)
{
	if ((''+$value).search(/^\s*[+-]?[0-9]+\s*$/) == -1) return false;

	$value -= 0;
	if (t($args[0]) && $value<$args[0]) return false;
	if (t($args[1]) && $value>$args[1]) return false;
	return true;
}

valid_float = function($value, $args)
{
	$value = parseFloat($value);

	if (isNaN($value)) return false;
	if (t($args[0]) && $value<$args[0]) return false;
	if (t($args[1]) && $value>$args[1]) return false;
	return true;
}

valid_string = function($value, $args)
{
	if ($args[0])
	{
		$args[0] = eval($args[0]);
		if ($value.search($args[0])==-1) return false;
	}
	return true;
}

valid_email = function ($value, $args)
{
	if ($value.search(/^\s*$/)==0) return true;
	$value = $value.toLowerCase();
	return valid_string($value, [/^\s*[-a-z0-9_\.\+]+@([-a-z0-9]+(\.[-a-z0-9]+)+)\s*$/i]) ? $value : false;
}

valid_phone = function($value, $args)
{
	$value = $value.replace(/[^+0-9]+/, '').replace(/^00/, '+');
	return -1 != $value.search(/^\+?[0-9]{4,}$/);
}

valid_date = function($value, $args)
{
	var Y = new Date();
	Y = Y.getFullYear();
	
	$value = $value.replace(/^[^0-9]+/, '');
	$value = $value.replace(/[^0-9]+$/, '');
	$value = $value.split(/[^0-9]+/);
	if ($value.length==2) $value[2] = Y;
	else if (1 == $value.length)
	{
		$value = $value[0];
		if (4 == $value.length || 6 == $value.length || 8 == $value.length)
		{
			$value = [
				$value.substr(0, 2),
				$value.substr(2, 2),
				($value.substr(4)-0) || Y
			];
		}
		else $value = '';
	}
	if ($value.length!=3) return '';
	$value[2] -= 0;
	if ($value[2]<100)
	{
		$value[2] += 1900;
		if (Y-$value[2]>50) $value[2] += 100;
	}
	$args = new Date($value[2], $value[1]-1, $value[0]);
	$value[2] = $args.getFullYear();
	$value[1] = $args.getMonth()+1; if ($value[1]<10) $value[1] = '0'+$value[1]; 
	$value[0] = $args.getDate(); if ($value[0]<10) $value[0] = '0'+$value[0];
	return $value.join('-');
}

ACCENT = navigator.userAgent.indexOf('Safari')<0 /* Without this test, the next while line makes Safari <= 2.0 crash */
	? ['AÀÁÂÃÄÅĀĂĄǺ','aàáâãäåāăąǻ','CĆĈÇĊČ','cćĉçċč','DĐĎ','dđď','EÈÉÊËĒĔĘĖĚ','eèéêëēĕęėě','GĜĢĞĠ','gĝģğġ','HĤĦ','hĥħ','IÌÍÎĨÏĪĬĮİ','iìíîĩïīĭįı','JĴ','jĵ','KĶ','kķ','LĹĻŁĿĽ','lĺļłŀľ','NŃÑŅŇ','nńñņň','OÒÓŐÔÕÖØŌŎǾ','oòóőôõöøōŏǿ','RŔŖŘ','rŕŗř','SŚŜŞŠ','sśŝşš','TŢŦŤ','tţŧť','UÙÚŰÛŨÜŮŪŬŲ','uùúűûũüůūŭų','WẀẂŴẄ','wẁẃŵẅ','YỲÝŶŸ','yỳýŷÿ','ZŹŻŽ','zźżž']
	: ['AÀÁÂÃÄÅ','aàáâãäå','CÇ','cç','EÈÉÊË','eèéêë','IÌÍÎĨÏ','iìíîĩï','NÑ','nñ','OÒÓŐÔÕÖ','oòóőôõö','UÙÚÛŨÜ','uùúûũü','YỲÝŶŸ','yỳýŷÿ'];

ACCENT_RX = [];
$i = ACCENT.length;
while ($i--) ACCENT_RX[$i] = new RegExp('['+ACCENT[$i]+']', 'g');

ACCENT_ALPHANUM = '0-9a-zA-Z' + ACCENT.join('');

RegExp.quote = function($str, $accent)
{
	var $i = ACCENT.length - 1;

	$str = $str.replace(/([\.\\\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:])/g, '\\$1');

	if ($accent)
	{
		do $str = $str.replace(ACCENT_RX[$i], '['+ACCENT[$i]+']');
		while (--$i);
	}

	return $str;
}

function stripAccents($str, $case)
{
	var $i = ACCENT.length;

	while ($i--) $str = $str.replace(ACCENT_RX[$i], ACCENT[$i].charAt(0));

	$str = $str.replace(
		/[ÆǼ]/g, 'AE').replace(
		/[æǽ]/g, 'ae').replace(
		/ß/g, 'ss').replace(
		/Œ/g, 'OE').replace(
		/œ/g, 'oe'
	);

	return $case>0 ? $str.toUpperCase() : $case<0 ? $str.toLowerCase() : $str;
}


/*
* Form's control extension
*/

getCheckStatus = IgCS = function()
{
	var $this = this,
		$i = 0,
		$disabledCounter = 0;

	if ($this.disabled || ((''+$this.value).length && $this.checked)) return 1;

	for (; $i<$this.length; ++$i)
	{
		if ($this[$i].disabled) ++$disabledCounter;
		else if ((''+$this[$i].value).length && $this[$i].checked) return 1;
	}

	return ($i && $disabledCounter == $this.length) ? 1 : '';
}

getSelectStatus = IgSS = function()
{
	var $this = this;
	return ($this.disabled
		|| !$this.options.length
		|| ($this.selectedIndex >= 0 && (''+$this.options[$this.selectedIndex].value).length)
	) ? 1 : '';
}

checkElementStatus = IcES = function($msgs, $form)
{
	var $i = 1, $element, $status, $onempty, $onerror;

	while ($i<$msgs.length)
	{
		$element = $form[ ''+$msgs[$i] ];

		$onempty = $msgs[++$i];
		$onerror = $msgs[++$i];
		++$i;

		if (!$element) continue;

		if (!$element.gS)
		{
			switch ($element.type || $element[0].type)
			{
				case 'radio':
				case 'checkbox':
					$element.gS = IgCS;
					break;

				case 'select':
					$element.gS = IgSS;
					break;

				default: $element.gS = function() {return 1;}
			}
		}

		$status = $element.gS();
		if ($status) continue;

		$status = '' + $status;
		$status = $status ? $onerror : $onempty;

		if ($status)
		{
			alert($status);

			$element = $element.type ? $element : $element[0];
			if ($element.type != 'hidden')
			{
				if ($element.focus) $element.focus();
				if ($element.select) $element.select();
			}

			return false;
		}
	}

	return true;
}

labelClick = IlC = function($elt)
{
	$elt = $elt.form[$elt.htmlFor];
	
	if (!$elt.type && $elt[0])
	{
		var $i = 0;
		while ($i+1<$elt.length && $elt[$i].disabled) ++$i;
		$elt = $elt[$i];
	}

	$elt && !$elt.disabled && $elt.focus();
	return false;
}

lastCheckbox = 0;
checkboxClick = IcbC = function($event, $elt)
{
	$event = $event || event;

	if ($elt)
		$elt = $elt.form[$elt.htmlFor],
		$elt = $elt[0] || $elt;

	var $lC = lastCheckbox,
		$this = $elt || this,
		$node = $this.form[ $this.name ],
		$i = 0,
		$trigger = 0,
		$currNode;

	if ($this.readOnly || $this.disabled) return;

	if ($elt)
	{
		$elt.focus();

		if ($elt.type=='checkbox') $elt.click();
		else return $elt.click(), false;
	}


	lastCheckbox = $this;

	if ($event.shiftKey && $node.length && $lC && $lC != $this)
	{
		for (; $i < $node.length; ++$i)
		{
			$currNode = $node[$i];
			if ($trigger && !$currNode.readOnly && !$currNode.disabled) $currNode.checked = $lC.checked;
			if ($currNode==$this || $currNode==$lC) $trigger = $trigger ? 0 : 1;
		}

		setTimeout('lastCheckbox.checked=' + ($lC.checked ? 1 : 0), 0);
	}

	if ($elt) return false;
}

function gLE($name, $multiple)
{
		var $lastElement;

		if ($name)
		{
			if (t(lF[$name])) $lastElement = lF[$name];
			else
			{
				$lastElement = document.getElementsByName($name);
				if ($lastElement.length) $lastElement = $lastElement[$lastElement.length-1];
				lF[$name] = $lastElement;
			}

			if ($multiple) lF[$name.substr(0, $name.length-2)] = $lastElement;
		}

		return $lastElement || false;
}

function FeC($mode)
{
	document.write(
		'<input type="image" border="0" width="1" height="1" src="img/blank.gif" alt="&nbsp;" style="position:absolute" onclick="return '
		+ ($mode == 2 ? 'enterControl(this.form)' : 'false') + '" />'
	);
}

function enterControl($form)
{
	var $i = 0, $elt,
		$all = document.getElementsByTagName('*'),
		$len = $all.length;

	while (++$i < $len && $all[$i] != $form.$lastFocusedElt);
	while (++$i < $len)
	{
		$elt = $all[$i];
		if ($elt.form == $form && !$elt.type.search(/^(submit|image)$/))
		{
			$elt.click();
			break;
		}
	}

	return false;
}

addOnload(function()
{
	var $i = 0, $forms = document.forms, $form, $j, $elt;

	if (_BOARD.lastL == ''+location) t(_BOARD.lastX) && scrollTo(_BOARD.lastX, _BOARD.lastY);
	else setboard('lastL', location);

	for (; $i<$forms.length; ++$i)
	{
		$form = $forms[$i];

		$form.submitIfValid = function($a)
		{
			$a = this.onsubmit();
			if ($a || 'false' != ''+$a) this.submit();
		}
		
		$form.$onsubmit = $form.onsubmit;
		$form.onsubmit = function($event)
		{
			var $this = this;
			if ($this.precheck && !$this.precheck($event)) return false;
			$event = $this.$onsubmit && $this.$onsubmit($event);
			if (!$event && 'false' == ''+$event) return false;

			setboard({
				lastX: window.pageXOffset||document.body.scrollLeft,
				lastY: window.pageYOffset||document.body.scrollTop
			});

			if ($this.UPLOAD_IDENTIFIER && window.loadUpload) loadUpload($this);
		}

		for ($j = 0; $j < $form.length; ++$j)
		{
			$elt = $form[$j];
			if ($elt.type == 'checkbox') $elt.onclick = $elt.onclick || checkboxClick;

			if ($elt.type != 'submit' && $elt.type != 'image')
			{
				$elt.$onfocus = $elt.onfocus;
				$elt.onfocus = function($event)
				{
					var $this = this;
					$this.form.$lastFocusedElt = $this;
					$event = $this.$onfocus && $this.$onfocus($event);
					if (!$event && 'false' == ''+$event) return false;
				}
			}
		}
	}

	$forms = $form = $elt = 0;	
});

}
