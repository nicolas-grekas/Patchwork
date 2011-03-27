<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


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
                else W('Something is wrong between Patchwork::__URI__() and PATCHWORK_BASE');

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
