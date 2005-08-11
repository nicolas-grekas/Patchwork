<?php

class agent_admin_option_dtree extends agent
{
	public $watch = array('option/all');

	public function render()
	{
		return (object) array(
			'branching' => new loop_dtree_branching
		);
	}
}
