function setTabId($tabId)
{
	tabId = $tabId;

	if (window.updateThread)
	{
		clearTimeout(updateThread);

		saveQJsrs.abort();
		updateQJsrs.abort();
		multiSaveQJsrs.abort();

		editTxt.onblur();
	}

	with (window)
		saveQJsrs = new QJsrs(_GET.__ROOT__ + 'QJsrs/save?tabId=' + tabId, true),
		updateQJsrs = new QJsrs(_GET.__ROOT__ + 'QJsrs/update?tabId=' + tabId, true),
		multiSaveQJsrs = new QJsrs(_GET.__ROOT__ + 'QJsrs/multiSave?tabId=' + tabId, true);

	updateGrid();
}

function updateGrid($data)
{
	if ($data)
	{
		var $i, $j, $lockArray = [];

		for ($i in $data.rows)
			setCell($data.rows[$i].R, $data.rows[$i].C, $data.rows[$i].D);

		for ($i in $data.locked)
		{
			$i = 'r'+$data.locked[$i].R+'c'+$data.locked[$i].C;
			$j = document.getElementById($i);
			if ($j)
			{
				$lockArray[$i] = $i;
				if (t(lockArray[$i])) delete lockArray[$i];
				else $j.style.backgroundColor = '#FFD3D3';
			}
		}

		for ($i in lockArray)
		{
			$i = lockArray[$i];
			$j = document.getElementById($i);
			if ($j) $j.style.backgroundColor = 'white';
		}

		lockArray = $lockArray;

		version = $data.version;
		if (window.updateThread) clearTimeout(updateThread);
		updateThread = setTimeout('updateGrid()', updatePeriod);
	}
	else updateQJsrs.pushCall({L:version},updateGrid);
}

function getXY($elt)
{
	var $X = $elt.offsetLeft,
		$Y = $elt.offsetTop,
		$parent = $elt.offsetParent;

	while ($parent)
	{
		$X += $parent.offsetLeft;
		$Y += $parent.offsetTop;
		$parent = $parent.offsetParent;
	}

	return [$X, $Y];
}

function editMe($cell)
{
	updateQJsrs.abort();
	clearTimeout(updateThread);
	updatePeriod = 1000;

	var $coo = $cell.id.substr(1).split('c');

	if (!window.lockFrame) return;

	window.editedCell = [$cell, $coo[0]/1, $coo[1]/1];

	lockFrame.location.replace(_GET.__ROOT__ + 'lock?tabId='+ tabId +'&R='+ $coo[0] +'&C='+ $coo[1]);
}

function releaseEdit($noLock)
{
	if ($noLock) alert("Cette cellule est en cours d'utilisation"), updateGrid();
	else lockFrame.location.replace(_GET.__ROOT__ + 'img/blank.gif');
}

function multiReleaseEdit($result)
{
	if (!$result.completed) alert("Une des cellules à modifier est en cours d'utilisation");
}

function openEdit($lock, $oldValue)
{
	var $cell = editedCell[0],
		$row = editedCell[1],
		$col = editedCell[2],
		$X = getXY($cell),
		$Y = $X[1];

	$X = $X[0];
	$X = Math.min($X, (window.innerWidth || document.body.offsetWidth) - editDiv.offsetWidth - 25);
	$X = Math.max(0, $X);

	editDiv.style.left = $X + 'px';
	editDiv.style.top = $Y + 'px';
	editDiv.style.visibility = 'visible';

	editTxt.value = $oldValue;
	editTxt.select();

	editTxt.onkeydown = function($e)
	{
		$e = ($e || event);

		switch ($e.keyCode)
		{
			case 27:
				this.value = $oldValue, this.onblur();
				break;

			case 9:
			case 13:
				if (this.value.indexOf('\t')>=0 || this.value.indexOf('\n')>=0)
				{
					this.onblur();
					return false;
				}

				$X = $row;
				$Y = $col;

				if ($e.shiftKey && !$e.ctrlKey && !$e.altKey)
				{
					if (9 == $e.keyCode)
					{
						if ($Y == 0) return false;
						--$Y;
					}
					else
					{
						if ($X == 0) return false;
						--$X;
					}
				}
				else 9 == $e.keyCode ? ++$Y : ++$X;

				$e = document.getElementById('r'+$X+'c'+$Y);
				if (!$e)
				{
					setCell($X, $Y, '');
					$e = document.getElementById('r'+$X+'c'+$Y);
				}

				this.onblur();

				editedCell = $e;
				setTimeout('editMe(editedCell)', 0);

				return false;
		}
	}

	editTxt.onkeyup = function($e)
	{
		$e = $e || event;

		if (($e.keyCode == 86 || $e.keyCode == 118) && $e.ctrlKey && !$e.shiftKey && !$e.altKey && (this.value.indexOf('\t')>=0 || this.value.indexOf('\n')>=0)) this.blur();
	}

	editTxt.onpaste = function()
	{
		if (this.value.indexOf('\t')>=0 || this.value.indexOf('\n')>=0) this.blur();
	}

	editTxt.onblur = function()
	{
		var $i, $j, $value = this.value;

		$value = $value.replace(
			/\r\n/g, '\n').replace(
			/\r/g  , '\n'
		);

		if ($oldValue != $value)
		{
			if ($value.indexOf('\t')>=0 || $value.indexOf('\n')>=0)
			{
				$value = $value.replace(/\t/g, ' \t ').replace(/\n/g, ' \n ');

				$value = $value.split('\n');
				if ($value[$value.length-1] == ' ') --$value.length;

				$X = 0;
				$Y = $value.length;

				for ($i=0; $i<$Y; ++$i)
				{
					$value[$i] = $value[$i].split('\t');
					if ($value[$i].length > $X) $X = $value[$i].length;

					for ($j=0; $j<$value[$i].length; ++$j) $value[$i][$j] = $value[$i][$j].replace(/^\s+/, '').replace(/\s+$/, '');
				}

				for ($i=0; $i<$Y; ++$i)
				{
					for ($j=0; $j<$X; ++$j)
					{
						$lock += '\t' + ($row + $i) + '\t' + ($col + $j) + '\t' + ($value[$i][$j] || '');
					}
				}

				multiSaveQJsrs.pushCall({'D':$lock}, multiReleaseEdit);
			}
			else
			{
				$value = $value.replace(/^\s+/, '').replace(/\s+$/, '');
				saveQJsrs.pushCall({L:$lock, R:$row, C:$col,D:$value});

				setCell($row, $col, $value);
			}

			$oldValue = $value;
		}


		editDiv.style.visibility = 'hidden';
		this.blur();
		releaseEdit();
		updateGrid();
	}

	setTimeout('editTxt.focus()', 50);
}

function setCell($row, $col, $data)
{
	var $i, $j, $a, $b;

	if ($row>=rowEnd)
	{
		$i = rowEnd;

		while ($i++ <= $row)
		{
			$a = document.createElement('TR');
			$a.id = 'r' + $i;

			for ($j = 0; $j <= colEnd; ++$j)
			{
				$b = document.createElement('TD');
				$b.id = 'r' + $i + 'c' + $j;
				$b.ondblclick = function() {editMe(this)};

				$a.appendChild($b);
			}

			dataGrid.appendChild($a);
		}

		rowEnd = $i - 1;
	}

	if ($col>=colEnd)
	{
		for ($i = 0; $i <= rowEnd; ++$i)
		{
			$a = document.getElementById('r'+$i);

			$j = colEnd;
			while ($j++ <= $col)
			{
				$b = document.createElement('TD');
				$b.id = 'r' + $i + 'c' + $j;
				$b.ondblclick = function() {editMe(this)};

				$a.appendChild($b);
			}
		}

		colEnd = $j - 1;
	}

	document.getElementById('r'+$row+'c'+$col).innerHTML = $data;
}
