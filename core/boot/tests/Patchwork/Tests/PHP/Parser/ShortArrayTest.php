<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class ShortArrayTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p->targetPhpVersionId = 50300;

        $p = new Parser\BracketWatcher($p);
        $p = new Parser\ShortArray($p);

        return $p;
    }

    function testShortOpenEcho()
    {
        $parser = $this->getParser();

        $in = '<?php []; if (0) {} []; ${""}[];';
        $out = '<?php array(); if (0) {} array(); ${""}[];';

        $this->assertSame( $out, $parser->parse($in) );
        $this->assertSame( array(), $parser->getErrors() );
    }
}
