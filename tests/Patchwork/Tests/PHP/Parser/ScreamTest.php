<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class ScreamTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p = new Parser\Scream($p);

        return $p;
    }

    function testShortOpenEcho()
    {
        $parser = $this->getParser();

        $in  = '<?php @abc();';
        $out = '<?php abc();';

        $this->assertSame( $out, $parser->parse($in) );
        $this->assertSame( array(), $parser->getErrors() );
    }
}
