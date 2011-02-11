<?php

class p extends patchwork
{
	static function __constructStatic()
	{
		trigger_error("Using class `p' for class `patchwork' without declaring the alias with `use patchwork as p;' is deprecated", E_USER_DEPRECATED);
	}
}
