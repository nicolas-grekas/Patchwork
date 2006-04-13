<!-- AGENT 'header' title = 'iaGrid' css = 'iaGrid.css' -->

<a href="javascript:;" class="docLabel">{$label}</a> :
<!-- LOOP $tab -->
<a href="javascript:;" class="tabSelected">{$label}</a> |
<!-- END:LOOP -->
<a href="javascript:;" class="docLabel">*</a>

<table border=0 cellspacing=1 cellpadding=2>
<tbody id="HdataGrid">
<tr id="r0"><td id="r0c0" ondblclick="editMe(this)"></td></tr>
</tbody>
</table>

<form accept-charset="UTF-8" onsubmit="return false">
<div id="HeditDiv"><textarea name="HeditTxt" cols="40" rows="1"></textarea></div>
</form>

<iframe name="HlockFrame" src="{/}img/blank.gif" width=0 height=0 frameborder=0></iframe>

<script type="text/javascript" src="{/}js/QJsrs"></script>
<script type="text/javascript" src="{/}js/iaGrid"></script>
<script type="text/javascript">/*<![CDATA[*/

version = 0;
dataGrid = document.getElementById('HdataGrid');
editDiv = document.getElementById('HeditDiv');
editTxt = document.forms[0][0];
lockFrame = frames.HlockFrame;

updatePeriod = 1000;
lockArray = [];
rowEnd = 0;
colEnd = 0;

setTabId({$tabId|js});

window.onblur = function()
{
	updatePeriod = 10000;
}

onfocus = function()
{
	updatePeriod = 1000;
}

/*]]>*/</script>

<!-- AGENT 'footer' -->
