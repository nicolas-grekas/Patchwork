<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class ShortArrayTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($targetPhpVersionId, $dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p->targetPhpVersionId = $targetPhpVersionId;

        $p = new Parser\BracketWatcher($p);
        $p = new Parser\ShortArray($p);

        return $p;
    }

    function testBackwardShortArray()
    {
        $parser = $this->getParser(50300);

        $in = '<?php []; if (0) {} []; ${""}[];';
        $out = '<?php array(); if (0) {} array(); ${""}[];';

        $this->assertSame( $out, $parser->parse($in) );
        $this->assertSame( array(), $parser->getErrors() );
    }

    function testForwardShortArray()
    {
        $parser = $this->getParser(50400);

        $in  = '<?php function (array $a = array(2 => array())) {array(array());};';
        $out = '<?php function (array $a = [2 => []]) {[[]];};';

        $this->assertSame( $out, $parser->parse($in) );
        $this->assertSame( array(), $parser->getErrors() );
    }
}
