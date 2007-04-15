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
<div class="vcard" {a$attributes}>
	<!-- IF a$given_name || a$family_name -->
	<div class="<!-- IF a$family_name -->fn <!-- END:IF -->n">
		<!-- IF a$given_name --><span class="given-name">{a$given_name}</span>
			<!-- IF a$additional_name --><span class="additional-name">{a$additional_name}</span><!-- END:IF -->
		<!-- END:IF -->

		<!-- IF a$family_name --><span class="family-name">{a$family_name}</span><!-- END:IF -->
	</div>
	<!-- END:IF -->

	<!-- IF a$organization -->
	<div class="<!-- IF !a$family_name -->fn <!-- END:IF -->org">
		<!-- IF a$organization_title || a$organization_role || a$organization_unit -->
			<!-- IF a$organization_title --><span class="organization-title">{a$organization_title}</span><!-- END:IF -->
			<!-- IF a$organization_role --><span class="organization-role">{a$organization_role}</span><!-- END:IF -->
			<!-- IF a$organization_title || a$organization_role -->,<!-- END:IF -->
			<!-- IF a$organization_unit --><span class="organization-unit">{a$organization_unit}</span>,<!-- END:IF -->
			<span class="organization-name">{a$organization}</span>
		<!-- ELSE -->{a$organization}
		<!-- END:IF -->
	</div>
	<!-- END:IF -->

	<!-- INCLUDE uF/adr -->
	<!-- INCLUDE uF/geo -->

	<!--
	
	IF a$EMAIL
		LOOP a$EMAIL --><div class="email"><span class="type">{$type}</span>: {$email|mailto:$email:'class="value"'}</div> <!-- END:LOOP
	ELSEIF a$email --><div class="email">{a$email|mailto}</div><!--
	END:IF

	-->

	<!--
	
	IF a$TEL
		LOOP a$TEL --><div class="tel"><span class="type">{$type}</span>: <span class="value">{$value}</span></div> <!-- END:LOOP
	ELSEIF a$tel --><div class="tel">{a$tel}</div><!--
	END:IF

	-->

	<!--
	
	IF a$URL
		LOOP a$URL --><div class="url"><a href="{$href}">{$label}</a></div> <!-- END:LOOP
	ELSEIF a$url --><div class="url"><a href="{a$url}">{a$url}</a></div><!--
	END:IF

	-->
</div>
