<!--

IF a$loop --><!--

	LOOP a$option
		-->{a$name}.add('o{$option_id}','c{$choice_id}',{$label|escape:'js'},'admin/option/{$option_id}','','',{'img/%s.gif'|printf:$type|escape:'js'},{'img/%s.gif'|printf:$type|escape:'js'});<!--
		LOOP $choice
			-->{a$name}.add('c{$choice_id}','o{$option_id}',{$label|escape:'js'},'admin/option/{$option_id}/{$choice_id}');<!--
			AGENT 'admin/option/dtree' option=$option name=a$name loop=1 --><!--
		END:LOOP --><!--
	END:LOOP --><!--


ELSE --><!--

	SET a$dtree --><!--
		AGENT 'admin/option/dtree' option=$option name=a$name loop=1 --><!--
	END:SET -->
<script src="js/dtree"></script><script><!--

{a$name} = new dTree('{a$name}');

{a$name}.add('c0',-1,{"Liste des options d'inscription"|escape:'js'},{g$__AGENT__|escape:'js'});
{a$dtree}

document.write({a$name});

//--></script><!--

END:IF -->
