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


class patchwork_PHP_Parser_namespaceBracketer extends patchwork_PHP_Parser
{
    protected

    $nsClose   = false,
    $callbacks = array(
        'tagOpenTag' => T_OPEN_TAG,
        'tagNs'      => T_NAMESPACE,
        'tagEnd'     => T_ENDPHP,
    ),
    $dependencies = array('normalizer', 'stringInfo');


    protected function tagOpenTag(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_OPEN_TAG));
        $this->unshiftTokens(array(T_NAMESPACE, 'namespace'), ';');
    }

    protected function tagNs(&$token)
    {
        if (!isset($token[2][T_NAME_NS])) return;

        if ($this->nsClose)
        {
            $this->nsClose = false;
            return $this->unshiftTokens('}', $token);
        }
        else $this->register(array('tagNsEnd' => array('{', ';')));
    }

    protected function tagNsEnd(&$token)
    {
        $this->unregister(array(__FUNCTION__ => array('{', ';')));

        if (';' === $token[0])
        {
            $this->nsClose = true;
            return $this->unshiftTokens('{');
        }
    }

    protected function tagEnd(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_ENDPHP));
        return $this->unshiftTokens('}', $token);
    }
}
