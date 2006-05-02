<?php

class agent_css extends agent_bin
{
	public $argv = array('__0__');

	protected $maxage = -1;

	protected $watch = array('public/css');

	protected $a = array();
	protected $v = array();
	protected $g = array();

	public function compose()
	{
		CIA::header('Content-Type: text/css; charset=UTF-8');

		$css = $this->argv->__0__;

		if ($css !== '')
		{
			$css = str_replace(
				array('\\', '../'),
				array('/' , '/'),
				"css/$css.css"
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
		eval($parser->compile($css . '.tpl'));

		return (object) array('DATA' => ob_get_clean());
	}
}
