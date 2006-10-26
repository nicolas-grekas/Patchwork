<?xml version="1.0" encoding="utf-8"?><!--

IF $xsl --><?xml-stylesheet type="text/xsl" href="{$xsl}"?><!-- END:IF -->{*

See http://www.atomenabled.org/developers/syndication/atom-format-spec.php

*}<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="{g$__LANG__}"><!--

AGENT 'feed/atom/common' mandatory=1
	title=$title title_type=$title_type
	id=$id
	updated=$updated
	rights=$rights rights_type=$rights_type
	author=$author
	contributor=$contributor
	category=$category
	link=$link

AGENT 'feed/atom/tag/generator' value=$generator uri=$generator_uri version=$generator_version
AGENT 'feed/atom/tag/icon'      value=$icon
AGENT 'feed/atom/tag/logo'      value=$logo
AGENT 'feed/atom/tag/subtitle'  value=$subtitle type=$subtitle_type

LOOP $entry
--><entry><!--

	AGENT 'feed/atom/common' mandatory=1
		title=$title title_type=$title_type
		id=$id
		updated=$updated
		rights=$rights rights_type=$rights_type
		author=$author
		contributor=$contributor
		category=$category
		link=$link

	SET $source -->{$source}<!--
		AGENT 'feed/atom/common' mandatory=0
			title=$source_title title_type=$source_title_type
			id=$source_id
			updated=$source_updated
			rights=$source_rights rights_type=$source_rights_type
			author=$source_author
			contributor=$source_contributor
			category=$source_category
			link=$source_link

		AGENT 'feed/atom/tag/generator' value=$source_generator uri=$source_generator_uri version=$source_generator_version
		AGENT 'feed/atom/tag/icon'      value=$source_icon
		AGENT 'feed/atom/tag/logo'      value=$source_logo
		AGENT 'feed/atom/tag/subtitle'  value=$source_subtitle type=$source_subtitle_type
	END:SET

	AGENT 'feed/atom/tag/source'    value=$source
	AGENT 'feed/atom/tag/published' value=$published
	AGENT 'feed/atom/tag/summary'   value=$summary type=$summary_type
	AGENT 'feed/atom/tag/content'   value=$content type=$content_type $src=$content_src

--></entry><!--
END:LOOP

--></feed>
