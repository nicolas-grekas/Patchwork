<?php

class agent_register_individual_summary extends agent
{
	public function compose()
	{
		$data = (object) SESSION::get('indiv');

		$form = new iaForm($data);

		$submit = $form->add('submit', 'submit');

		return $data;
	}
}
