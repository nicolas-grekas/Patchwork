<!--*

a$_mode_ : ('errormsg'|'close'|'')
a$_enterControl_ : 0 to keep the browser's behaviour,
                   1 to disable submit on enter key press,
				   2 to enable submit on enter key press by simulating
				     a click on the submit/image element positioned
					 after the currently focused element.

*--><!--

IF a$_mode_ == 'errormsg' --><!--

	IF a$_errormsg
		--><div class="errormsg"><!--
		LOOP a$_errormsg -->{$VALUE}<br /><!-- END:LOOP
		--></div><!--
	END:IF --><!--

ELSEIF a$_mode_ == 'close' --></form><!--

ELSE --><!--

	SET a$action --><!-- IF !a$action -->{g$__URI__}<!-- ELSE -->{root:a$action}<!-- END:IF --><!-- END:SET

	--><form accept-charset="UTF-8" {a$|htmlArgs}><!--

	IF !g$_FORM --><script type="text/javascript" src="{~}js/v"></script><!-- END:IF

	--><script type="text/javascript">/*<![CDATA[*/
lF=document.forms[document.forms.length-1]<!-- IF a$_enterControl_ -->;FeC({a$_enterControl_})<!-- END:IF -->/*]]>*/</script><!--

	IF !g$_FORM && a$_upload --><script type="text/javascript" src="{~}js/upload"></script><!-- END:IF --><!--

	LOOP a$_hidden
		--><input type="hidden" name="{$name}" value="{$value}" /><!--
	END:LOOP --><!--

	SET g$_FORM -->1<!-- END:SET --><!--

END:IF -->
