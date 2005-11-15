<!--*

This template displays a QSelect control.
It has the same parameters as input.tpl

*--><!--

SET a$id -->{a$name}<!-- END:SET --><!--
SET a$class -->{a$class|default:a$type}<!-- END:SET --><!--

IF !a$title --><!--
	SET a$title
		-->{a$_caption_}<!--
	END:SET --><!--
END:IF --><!--


SET $CAPTION --><!--
	IF a$_caption_
		--><label for="{a$id}" class="{a$class}" onclick="return IlC(this)"><!--
		IF a$_mandatory --><span class="mandatory"><!-- END:IF 
		-->{a$_caption_}<!--
		IF a$_mandatory --></span><!-- END:IF
		--></label><!--
	END:IF --><!--
END:SET --><!--


SET $INPUT --><!--

	IF a$_mandatory --><span class="mandatory"><!-- END:IF --><!--	
	IF !a$class --><!-- SET a$class -->QSelect<!-- END:SET --><!-- END:IF --><!--

	SET $id -->{a$name}<!-- END:SET

	--><script src="js/QSelect"></script><div id="_d1{$id}" style="position:absolute;visibility:hidden;z-index:9"

		><div id="_d2{$id}" style="position:absolute"
			><img src="QSelect/tr.png" width="5" height="10"><br
			><img src="QSelect/r.png"  width="5" height="5" id="_i1{$id}"><br
			><img src="QSelect/br.png" width="5" height="5"
		></div

		><div id="_d3{$id}" style="position:absolute"
			><img src="QSelect/bl.png" width="10" height="5"
			><img src="QSelect/b.png"  width="5"  height="5" id="_i2{$id}"
		></div

		><select name="_s{$id}" size="7"></select

	></div

	><link rel="stylesheet" type="text/css" href="QSelect/style.css"
	><span class="QSstyle"
		><input autocomplete="off" {a$|htmlArgs}
		><img src="QSelect/b.gif" id="_i3{$id}" onmouseover="this.src='QSelect/bh.gif'" onmouseout="this.src='QSelect/b.gif'" onmousedown="this.src='QSelect/bp.gif'" onmouseup="this.onmouseover()"
	></span

	><script><!--

	lE=gLE({a$name|escape:'js'});
	lE.lock={a$_lock_|escape:'js'};

	lE.gS=function(){return valid(this<!-- LOOP a$_valid -->,{$VALUE|escape:'js'}<!-- END:LOOP -->)};

	lE.cS=function(){return IcES([0<!-- LOOP a$_elements -->,{$name|escape:'js'},{$onempty|escape:'js'},{$onerror|escape:'js'}<!-- END:LOOP -->],this.form)};//--></script><script src="{a$_src_}"></script><!--

	IF a$_mandatory --></span><!-- END:IF --><!--

END:SET --><!--


SET $ERROR --><!--
	IF a$_errormsg -->{a$_beforeError_|default:g$inputBeforeError}<span class="errormsg">{a$_errormsg}</span>{a$_afterError_|default:g$inputAfterError}<!-- END:IF --><!--
END:SET


-->{a$_format_|default:g$inputFormat|echo:$CAPTION:$INPUT:$ERROR}
