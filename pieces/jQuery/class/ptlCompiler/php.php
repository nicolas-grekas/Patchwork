<?php

class extends self
{
	function __construct($template, $binaryMode)
	{
		if (0 === strpos($template, 'js/jquery'))
		{
			$this->Xlvar = '\{\[';
			$this->Xrvar = '\]\}';

			$this->blockSplit = ' ***//*** ';
			$this->Xlblock = '\/\*\*\*\s*';
			$this->Xrblock = '\s*\*\*\*\/\n?';
			$this->Xcomment = '\{\[\*.*?\*\]\}\n?';
		}

		parent::__construct($template, $binaryMode);
	}
}
