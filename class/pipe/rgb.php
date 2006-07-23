<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class
{
	static function php($r, $g, $b)
	{
		$r = CIA::string($r) - 0;
		$g = CIA::string($g) - 0;
		$b = CIA::string($b) - 0;

		return sprintf('#%02x%02x%02x', $r, $g, $b);
	}

	static function js()
	{
		?>/*<script>*/

P$rgb = function($r, $g, $b)
{
	$r = ($r/1 || 0).toString(16);
	$g = ($g/1 || 0).toString(16);
	$b = ($b/1 || 0).toString(16);

	if ($r.length < 2) $r = '0' + $r;
	if ($g.length < 2) $g = '0' + $g;
	if ($b.length < 2) $b = '0' + $b;

	return '#' + $r + $g + $b;
}

<?php	}
}
