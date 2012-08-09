<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

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
        foreach ($parent as $seq) if ($seq !== $node) $seqs[] = $this->linearize($seq);
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
