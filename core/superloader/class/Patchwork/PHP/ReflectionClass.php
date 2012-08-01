<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP;

/**
 * ReflectionClass hides superpositioning to the native ReflectionClass users.
 */
class ReflectionClass extends \ReflectionClass
{
    protected

    $isTop = true,
    $superStack = array(),
    $superParent = false;


    function __construct($class)
    {
        is_string($class) && \Patchwork\Superloader::exists($class, false); // Do a lightweigh autoload

        parent::__construct($class);

        if (false !== $parent = parent::getParentClass())
        {
            $p = $parent->name;
            $top = $this->name;
            $i = strrpos($top, '__');

            if (false !== $i
                && isset($top[$i+2])
                && '' === trim(substr($top, $i+2), '0123456789'))
            {
                $this->isTop = false;
                $top = substr($top, 0, $i);
            }

            $top .= '__';
            $i = strlen($top);

            while (isset($p[$i]) && $top === rtrim($p, '0123456789'))
            {
                $this->superStack[] = $parent;
                if (false === $parent = $parent->getParentClass()) break;
                $p = $parent->name;
            }

            $this->superParent = $parent;
        }
    }

    function getConstants()
    {
        $c = parent::getConstants();
        unset($c["f\x9D"], $c["i\x9D"]);
        return $c;
    }

    function getDefaultProperties()
    {
        $props = array();

        if ($this->isTop)
        {
            foreach ($this->superStack as $s)
                $props += $s->getDefaultProperties();
        }
        else if ($this->superParent)
        {
            $props = $this->superParent->getDefaultProperties();
        }

        return $props;
    }

    function getDocComment()
    {
        if ($this->isTop)
            foreach ($this->superStack as $s)
                if (false !== $doc = $s->getDocComment())
                    return $doc;

        return false;
    }

    function getInterfaces()
    {
        if ($this->isTop) $interfaces = parent::getInterfaces();
        else if ($this->superParent) $interfaces = $this->superParent->getInterfaces();
        else $interfaces = array();

        foreach ($interfaces as &$i)
            $i = new self($i->name);

        return $interfaces;
    }

    function getMethod($name)
    {
        if ($this->isTop)
        {
            foreach ($this->superStack as $s)
            {
                try {return $s->getMethod($name);}
                catch (\ReflectionException $s) {}
            }
        }
        else if ($this->superParent)
        {
            return $this->superParent->getMethod($name);
        }

        return parent::getMethod($name);
    }

    function getMethods($filter = -1)
    {
        if ($this->isTop)
        {
            $meths = parent::getMethods($filter);

            if (\ReflectionMethod::IS_PRIVATE & $filter)
            {
                $u = array();

                foreach ($this->superStack as $s)
                    foreach ($s->getMethods(\ReflectionMethod::IS_PRIVATE) as $m)
                        isset($u[$m->name]) || $u[$m->name] = $meths[] = $m;
            }

            return $meths;
        }
        else if ($this->superParent)
        {
            return $this->superParent->getMethods($filter);
        }
        else return array();
    }

    function getParentClass()
    {
        return false !== $this->superParent ? new self($this->superParent->name) : false;
    }

    function getProperties($filter = -1)
    {
        if ($this->isTop)
        {
            $props = parent::getProperties($filter);

            if (\ReflectionProperty::IS_PRIVATE & $filter)
            {
                $u = array();

                foreach ($this->superStack as $s)
                    foreach ($s->getProperties(\ReflectionProperty::IS_PRIVATE) as $p)
                        isset($u[$p->name]) || $u[$p->name] = $props[] = $p;
            }

            return $props;
        }
        else if ($this->superParent)
        {
            return $this->superParent->getProperties($filter);
        }
        else return array();
    }

    function getProperty($name)
    {
        if ($this->isTop)
        {
            foreach ($this->superStack as $s)
            {
                try {return $s->getProperty($name);}
                catch (\ReflectionException $s) {}
            }
        }
        else if ($this->superParent)
        {
            return $this->superParent->getProperty($name);
        }

        return parent::getProperty($name);
    }

    function getStaticProperties()
    {
        if ($this->isTop)
        {
            $props = array();

            foreach ($this->superStack as $s)
                $props += $s->getStaticProperties();

            return $props;
        }
        else if ($this->superParent)
        {
            return $this->superParent->getMethods($filter);
        }
        else return array();
    }

    function hasMethod($name)
    {
        if ($this->isTop)
        {
            foreach ($this->superStack as $s)
                if ($s->hasMethod($name))
                    return true;
        }
        else if ($this->superParent)
        {
            return $this->superParent->hasMethod($name);
        }

        return false;
    }

    function hasProperty($name)
    {
        if ($this->isTop)
        {
            foreach ($this->superStack as $s)
                if ($s->hasProperty($name))
                    return true;
        }
        else if ($this->superParent)
        {
            return $this->superParent->hasProperty($name);
        }

        return false;
    }
}
