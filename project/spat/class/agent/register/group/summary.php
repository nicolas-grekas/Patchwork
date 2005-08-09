<?php

class agent_register_group_summary extends agent
{
	public function render()
	{
		$data = (object) SESSION::get('group');

		$form = new iaForm;
		$form->autoPopulate($data);

		$submit = $form->add('submit', 'submit');

		return $data;
	}
}
