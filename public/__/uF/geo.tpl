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
<!-- IF a$latitude || a$longitude -->
<div class="geo">Geo:
	<!-- IF a$latitude  --><abbr class="latitude"  title="{a$latitude}" >{a$latitude|geo:'latitude'}</abbr>{a$longitude|test:', '}<!-- END:IF -->
	<!-- IF a$longitude --><abbr class="longitude" title="{a$longitude}">{a$latitude|geo:'longitude'}</abbr><!-- END:IF -->
</div>
<!-- END:IF -->
