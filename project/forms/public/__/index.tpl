<!-- AGENT 'header' title = 'Form: test' --><style type="text/css">

.errormsg
{
	font-style: italic;
}

span.errormsg
{
	padding-left: 10px;
	position: absolute;
}

</style>

{$test}

<!-- AGENT 'http://localhost/cia/annuaire.php/fr/widget/checklist' -->

<!-- AGENT $form _mode_='errormsg' -->

<!-- AGENT $form -->

<!-- SET g$inputFormat --><tr><td>%0 :</td><td>%1%2</td></tr><!-- END:SET -->

<table border="0">
<!-- AGENT $f_QSelect1       _caption_='QSelect' -->
<!-- AGENT $f_QSelect2       _caption_='QSelect' -->
<!--* AGENT $f_TEXT           _caption_='TEXT' -->
<!-- AGENT $f_FILE           _caption_='FILE' -->
<!-- AGENT $f_TEXTAREA       _caption_='TEXTAREA' -->
<!-- AGENT $f_PASS           _caption_='PASS' -->
<!-- AGENT $f_SELECTMULTIPLE _caption_='SELECTMULTIPLE' -->
<!-- AGENT $f_SELECT         _caption_='SELECT' -->
<!-- AGENT $f_RADIO          _caption_='RADIO' -->
<!-- AGENT $f_CHECKONE       _caption_='CHECKONE' -->
<!-- AGENT $f_CHECKMULTIPLE  _caption_='CHECKMULTIPLE' -->
<!-- AGENT $f_TESTSUBMIT     _caption_='TESTSUBMIT' value='Click!' *-->
</table>

<!-- AGENT $form _mode_='close' -->
<!-- AGENT 'footer' -->
