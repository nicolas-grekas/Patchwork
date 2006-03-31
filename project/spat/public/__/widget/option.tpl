<!-- SET a$inputFormat -->{g$inputFormat}<!-- END:SET -->
<!-- SET g$inputFormat --><tr><td nowrap="nowrap">%0{"&nbsp;:"}</td><td width="100%">%1%2</td></tr><!-- END:SET -->

<!-- LOOP a$option -->
	<!-- IF $type=='separator' -->
	<tr><th colspan="2">{$label|allowhtml}</th></tr>
	<!-- ELSE -->
	<!-- AGENT $f_option _caption_=$label onchange="this.form.submitIfValid()" -->
	<!-- END:IF -->
<!-- END:LOOP -->

<!-- SET g$inputFormat -->{a$inputFormat}<!-- END:SET -->
