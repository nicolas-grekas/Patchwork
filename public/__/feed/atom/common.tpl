<!--

AGENT 'feed/atom/tag/title'   value=a$title   type=a$title_type  mandatory=a$mandatory
AGENT 'feed/atom/tag/id'      value=a$id                         mandatory=a$mandatory
AGENT 'feed/atom/tag/updated' value=a$updated                    mandatory=a$mandatory
AGENT 'feed/atom/tag/rights'  value=a$rights  type=a$rights_type

LOOP a$author      --><!-- AGENT 'feed/atom/person/author'      name=$name uri=$uri email=$email --><!-- END:LOOP
LOOP a$contributor --><!-- AGENT 'feed/atom/person/contributor' name=$name uri=$uri email=$email --><!-- END:LOOP
LOOP a$category    -->{* Attributes: term*, scheme, label *}<category term="{$term}" {$|htmlArgs:'term'}/><!-- END:LOOP
LOOP a$link        -->{* Attributes: href*, rel, type, hreflang, title, length *}<link href="{$href}" {$|htmlArgs:'href'}/><!-- END:LOOP

-->
