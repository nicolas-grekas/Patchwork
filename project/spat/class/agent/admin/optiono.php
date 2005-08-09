<?php

class agent_admin_optiono extends agent
{
	public function render()
	{
		$data = (object) array();

		$form = new iaForm;
		$form->autoPopulate($data, 'form');

		return $data;
	}
}
