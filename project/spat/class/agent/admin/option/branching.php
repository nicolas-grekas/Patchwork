<?php

class agent_admin_option_branching extends agent
{
	public $watch = array('option/all');

	public function compose()
	{
		return (object) array(
			'branching' => new loop_dtree_branching
		);
	}
}
