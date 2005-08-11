<!--

IF a$loop --><!--

	LOOP a$branching
		-->{a$name}.add('{$node_id}','{$parent_node_id}',{$label|escape:'js'});<!--
		AGENT 'admin/option/dtree' branching=$branching name=a$name loop=1 --><!--
	END:LOOP --><!--

ELSE --><!--

	SET a$dtree --><!--
		AGENT 'admin/option/dtree' branching=$branching name=a$name loop=1 --><!--
	END:SET -->
<script src="js/dtree"></script><script><!--

{a$name} = new dTree('{a$name}');

{a$name}.add('c0',-1,{"Branchement des options"|escape:'js'});
{a$dtree}

document.write({a$name});

//--></script><!--

END:IF -->
