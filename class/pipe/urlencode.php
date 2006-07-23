<?php

class
{
	static function php($str)
	{
		return rawurlencode(CIA::string($str));
	}

	static function js()
	{
		?>/*<script>*/

P$urlencode = function($str)
{
	return eUC(str($str));
}

<?php	}
}
