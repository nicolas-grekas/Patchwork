<?php

class
{
	static function php($a)
	{
		return trim( CIA::string($a) );
	}

	static function js()
	{
		?>/*<script>*/

P$trim = function($a)
{
	return str($a).replace(/^\s+/, '').replace(/\s+$/, '');
}

<?php	}
}
