<?php

class extends agent
{
	protected $data = array();

	function compose($o)
	{
		$f = new pForm($o);
		$f->setDefaults($this->data);

		$f->pushContext($o, substr(p::$agentClass, 6));
		$send = $f->add('submit', 'send');
		$f->pullContext();

		$o = $this->composeForm($o, $f, $send);

		$f = $o->form;
		$send = $o->f_send;
		unset($o->form, $o->f_send);
		$o->form = $f;
		$o->f_send = $send;

		if ($send->isOn() && $this->formIsOk($f) && $send->isOn())
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

	protected function composeForm($o, $f, $send)
	{
		return $o;
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
