<!--

SET a$TITLE -->Bienvenue<!-- END:SET --><!--
SET a$CONTENT -->page/accueil<!-- END:SET --><!--
SET a$MENU --><!--
	LOOP $menu --><!--
		IF $KEY==g$__1__ || !(a+1$COUNTER || g$__1__) --><!--
			SET a$TITLE -->{$VALUE}<!-- END:SET --><!--
			SET a$CONTENT -->page/{$KEY}<!-- END:SET

			--><li class="selected"><a href="{$KEY}/">{$VALUE}</a><!--

			IF $submenu --><!--

				SET a$SUBCOUNTER
					-->0<!--
				END:SET

				--><ul><!--
	
				LOOP $submenu
				--><!--
					IF $KEY==g$__2__ || !(a+1$SUBCOUNTER || g$__2__)  --><!--

						SET a$TITLE -->{a$TITLE} - {$VALUE}<!-- END:SET --><!--
						SET a$CONTENT -->page/{$$KEY}/{$KEY}<!-- END:SET
						--><li class="selected"><a href="{$$KEY}/{$KEY}/">{$VALUE}</a><!--
	
					ELSE
						--><li><a href="{$$KEY}/{$KEY}/">{$VALUE}</a></li><!--
					END:IF --><!--
				END:LOOP

				--></ul><!--
			END:IF
			--></li><!--
		ELSE
			--><li><a href="{$KEY}/">{$VALUE}</a><!--
		END:IF --><!--
	END:LOOP --><!--
END:SET --><!--
	
AGENT 'header' title = a$TITLE

-->

<div id="menu"><ul>{a$MENU}</ul></div>

<div id="main"><!-- AGENT a$CONTENT --></div>

<!-- AGENT 'footer' -->
