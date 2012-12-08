<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

class Patchwork_PHP_Preprocessor52 extends Patchwork_PHP_Preprocessor
{
    protected $preprocessor;
    protected static $preprocessor52, $code52;

    function __construct($preprocessor)
    {
        parent::__construct();
        $preprocessor->filterPrefix =& $this->filterPrefix;
        $this->preprocessor = $preprocessor;
    }

    function process($code)
    {
        self::$code52 = $code;
        self::$preprocessor52 = $this->preprocessor;
        return '<?php return eval(' . get_class($this) . '::process52(__FILE__));';
    }

    static function process52($uri)
    {
        self::$preprocessor52->uri = $uri;
        $code = '?>' . self::$preprocessor52->process(self::$code52);
        self::$preprocessor52 = self::$code52 = null;
        return $code;
    }
}
