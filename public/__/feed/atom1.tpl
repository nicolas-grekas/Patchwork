<?xml version="1.0" encoding="utf-8"?>{*

See http://www.atomenabled.org/developers/syndication/atom-format-spec.php

*}<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="{g$__LANG__}"><!--

AGENT 'feed/atom1/common' mandatory=1
	title=$title title_type=$title_type
	id=$id
	updated=$updated
	rights=$rights rights_type=$rights_type
	author=$author
	contributor=$contributor
	category=$category
	link=$link

AGENT 'feed/atom1/tag/generator' value=$generator uri=$generator_uri version=$generator_version
AGENT 'feed/atom1/tag/icon'      value=$icon
AGENT 'feed/atom1/tag/logo'      value=$logo
AGENT 'feed/atom1/tag/subtitle'  value=$subtitle type=$subtitle_type

LOOP $entry
--><entry><!--

	AGENT 'feed/atom1/common' mandatory=1
		title=$title title_type=$title_type
		id=$id
		updated=$updated
		rights=$rights rights_type=$rights_type
		author=$author
		contributor=$contributor
		category=$category
		link=$link

	SET $source -->{$source}<!--
		AGENT 'feed/atom1/common' mandatory=0
			title=$source_title title_type=$source_title_type
			id=$source_id
			updated=$source_updated
			rights=$source_rights rights_type=$source_rights_type
			author=$source_author
			contributor=$source_contributor
			category=$source_category
			link=$source_link

		AGENT 'feed/atom1/tag/generator' value=$source_generator uri=$source_generator_uri version=$source_generator_version
		AGENT 'feed/atom1/tag/icon'      value=$source_icon
		AGENT 'feed/atom1/tag/logo'      value=$source_logo
		AGENT 'feed/atom1/tag/subtitle'  value=$source_subtitle type=$source_subtitle_type
	END:SET

	AGENT 'feed/atom1/tag/source'    value=$source
	AGENT 'feed/atom1/tag/published' value=$published
	AGENT 'feed/atom1/tag/summary'   value=$summary type=$summary_type
	AGENT 'feed/atom1/tag/content'   value=$content type=$content_type $src=$content_src

--></entry><!--
END:LOOP

--></feed>
