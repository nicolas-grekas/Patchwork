<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class WorkaroundBug55156Test extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p->targetPhpVersionId = 50300;

        $p = new Parser\Normalizer($p);
        $p = new Parser\StringInfo($p);
        $p = new Parser\WorkaroundBug55156($p);

        return $p;
    }

    function testParse()
    {
        $parser = $this->getParser();

        $in  = '<?php ';
        $out = '<?php {}';

        $this->assertSame( $out, $parser->parse($in) );
        $this->assertSame( array(), $parser->getErrors() );

        $parser = $this->getParser();

        $in  = '<?php namespace abc;';
        $out = '<?php {}namespace abc;{}';

        $this->assertSame( $out, $parser->parse($in) );
        $this->assertSame( array(), $parser->getErrors() );

        $parser = $this->getParser();

        $in  = '<?php namespace abc{}';
        $out = '<?php {}namespace abc{{}}';

        $this->assertSame( $out, $parser->parse($in) );
        $this->assertSame( array(), $parser->getErrors() );
    }
}
