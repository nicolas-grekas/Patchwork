<!--

IF a$loop --><!--

	LOOP a$tree
		-->{a$name}.add(
			{$id|js},
			{$pid|js},
			{$label|js},
			{$url|js},
			{$title|js},
			{$target|js},
			{$icon|js},
			{$iconOpen|js},
			{$open|js}
		);<!--
		AGENT 'dtree' tree=$tree name=a$name loop=1 --><!--
	END:LOOP --><!--

ELSE --><!--

	SET a$dtree --><!--
		AGENT 'dtree' tree=a$tree name=a$name loop=1 --><!--
	END:SET --><!--
	
	IF !g$_DTREE --><!--
		SET g$_DTREE -->1<!-- END:SET
		--><style type="text/css">
.dtree
{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	color: #666;
	white-space: nowrap;
}

.dtree img
{
	border: 0px;
	vertical-align: middle;
}

.dtree a
{
	color: #333;
	text-decoration: none;
}

.dtree a.node, .dtree a.nodeSel
{
	white-space: nowrap;
	padding: 1px 2px 1px 2px;
}

.dtree a.node:hover, .dtree a.nodeSel:hover
{
	color: #333;
	text-decoration: underline;
}

.dtree a.nodeSel
{
	background-color: #c0d2ec;
}

.dtree .clip
{
	overflow: hidden;
}
</style><script type="text/javascript" src="{/}js/dtree"></script><!--
	END:IF --><script type="text/javascript"><!--

{a$name} = new dTree('{a$name}');

{a$name}.add(
	{a$rootId|js},
	-1,
	{a$rootLabel|js},
	{a$rootUrl|js},
	{a$rootTitle|js},
	{$rootTrget|js},
	{$rootIcon|js},
	{$rootIconOpen|js},
	{$rootOpen|js}
	);
{a$dtree}

document.write({a$name});

//--></script><!--

END:IF -->
