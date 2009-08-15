<?php

class extends agent_js
{
	protected static

	$uiLoad = '',

	$uiDependency = array(

		'ui.draggable'      => 'ui.core',
		'ui.droppable'      => 'ui.core ui.draggable',
		'ui.resizable'      => 'ui.core',
		'ui.selectable'     => 'ui.core',
		'ui.sortable'       => 'ui.core',
		'ui.accordion'      => 'ui.core',
		'ui.dialog'         => 'ui.core ui.draggable ui.resizable',
		'ui.slider'         => 'ui.core',
		'ui.tabs'           => 'ui.core',
		'ui.datepicker'     => 'ui.core',
		'ui.progressbar'    => 'ui.core',

		'effects.core'      => 'ui.core',
		'effects.blind'     => 'effects.core',
		'effects.bounce'    => 'effects.core',
		'effects.clip'      => 'effects.core',
		'effects.drop'      => 'effects.core',
		'effects.explode'   => 'effects.core',
		'effects.fold'      => 'effects.core',
		'effects.highlight' => 'effects.core',
		'effects.pulsate'   => 'effects.core',
		'effects.scale'     => 'effects.core',
		'effects.shake'     => 'effects.core',
		'effects.slide'     => 'effects.core',
		'effects.transfer'  => 'effects.core',

	);

	function compose($o)
	{
		if ($this->debug || $this->get->src)
		{
			$uiLoad = trim(self::$uiLoad);

			if ('' !== $uiLoad)
			{
				$uiLoad = explode(' ', $uiLoad);

				while (list(,$v) = each($uiLoad))
				{
					$o->{strtr($v, '.', '_')} = 1;

					if (isset(self::$uiDependency[$v]))
					{
						$v = self::$uiDependency[$v];
						foreach (explode(' ', $v) as $v) $uiLoad[] = $v;
					}
				}
			}
		}

		return parent::compose($o);
	}
}
