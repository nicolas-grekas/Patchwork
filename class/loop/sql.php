<?php

if (function_exists('mysqli_connect'))
{
	class loop_sql extends loop_sql_mysqli {}
}
else
{
	class loop_sql extends loop_sql_pearDB {}
}
