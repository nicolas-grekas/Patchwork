{*/**************************************************************************
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
 **************************************************************************/*}
<!--
{*

This template displays a QSelect control.
It takes the same parameters as input.tpl

*}

SET a$id -->{a$name}<!-- END:SET
SET a$class -->{a$class|default:'QSelect'}<!-- END:SET

IF !a$title
	SET a$title
		-->{a$_caption_}<!--
	END:SET
END:IF


SET $CAPTION
	IF a$_caption_
		--><label for="{a$id}" class="{a$class}" onclick="return IlC(this)"><!--
		IF a$_mandatory --><span class="mandatory"><!-- END:IF
		-->{a$_caption_}<!--
		IF a$_mandatory --></span><!-- END:IF
		--></label><!--
	END:IF
END:SET


SET $INPUT

	IF !g$__QS --><link rel="stylesheet" type="text/css" href="{home:'QSelect/style.css'}" /><!-- END:IF
	SET $INPUT -->{home:|urlencode}<!-- END:SET
	IF a$_mandatory --><span class="mandatory"><!-- END:IF

	--><span class="QSstyle"><input autocomplete="off" {a$|htmlArgs} /><img src="{home:'QSelect/b.gif'}" id="__QSb{$INPUT}{a$name}" onmouseover="this.src={home:'QSelect/bh.gif'|js}" onmouseout="this.src={home:'QSelect/b.gif'|js}" onmousedown="this.src={home:'QSelect/bp.gif'|js}" onmouseup="this.onmouseover()" alt=" " title="" /></span><script type="text/javascript">/*<![CDATA[*/<!--

	IF !g$__QS
		SET g$__QS
			--><div id="__QSd1" style="position:absolute;visibility:hidden;z-index:9;top:0px"><form action=""><div id="__QSd2" style="position:absolute"><img src="{home:'QSelect/tr.png'}" width="5" height="10" /><br /><img src="{home:'QSelect/r.png'}" width="5" height="5" id="__QSi1" /><br /><img src="{home:'QSelect/br.png'}" width="5" height="5" /></div><div id="__QSd3" style="position:absolute"><img src="{home:'QSelect/bl.png'}" width="10" height="5" /><img src="{home:'QSelect/b.png'}" width="5" height="5" id="__QSi2" /></div><select id="__QSs" size="7"></select></form></div><script type="text/javascript" src="{home:'js/QSelect'}"></script ><!--
		END:SET
		-->window.__QSd||(window.__QSd=1,footerHtml.push({g$__QS|js}));<!--
		SET g$__QS -->1<!-- END:SET
	END:IF -->
	lE=gLE({a$name|js},0,1)
	lE.__QSt={$INPUT|js}
	lE.lock={a$_lock|js}

	lE.gS=function(){return valid(this<!-- LOOP a$_valid -->,{$VALUE|js}<!-- END:LOOP -->)}

	lE.cS=function(){return IcES([0<!-- LOOP a$_elements -->,{$name|js},{$onempty|js},{$onerror|js}<!-- END:LOOP -->],this.form)};<!-- IF a$_focus_ -->lE.focus()<!-- END:IF -->//]]></script ><script type="text/javascript" src="{home:a$_src}"></script ><!--

	IF a$_mandatory --></span><!-- END:IF

END:SET


SET $ERROR
	IF a$_errormsg -->{a$_beforeError_|default:g$inputBeforeError}<span class="errormsg">{a$_errormsg}</span>{a$_afterError_|default:g$inputAfterError}<!-- END:IF
END:SET


-->{a$_format_|default:g$inputFormat|echo:$CAPTION:$INPUT:$ERROR}
