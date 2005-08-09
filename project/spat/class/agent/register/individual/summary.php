<?php

class agent_register_individual_summary extends agent
{
	public function render()
	{
		$data = (object) SESSION::get('indiv');

		$form = new iaForm;
		$form->autoPopulate($data);

		$submit = $form->add('submit', 'submit');

		return $data;
	}
}
