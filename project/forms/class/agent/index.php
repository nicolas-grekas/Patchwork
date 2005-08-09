<?php

class agent_index extends agent
{
	public $canPost = true;

	protected $maxage = -1;

	protected $watch = array('forms/index');

	public function render()
	{
		$a = (object) array();

		$colors = array(
			'Les couleurs' => array(
				(object) array(
					'caption' => 'blue',
					'style' => 'background-color: blue',
					'disabled' => 'disabled',
				),
				'red',
				'green'
			)
		);

		$form = new iaForm;
		$form->autoPopulate($a, 'form', '');

		$form->add('submit', 'TESTSUBMIT');

		$form->add('hidden', 'HIDDEN');
		$form->add('file', 'FILE', array(
			'maxlength' => 1000000
		));

		$form->add('QSelect', 'QSelect1', array(
			'src' => 'QSelect/namesDb'
		));

		$form->add('text', 'TEXT', array(
			'maxlength' => 16,
			'valid' => 'int'
		));

		$form->add('textarea', 'TEXTAREA', array(
			'maxlength' => 16
		));

		$form->add('password', 'PASS');

		$form->add('select', 'SELECTMULTIPLE', array(
			'firstItem' => '-- please select one --',
			'multiple' => true,
			'item' => $colors
		));

		$form->add('select', 'SELECT', array(
			'firstItem' => '-- please select one --',
			'item' => $colors,
		));

		$form->add('check', 'RADIO', array(
			'firstItem' => '-- please select one --',
			'item' => $colors
		));

		$form->add('check', 'CHECKONE', array(
			'firstItem' => '-- please select one --',
			'item' => array(
			'Les couleurs' => array(
				(object) array(
					'caption' => 'blue',
					'style' => 'background-color: blue',
				),
			)
		)
		));

		$form->add('check', 'CHECKMULTIPLE', array(
			'firstItem' => '-- please select one --',
			'multiple' => true,
			'item' => $colors
		));

		$a->TESTSUBMIT->add(
			'TEXT', 'Empty text', 'not valid text',
			'SELECT', 'Select empty', 'Select error'
		);

		if ($a->TESTSUBMIT->isOn())
		{
			E('ON');
		}

		return $a;
	}
}
