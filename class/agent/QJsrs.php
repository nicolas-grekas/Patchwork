<?php

class agent_QJsrs extends agent_bin
{
	protected $data = array();
	protected $from = array('\\'  , "\r", "\n", "'"  , '</'  );
	protected $to   = array('\\\\', '\r', '\n', "\\'", '<\\/');

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

			foreach ($data as $k => $v) $a .= "'" . str_replace($this->from, $this->to, $k) . "':" . $this->getJs($v) . ',';

			$v = strlen($a);
			if ($v > 1) $a{strlen($a)-1} = '}';
			else $a = '{}';
		}
		else
		{
			$a = (string) $data;
			if ((string) $a !== (string) ($a-0)) $a = "'" . str_replace($this->from, $this->to, $a) . "'";
		}

		return $a;
	}
}
