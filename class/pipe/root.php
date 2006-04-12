<?php

class pipe_root
{
	static function php($string)
	{
		return CIA::root( CIA::string($string) );
	}

	static function js()
	{
		?>/*<script>*/

P$<?php echo substr(__CLASS__, 5)?> = function($string)
{
	return root( str($string) );
}

<?php	}
}
