<?php

class agent_index extends agent
{
	public function render()
	{
		$a = (object) array(
			'PINEURS' => new loop_sql('SELECT * FROM annuaire ORDER BY nom')
		);

		return $a;
	}
}
