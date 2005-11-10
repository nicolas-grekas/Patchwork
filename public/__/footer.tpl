<!-- SERVERSIDE -->
<script><!--
w=addOnload
w.o=window.onload

onload=function()
{
	var i;
	if(w.o)i=w.o,w.o=null,i()
	for(i=0;i<w.p.length;++i)w.p[i]()
	w.p.length=0
	onload=null
}

if(window.Error)document.cookie='JS=1; path=/',document.cookie='JS=1; expires=Sun, 17-Jan-2038 19:14:07 GMT; path=/'
//--></script>
<!-- END:SERVERSIDE -->
</body>
</html>
