<?php

class agent_index extends agent
{
	protected $watch = array('sql/table/annuaire');

	public function render()
	{
		$a = (object) array(
			'PINEURS' => new loop_sql('SELECT * FROM annuaire ORDER BY nom, prenom')
		);

		return $a;
	}
}
