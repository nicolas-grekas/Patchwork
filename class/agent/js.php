<?php

class agent_js extends agent
{
	public $argv = array('__0__');
	public $binary = true;

	protected $maxage = -1;

	protected $template = 'bin';
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
		$g->__SCRIPT__ = CIA::htmlescape($_SERVER['SCRIPT_NAME']);
		$g->__URI__ = CIA::htmlescape($_SERVER['REQUEST_URI']);
		$g->__ROOT__ = CIA::htmlescape(CIA_ROOT);
		$g->__LANG__ = CIA::htmlescape(CIA_LANG);
		$g->__AGENT__ = str_replace('_', '/', substr(get_class($this), 6)) . '/';
		$g->__HOST__ = CIA::htmlescape('http' . (@$_SERVER['HTTPS']?'s':'') . '://' . @$_SERVER['HTTP_HOST']);

		$parser = new iaCompiler_php;
		ob_start();
		eval($parser->compile($js . '.tpl'));

		$parser = new jsquiz;
		$parser->addJs(ob_get_clean());

		return (object) array('DATA' => 'CIApID=' . CIA_PROJECT_ID . ';window.w&&w();' . $parser->get());
	}
}
