<table class="option" cellspacing="0">
<tr>
	<th width="100%">{"Option"}</th>
	<th>{"Type"}</th>
	<th>{"TVA"}</th>
	<th></th>
</tr>
<!-- LOOP $option -->
<tr>
	<td><!-- AGENT 'input' _argv_=$edit_label --></td>
	<td><!-- AGENT 'input' _argv_=$edit_type  --></td>
	<td><!-- AGENT 'input' _argv_=$edit_tax_id --></td>
	<td nowrap="nowrap" style="white-space:nowrap">
		<!-- AGENT 'input' type='image' _argv_=$edit_submit src='img/edit.gif' title="Enregistrer les modifications" -->
		<!-- AGENT 'input' type='image' _argv_=$edit_del onclick="return confirm({\"Voulez-vous vraiment supprimer cette option et tout ce qui y est rattaché ?\"|escape:'js'})" src='img/drop.gif' title="Supprimer cette option" -->
		<!-- AGENT 'input' type='image' _argv_=$edit_up src='img/up.gif' title="Monter cette option dans la liste" -->
		<!-- AGENT 'input' type='image' _argv_=$edit_down src='img/down.gif' title="Descendre cette option dans la liste" -->
		<img src="img/expand.gif" onclick="showBlock({$option_id})" />
	</td>
</tr>
<tr id="choice{$option_id}" style="<!-- IF !$choice -->display:none<!-- END:IF -->">
	<td colspan="4" style="padding-left:30px;padding-right:1px">
		<table class="choice">
		<tr>
			<th width="100%">{"Choix"}</th>
			<th>{"PU HT (-)"}</th>
			<th>{"PU HT (+)"}</th>
			<th></th>
		</tr>
		<!-- LOOP $choice -->
		<tr>
			<td><!-- AGENT 'input' _argv_=$edit_label  --></td>
			<td><!-- AGENT 'input' _argv_=$edit_amount --></td>
			<td><!-- AGENT 'input' _argv_=$edit_amount_raised --></td>
			<td nowrap="nowrap" style="white-space:nowrap">
				<!-- AGENT 'input' type='image' _argv_=$edit_submit src='img/edit.gif' title="Enregistrer les modifications" -->
				<!-- AGENT 'input' type='image' _argv_=$edit_del onclick="return confirm({\"Voulez-vous vraiment supprimer ce choix et tout ce qui y est rattaché ?\"|escape:'js'})" src='img/drop.gif' title="Supprimer cette option" -->
				<!-- AGENT 'input' type='image' _argv_=$edit_up src='img/up.gif' title="Monter ce choix dans la liste" -->
				<!-- AGENT 'input' type='image' _argv_=$edit_down src='img/down.gif' title="Descendre ce choix dans la liste" -->
				<img src="img/expand.gif" onclick="showBlock({$choice_id},1)" />
			</td>
		</tr>
		<!-- SET a$suboption --><!-- AGENT "{g$__AGENT__}recursiveList" choice_id=$choice_id --><!-- END:SET -->
		<tr id="option{$choice_id}" style="<!-- IF !g$suboption -->display:none<!-- END:IF -->">
			<td colspan="4" style="padding-left:10px;padding-right:1px">{a$suboption}</td>
		</tr>
		<!-- END:LOOP -->
		<!-- IF $new_submit -->
		<tr>
			<td><!-- AGENT 'input' _argv_=$new_label  --></td>
			<td><!-- AGENT 'input' _argv_=$new_amount --></td>
			<td><!-- AGENT 'input' _argv_=$new_amount_raised --></td>
			<td><!-- AGENT 'input' _argv_=$new_submit value="Ajouter" --></td>
		</tr>
		<!-- END:IF -->
		</table>
	</td>
</tr>
<!-- END:LOOP -->
<!-- IF $new_submit -->
<tr>
	<td><!-- AGENT 'input' _argv_=$new_label --></td>
	<td><!-- AGENT 'input' _argv_=$new_type  --></td>
	<td><!-- AGENT 'input' _argv_=$new_tax_id --></td>
	<td><!-- AGENT 'input' _argv_=$new_submit value="Ajouter" --></td>
</tr>
<!-- END:IF -->
</table><!--

SET g$suboption
	-->{$option}<!--
END:SET -->
