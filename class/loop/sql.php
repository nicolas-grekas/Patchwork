<?php

if (function_exists('mysqli_connect'))
{
	class extends loop_sql_mysqli {}
}
else
{
	class extends loop_sql_MDB2 {}
}
