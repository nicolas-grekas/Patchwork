<!-- SET a$inputFormat -->{g$inputFormat}<!-- END:SET -->
<!-- SET g$inputFormat -->%1<!-- END:SET -->
<!-- IF !a$prev --><!-- SET a$prev -->{"< Précédent"}<!-- END:SET --><!-- END:IF -->
<!-- IF !a$next --><!-- SET a$next -->{"Suivant >"}<!-- END:SET --><!-- END:IF -->

<!-- AGENT 'input' value=a$next style='float:right' _argv_=a$submit -->
<input type="button" value="{a$prev|escape}" style="float:left" onclick="location={a$prevurl|escape:'jsh'}" />

<!-- SET g$inputFormat -->{a$inputFormat}<!-- END:SET -->
