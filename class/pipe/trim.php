<?php

class pipe_trim
{
	static function php($a)
	{
		return trim( CIA::string($a) );
	}

	static function js()
	{
		?>/*<script>*/

P$<?php echo substr(__CLASS__, 5)?> = function($a)
{
	return str($a).replace(/^\s+/, '').replace(/\s+$/, '');
}

<?php	}
}
