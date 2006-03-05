<?php

class agent_register extends agent
{
	public function compose()
	{
		$data = (object) array();

		$form = new iaForm($data);
		$form->setPrefix('');

		$form->add('text', 'email');
		$form->add('text', 'ref');

		return $data;
	}
}
