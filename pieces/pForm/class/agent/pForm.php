<?php

class extends agent
{
	protected $data = array();

	function compose($o)
	{
		$f = new pForm($o);
		$f->setDefaults($this->data);

		$send = $f->add('submit', 'send');

		$this->composeForm($f, $send);

		if ($send->isOn() && $this->formIsOk($f))
		{
			$a = $this->data ? 'save' : 'create';

			list($send, $b) = (array) $this->save($send->getData()) + array(null, null);

			if (null === $send) W(get_class($this) . '->save() result must be non-null');
			else if (false !== $send)
			{
				$b && s::flash('headerMessage', true !== $b ? $b : $a);
				p::redirect($send);
			}
		}

		return $o;
	}

	protected function composeForm($f, $send)
	{
	}

	protected function formIsOk($f)
	{
		return true;
	}

	protected function save($data)
	{
		return false;
	}
}
