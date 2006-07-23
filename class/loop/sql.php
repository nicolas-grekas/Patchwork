<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

if (function_exists('mysqli_connect'))
{
	class extends loop_sql_mysqli {}
}
else
{
	class extends loop_sql_MDB2 {}
}
