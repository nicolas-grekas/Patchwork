{***************************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************}
<!--

IF a$required || a$value

	SET a$a
		IF a$type    --> type="{a$type}"<!--       END:IF
		IF a$src     --> src="{a$src}"<!--         END:IF {* Only for content *}
		IF a$uri     --> uri="{a$uri}"<!--         END:IF {* Only for generator *}
		IF a$version --> version="{a$version}"<!-- END:IF {* Only for generator *}
	END:SET

	--><{a$__1__}{a$a}><!--

	IF 'xhtml' == a$type --><div xmlns="http://www.w3.org/1999/xhtml">{a$value}</div><!--
	ELSE -->{a$value}<!--
	END:IF

	--></{a$__1__}><!--

END:IF

-->
