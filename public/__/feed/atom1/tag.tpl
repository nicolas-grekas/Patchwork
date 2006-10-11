<!--

IF a$mandatory || a$value

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