<!-- SET a$inputFormat -->{g$inputFormat}<!-- END:SET -->
<!-- SET g$inputFormat --><tr><td nowrap="nowrap">%0{"&nbsp;:"}</td><td width="100%">%1%2</td></tr><!-- END:SET -->

<!-- LOOP a$option -->
	<!-- IF $type=='separator' -->
	<tr><th colspan="2">{$label|escape:'unhtml'}</th></tr>
	<!-- ELSE -->
	<!-- AGENT 'input' _caption_=$label _argv_=$f_option onchange="this.form.submitIfValid()" -->
	<!-- END:IF -->
<!-- END:LOOP -->

<!-- SET g$inputFormat -->{a$inputFormat}<!-- END:SET -->
