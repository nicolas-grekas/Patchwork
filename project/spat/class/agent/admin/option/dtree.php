<?php

class agent_admin_option_dtree extends agent
{
	public $watch = array('option/all');

	public function render()
	{
		return (object) array(
			'option' => new loop_dtree_option
		);
	}
}
