<?php

class agent_index extends agent
{
	protected $maxage = -1;
	protected $watch = array('forms/index');

	public function compose()
	{
		$a = (object) array();

		$a->test = -72.34;

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

		$form = new iaForm($a);

		$form->add('submit', 'TESTSUBMIT');

		$form->add('hidden', 'HIDDEN');
		$form->add('file', 'FILE', array(
			'maxlength' => 1000000
		));

		$form->add('QSelect', 'QSelect1', array(
			'src' => 'pays.js'
		));

		$form->add('QSelect', 'QSelect2', array(
			'src' => 'pays.js',
			'lock' => true
		));

/*		$form->add('text', 'TEXT', array(
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

		$a->f_TESTSUBMIT->add(
			'TEXT', 'Empty text', 'not valid text',
			'SELECT', 'Select empty', 'Select error'
		);

		if ($a->f_TESTSUBMIT->isOn())
		{
			E('ON');
		}
*/
		return $a;
	}
}
