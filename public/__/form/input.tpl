<!--*

This template merges all standard HTML form elements (<input type="*">, <textarea> and <select>) into one single agent.

You can pass it every HTML attribute you need (class, on*, ...), they will be used to define the element.

You can control it with the additional arguments:
- a$_caption_										: the caption of the element, with class="mandatory" if needed
- a$_glue_			|default:g$checkboxGlue			: for radio|checkbox elements : string to put to glue a list of radio|checkbox
- a$_beforeError_	|default:g$inputBeforeError		: HTML code put at the beginning of an error message
- a$_afterError_	|default:g$inputAfterError		: HTML code put at the end of an error message
- a$_focus_											: set the focus on this element
- a$_format_		|default:g$inputFormat			: a string to format the output where ("=>" means "is replaced by"):
														  %0 => the caption,
														  %1 => the control,
														  %2 => the error message,
														  %% => %

Question : should I add a label attribute to every <option> tag ?

*--><!--

IF a$_type == 'check' --><!--
	SET a$_check -->1<!-- END:SET --><!--
	SET a$_type --><!--
		IF a$multiple || a$_option == 1 -->checkbox<!-- ELSE -->radio<!-- END:IF --><!--
	END:SET --><!--
END:IF --><!--

IF !a$type --><!-- SET a$type -->{a$_type}<!-- END:SET --><!-- END:IF --><!--

SET a$id -->FiD{g+1$GLOBID}<!-- END:SET --><!--
SET a$class -->{a$class|default:a$type}<!-- END:SET --><!--

IF !a$title --><!--
	SET a$title
		-->{a$_caption_|replace:'<[^>]*>':''}<!--
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

	SET a$_JsStart
		--><script type="text/javascript">/*<![CDATA[*/
		lE=gLE({a$name|js}<!-- IF a$multiple -->,1<!-- END:IF -->)
		if(lE){<!--
	END:SET --><!--

	SET a$_JsEnd
		-->}/*]]></script><!--
	END:SET --><!--

	IF a$_mandatory --><span class="mandatory"><!-- END:IF --><!--

	IF a$_check --><!--

		LOOP a$_option --><!--
			IF $_groupOn --><!--
				SET a$_i -->0<!-- END:SET
				--><fieldset class="{a$class}"><legend class="{a$class}">{$label}</legend><!--
			ELSEIF $_groupOff
				--></fieldset><!--
			ELSE --><!--
				IF a+1$_i -->{a$_glue_|default:g$checkboxGlue|default:'<br />'}<!-- END:IF --><!--

				SET $class -->{$class|default:a$class}<!-- END:SET

				--><input {$|htmlArgs:'caption':'selected'} {a$|htmlArgs:'class'} /><!--

				IF $caption --><label for="{a$id}" class="{$class}" onclick="return IcbC(event,this)">&nbsp;{$caption}</label><!-- END:IF --><!--
				SET a$id -->FiD{g+1$GLOBID}<!-- END:SET --><!--
			END:IF --><!--
		END:LOOP

		-->{a$_JsStart}lE.gS=IgCS;<!--

	ELSEIF a$type == 'select'

		--><select {a$|htmlArgs:'type'}><!--

		IF a$_firstItem && !a$multiple --><option value="">{a$_firstCaption}</option><!-- END:IF --><!--

		LOOP a$_option --><!--
			IF $_groupOn
				--><optgroup {$|htmlArgs}><!--
			ELSEIF $_groupOff
				--></optgroup><!--
			ELSE
				--><option {$|htmlArgs:'caption':'checked'}>{$caption}</option><!--
			END:IF --><!--
		END:LOOP

		--></select>{a$_JsStart}lE.gS=IgSS;<!--

	ELSE --><!--

		IF a$type == 'file' && a$maxlength
			--><input type="hidden" name="MAX_FILE_SIZE" value="{a$maxlength}" /><input {a$|htmlArgs} /><!--

		ELSEIF a$type == 'textarea'
			--><textarea {a$|htmlArgs:'type':'value'}>{a$value}</textarea><!--

		ELSE --><input {a$|htmlArgs} /><!--
		END:IF

		-->{a$_JsStart}<!--

		IF a$type == 'submit' || a$type == 'image' || a$type == 'button'
			-->lE.oc=lE.onclick;
			lE.onclick=function(e){var f=this.form;return(f.precheck?f.precheck():1)&&(this.cS()?(this.oc?this.oc(e):true):false)};<!--

		ELSE
			-->lE.gS=function(){return valid(this<!-- LOOP a$_valid -->,{$VALUE|js}<!-- END:LOOP -->)};<!--

		END:IF --><!--

	END:IF

	-->lE.cS=function(){return IcES([0<!-- LOOP a$_elements -->,{$name|js},{$onempty|js},{$onerror|js}<!-- END:LOOP -->],this.form)};<!-- IF a$_focus_ -->lEF=lE;setTimeout('lEF.focus()',100);<!-- END:IF -->{a$_JsEnd}<!--

	IF a$_mandatory --></span><!-- END:IF --><!--

END:SET --><!--


SET $ERROR --><!--
	IF a$_errormsg -->{a$_beforeError_|default:g$inputBeforeError}<span class="errormsg">{a$_errormsg}</span>{a$_afterError_|default:g$inputAfterError}<!-- END:IF --><!--
END:SET


-->{a$_format_|default:g$inputFormat|echo:$CAPTION:$INPUT:$ERROR}
