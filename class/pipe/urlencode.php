<?php

class pipe_urlencode
{
	static function php($str)
	{
		return rawurlencode(CIA::string($str));
	}

	static function js()
	{
		?>/*<script>*/

P$<?php echo substr(__CLASS__, 5)?> = function($str)
{
	return eUC(str($str));
}

<?php	}
}
