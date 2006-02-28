<?php

class agent_js extends agent_bin
{
	public $argv = array('__0__');

	protected $maxage = -1;

	protected $watch = array('public/js');

	protected $a = array();
	protected $v = array();
	protected $g = array();

	public function render()
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
		$v = (object) $this->v;
		$g = (object) $this->g;
		$g->__URI__ = htmlspecialchars($_SERVER['REQUEST_URI']);
		$g->__ROOT__ = htmlspecialchars(CIA_ROOT);
		$g->__LANG__ = htmlspecialchars(CIA_LANG);
		$g->__AGENT__ = str_replace('_', '/', substr(get_class($this), 6)) . '/';
		$g->__HOST__ = htmlspecialchars('http' . (@$_SERVER['HTTPS']?'s':'') . '://' . @$_SERVER['HTTP_HOST']);

		if (DEBUG) $v->DEBUG = true;

		$parser = new iaCompiler_php(true);
		ob_start();
		eval($parser->compile($js . '.tpl'));

		$parser = new jsquiz;
		$parser->addJs(ob_get_clean());

		return (object) array('DATA' => $parser->get());
	}
}
