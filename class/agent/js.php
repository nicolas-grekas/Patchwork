<?php

class extends agent_bin
{
	public $argv = array('__0__', 'source:bool');

	protected $maxage = -1;

	protected $watch = array('public/js');

	function control()
	{
		CIA::header('Content-Type: text/javascript; charset=UTF-8');

		if (DEBUG || $this->argv->source)
		{
			$tpl = $this->argv->__0__;

			if ($tpl !== '')
			{
				$tpl = str_replace(
					array('\\', '../'),
					array('/' , '/'),
					"js/$tpl.js"
				);
			}

			$this->template = $tpl;
		}
	}

	function compose($o)
	{
		if (!DEBUG && !$this->argv->source)
		{
			$source = (array) $this->argv;
			$source['source'] = 1;

			$source = IA_php::returnAgent(substr(get_class($this), 6), $source);

			$parser = new jsquiz;
			$parser->addJs($source);
			echo $parser->get();
		}

		return $o;
	}
}
