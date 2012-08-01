<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class loop_altLang extends loop
{
    protected $lang, $alt;

    protected static $nativeLang = array(
        'fr' => 'Français',
        'en' => 'English',
        'es' => 'Español',
        'de' => 'Deutsch',
    );


    protected function prepare()
    {
        if (PATCHWORK_I18N)
        {
            $this->lang = Patchwork::__LANG__();

            if (!isset($this->alt))
            {
                $a = array();

                $base = preg_quote($_SERVER['PATCHWORK_BASE'], "'");
                $base = explode('__', $base, 2);
                $base[1] = '/' === $base[1] ? '[^?/]+(/?)' : ".+?({$base[1]})";
                $base = "'^({$base[0]}){$base[1]}(.*)$'D";

                if (preg_match($base, Patchwork::__URI__(), $base))
                {
                    unset($base[0]);

                    foreach ($GLOBALS['CONFIG']['i18n.lang_list'] as $k => $v)
                    {
                        if ('' === $k) continue;

                        $v = $base[1] . $v . $base[2] . ($this->lang === $k ? $base[3] : Patchwork::translateRequest($base[3], $k));

                        $a[] = (object) array(
                            'lang' => $k,
                            'title' => isset(self::$nativeLang[$k]) ? self::$nativeLang[$k] : $k,
                            'href'  => $v,
                        );
                    }
                }
                else user_error('Something is wrong between Patchwork::__URI__() and PATCHWORK_BASE');

                $this->alt =& $a;
            }

            return count($this->alt);
        }
        else return 0;
    }

    protected function next()
    {
        if (list(, $a) = each($this->alt)) return $a;
        else reset($this->alt);
    }
}
