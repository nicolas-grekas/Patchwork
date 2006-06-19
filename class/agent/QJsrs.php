<?php

class agent_QJsrs extends agent_bin
{
	protected $data = array();

	public function compose()
	{
		echo '/*<script type="text/javascript">/**/q="',
			str_replace(array('\\', '"'), array('\\\\', '\\"'), $this->getJs($this->data)),
			'"//</script>',
			'<script type="text/javascript" src="' . CIA::__HOME__() . 'js/QJsrsHandler"></script>';
	}

	protected function getJs(&$data)
	{
		if (is_object($data) || is_array($data))
		{
			$a = '{';

			foreach ($data as $k => $v) $a .= "'" . jsquote($k, false) . "':" . $this->getJs($v) . ',';

			$v = strlen($a);
			if ($v > 1) $a{strlen($a)-1} = '}';
			else $a = '{}';
		}
		else $a = jsquote((string) $data);

		return $a;
	}
}
