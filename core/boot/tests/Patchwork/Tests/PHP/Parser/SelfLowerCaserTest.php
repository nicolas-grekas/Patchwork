<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class SelfLowerCaserTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $this->targetPhpVersionId = 50400;

        $p = new Parser\SelfLowerCaser($p);

        return $p;
    }

    function testShortOpenEcho()
    {
        $parser = $this->getParser();

        $in  = '<?php SelF; ParenT;';

        if (PHP_VERSION_ID >= 50500) $out = $in;
        else $out = '<?php self; parent;';

        $this->assertSame( $out, $parser->parse($in) );
        $this->assertSame( array(), $parser->getErrors() );
    }
}
