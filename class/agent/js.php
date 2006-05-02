<?php

class agent_js extends agent_bin
{
	public $argv = array('__0__');

	protected $maxage = -1;

	protected $watch = array('public/js');

	protected $a = array();
	protected $v = array();
	protected $g = array();

	public function compose()
	{
		CIA::header('Content-Type: text/javascript; charset=UTF-8');

		$js = $this->argv->__0__;

		if ($js !== '')
		{
			$js = str_replace(
				array('\\', '../'),
				array('/' , '/'),
				"js/$js.js"
			);
		}

		$a = (object) $this->a;
		$d = $v = (object) $this->v;
		$g = (object) $this->g;
		$g->__DEBUG__ = DEBUG ? DEBUG : 0;
		$g->__HOST__ = CIA::__HOST__();
		$g->__LANG__ = CIA::__LANG__();
		$g->__HOME__ = CIA::__HOME__();
		$g->__AGENT__ = str_replace('_', '/', substr(get_class($this), 6)) . '/';
		$g->__URI__ = htmlspecialchars(CIA::__URI__());

		$parser = new iaCompiler_php(true);
		ob_start();
		eval($parser->compile($js . '.tpl'));

		$parser = new jsquiz;
		$parser->addJs(ob_get_clean());

		return (object) array('DATA' => $parser->get());
	}
}
