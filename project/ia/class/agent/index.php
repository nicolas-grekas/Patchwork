<?php

class agent_index extends agent
{
	protected $maxage = -1;

	public function compose()
	{
		$a = (object) array(
			'menu' => new loop_array(array(
				array(
					'KEY' => 'accueil',
					'VALUE' => 'Accueil',
				),
				array(
					'KEY' => 'intellagence',
					'VALUE' => 'Qui sommes-nous ?',
				),
				array(
					'KEY' => 'services',
					'VALUE' => 'Services',
					'submenu' => new loop_array(array(
						'presentation' => 'Présentation',
						'plus' => 'Points forts',
						'demo' => 'Démonstration'
					))
				),
				array(
					'KEY' => 'references',
					'VALUE' => 'Références',
				),
				array(
					'KEY' => 'contact',
					'VALUE' => 'Nous contacter',
				)),
			
				'filter_rawArray'
			)
		);

		return $a;
	}
}
