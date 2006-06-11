<?php

class agent_register_individual extends agent
{
	public function compose()
	{
		$data = (object) array();

		$form = new iaForm($data, 'indiv');

		$form->add('text', 'firstname');
		$form->add('text', 'lastname');
		$form->add('text', 'email');
		$form->add('text', 'company');
		$form->add('textarea', 'adress');
		$form->add('text', 'zipcode');
		$form->add('text', 'city');
		$form->add('QSelect', 'country', array('src' => 'QSelect/countries', 'lock' => 0));
		$form->add('text', 'phone');
		$form->add('text', 'fax');

		$form->add('text', 'fact_company');
		$form->add('textarea', 'fact_adress');
		$form->add('text', 'fact_zipcode');
		$form->add('text', 'fact_city');
		$form->add('QSelect', 'fact_country', array('src' => 'QSelect/countries'));
		$form->add('text', 'fact_vat');

		$submit = $form->add('submit', 'submit');

		$data->option = new loop_register_option($form, $submit);
		$data->option->loop();

		if ($submit->isOn()) CIA::redirect('register/individual/summary/');

		return $data;
	}
}
