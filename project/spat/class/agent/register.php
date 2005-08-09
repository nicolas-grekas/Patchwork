<?php

class agent_register extends agent
{
	public function render()
	{
		$data = (object) array();

		$form = new iaForm;
		$form->autoPopulate($data);
		$form->setPrefix('');

		$form->add('text', 'email');
		$form->add('text', 'ref');

		return $data;
	}
}
