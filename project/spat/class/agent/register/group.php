<?php

class agent_register_group extends agent
{
	public function render()
	{
		$data = (object) array();

		$form = new iaForm('group');
		$form->autoPopulate($data);

		$form->add('text', 'company');
		$form->add('textarea', 'adress');
		$form->add('text', 'zipcode');
		$form->add('text', 'city');
		$form->add('QSelect', 'country', array('src' => 'QSelect/countries'));

		$form->add('text', 'contact_firstname');
		$form->add('text', 'contact_lastname');
		$form->add('text', 'contact_email');
		$form->add('text', 'contact_phone');
		$form->add('text', 'contact_fax');

		$form->add('text', 'fact_company');
		$form->add('textarea', 'fact_adress');
		$form->add('text', 'fact_zipcode');
		$form->add('text', 'fact_city');
		$form->add('QSelect', 'fact_country', array('src' => 'QSelect/countries'));
		$form->add('text', 'fact_vat');

		$submit = $form->add('submit', 'submit');

		if ($submit->isOn()) CIA::redirect('register/group/member/');

		return $data;
	}
}
