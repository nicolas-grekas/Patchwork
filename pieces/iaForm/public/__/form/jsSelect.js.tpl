/***************************************************************************
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
 ***************************************************************************/
<!-- SET a$DATA --><!-- AGENT $f_select --><!-- END:SET

-->document.write('<select '+a+'>'+(i&&!m?'<option value="">'+c+'</option>':'')+{a$DATA|replace:'<select[^>]*>':''|replace:'<script.*':''|js});
function jsSelectInit(o,v){var i,j,k;--v.length;o=o.options;for(i=0;i<o.length;++i){k=o[i];k.value=k.text;for(j in v)if(v[j]==k.text){k.selected=1;delete v[j];break}}}
