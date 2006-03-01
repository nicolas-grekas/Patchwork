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
	return stripAccents($str, $case);
}
<?php 	}
}
