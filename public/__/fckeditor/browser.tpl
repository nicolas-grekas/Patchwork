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
<!--

IF $fileUpload

	--><script type="text/javascript">window.parent.frames.frmUpload.OnUploadCompleted({$error|js},{$filename|js})</script><!--

ELSE

	--><?xml version="1.0" encoding="utf-8" ?><!--

	IF $command
		--><Connector command="{$command}" resourceType="{$resourceType}">
<CurrentFolder path="{$currentFolder}" url="{home:$currentUrl:1}" /><!--

		IF $FOLDERS
			--><Folders><!--
			LOOP $FOLDERS --><Folder name="{$VALUE}" /><!-- END:LOOP
			--></Folders><!--
		END:IF

		IF $FILES
			--><Files><!--
			LOOP $FILES --><File name="{$KEY}" size="{$VALUE}" /><!-- END:LOOP
			--></Files><!--
		END:IF

		--></Connector><!--

	ELSE
		--><Connector><Error {$|htmlArgs} /></Connector><!--

	END:IF

END:IF

-->
