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
<!-- AGENT 'header' title=a$title -->

{a$message}

<!-- IF a$time > 0 -->
<script type="text/javascript">/*<![CDATA[*/
<!-- SET a$redirect -->{home:a$redirect:1}<!-- END:SET -->
<!-- SET a$a -->location.replace({a$redirect|js})<!-- END:SET -->
setTimeout({a$a|js},{a$time*1000})
//]]></script ><meta http-equiv="refresh" content="{a$time}; URL={a$redirect}" />
<!-- END:IF -->

<!-- AGENT 'footer' -->
