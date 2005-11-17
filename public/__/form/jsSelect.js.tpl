<!-- SET a$DATA --><!-- AGENT $f_select --><!-- END:SET

-->function jsSelectPrint(a,m,i,c){document.write('<select '+a+'>'+(i&&!m?'<option value="">'+c+'</option>':'')+{a$DATA|replace:'<select[^>]*>':''|replace:'<script.*':''|escape:'js'})}
function jsSelectInit(o,v){var i,j;--v.length;o=o.options;for(i in o){i=o[i];i.value=i.text;for(j in v)if(v[j]==i.text){i.selected=1;delete v[j];break;}}}
