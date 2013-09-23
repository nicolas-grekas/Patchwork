<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class ShortOpenEchoTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p->targetPhpVersionId = 50300;

        $p = new Parser\ShortOpenEcho($p);

        return $p;
    }

    function testShortOpenEcho()
    {
        $parser = $this->getParser();

        $in = "<?=4;";
        $out = "<?php echo 4;";

        $this->assertSame( $out, $parser->parse($in) );
        $this->assertSame( array(), $parser->getErrors() );

        ini_set('short_open_tag', false);

        $parser = $this->getParser();

        $in = "<?=4;";
        $out = "<?php echo 4;";

        $this->assertSame( $out, $parser->parse($in) );
        $this->assertSame( array(), $parser->getErrors() );
    }
}
