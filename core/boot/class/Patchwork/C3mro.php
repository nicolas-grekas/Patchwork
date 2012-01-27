<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/

/**
 * C3 Method Resolution Order graph linearization.
 *
 * See http://python.org/2.3/mro.html
 */
class Patchwork_C3mro
{
    protected $cache = array(), $getParentNodes;

    function __construct($getParentNodes)
    {
        $this->getParentNodes = $getParentNodes;
    }

    function linearize($node)
    {
        $resultSeq =& $this->cache[$node];

        // If result is cached, return it
        if (null !== $resultSeq) return $resultSeq;

        $parent = call_user_func($this->getParentNodes, $node);

        // If no parent, result is trivial
        if (!$parent) return $resultSeq = array($node);

        // Compute C3 MRO
        $seqs = array(array($node));
        foreach ($parent as $seq) $seqs[] = $this->linearize($seq);
        reset($parent);
        $seqs[] = $parent;

        $resultSeq = array();
        $parent = false;

        for (;;)
        {
            if (!$seqs) return $resultSeq;

            unset($seq);
            $notHead = array();
            foreach ($seqs as $seq)
                foreach (array_slice($seq, 1) as $seq)
                    $notHead[$seq] = 1;

            foreach ($seqs as &$seq)
            {
                $parent = reset($seq);

                if (isset($notHead[$parent])) $parent = false;
                else break;
            }

            if (false === $parent)
            {
                $resultSeq = null;
                throw new Patchwork_C3mro_InconsistentHierarchyException($node);
            }
            else $resultSeq[] = $parent;

            foreach ($seqs as $k => &$seq)
            {
                if ($parent === current($seq)) unset($seqs[$k][key($seq)]);
                if (!$seqs[$k]) unset($seqs[$k]);
            }
        }
    }
}

class Patchwork_C3mro_InconsistentHierarchyException extends Exception
{
}
