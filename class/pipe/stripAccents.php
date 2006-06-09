<?php

class pipe_stripAccents
{
	static function php($str, $case = 0)
	{
		return LIB::stripAccents($str, $case);
	}

	static function js()
	{
		?>/*<script>*/

P$<?php echo substr(__CLASS__, 5)?> = function($str, $case)
{
	if (window.stripAccents) return stripAccents($str, $case);
	else $str = 'Please insert this tag in your templates before using {$example|stripAccents} : <script type="text/javascript" src="{~}js/accents"><\/script>';

	alert($str);

	return esc($str);
}

<?php 	}
}
