<?php

class extends agent_pStudio_explorer
{
	public $get = array(
		'path:c',
		'low:i' => false,
		'high:i' => PATCHWORK_PATH_LEVEL,
		'$serverside:b',
	);

	protected

	$extension = '',
	$editorTemplate = 'pStudio/opener';


	function control()
	{
		$a = get_class($this);

		if (0 === strpos($a, __CLASS__ . '_'))
		{
			$a = substr($a, strlen(__CLASS__) + 1);
			$a = strtr($a, '_', '/') . '/';
			$this->extension = $a;
		}

		$this->get->__0__ = $this->get->path;

		parent::control();
	}

	function compose($o)
	{
		if ($this->is_auth_edit)
		{
			$o->is_auth_edit = true;
			$o = $this->composeEditor($o);
		}
		else $o = $this->composeReader($o);

		return $o;
	}

	protected function composeReader($o)
	{
		if ($a = @file_get_contents($this->realpath))
		{
			if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $a))
			{
				$o->is_binary = true;
			}
			else
			{
				$a && false !== strpos($a, "\r") && $a = strtr(str_replace("\r\n", "\n", $a), "\r", "\n");
				u::isUTF8($a) || $a = utf8_encode($a);
				$o->text = $a;
			}
		}

		return $o;
	}

	protected function composeEditor($o)
	{
		$o = self::composeReader($o);

		if (!empty($o->is_binary))
		{
			unset($o->is_binary, $o->is_auth_edit);
			$o = $this->composeReader($o);
		}
		else
		{
			$this->editorTemplate && $this->template = $this->editorTemplate;

			$f = new pForm($o);
			$f->add('textarea', 'code', array('default' => $o->text));
			$send = $f->add('submit', 'save');
			$send->add('code', '', '');

			if ($send->isOn())
			{
				$code = $send->getData();
				$code = $code['code'];
				if ('' !== $code && "\n" !== substr($code, -1)) $code .= "\n";

				file_put_contents($this->realpath, $code);

				p::redirect();
			}

			unset($o->text);
		}

		return $o;
	}
}
