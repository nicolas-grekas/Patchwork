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
<!-- IF a$post_office_box
	|| a$extended_address
	|| a$street_address
	|| a$locality
	|| a$region
	|| a$postal_code
	|| a$country_name
-->
<div class="adr">
	<!-- IF a$post_office_box  --><div  class="post-office-box">{a$post_office_box}</div><!-- END:IF -->
	<!-- IF a$extended_address --><div  class="extended-address">{a$extended_address}</div><!-- END:IF -->
	<!-- IF a$street_address   --><div  class="street-address">{a$street_address}</div><!-- END:IF -->
	<!-- IF a$postal_code      --><span class="postal-code">{a$postal_code}</span>,<!-- END:IF -->
	<!-- IF a$locality         --><span class="locality">{a$locality}</span>{a$region|test:','}<!-- END:IF -->
	<!-- IF a$region           --><span class="region">{a$region}</span><!-- END:IF -->
	<!-- IF a$country_name     --><span class="country-name">{a$country_name}</span><!-- END:IF -->
</div>
<!-- END:IF -->
