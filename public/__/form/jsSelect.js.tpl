<!-- SET a$DATA --><!-- AGENT $f_select --><!-- END:SET

-->document.write('<select '+a+'>'+(i&&!m?'<option value="">'+c+'</option>':'')+{a$DATA|replace:'<select[^>]*>':''|replace:'<script.*':''|escape:'js'});
function jsSelectInit(o,v){var i,j,k;--v.length;o=o.options;for(i=0;i<o.length;++i){k=o[i];k.value=k.text;for(j in v)if(v[j]==k.text){k.selected=1;delete v[j];break}}}
