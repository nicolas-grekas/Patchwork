<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class CurlyDollarNormalizerTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p = new Parser\BracketWatcher($p);
        $p = new Parser\CurlyDollarNormalizer($p);

        return $p;
    }

    function testShortOpenEcho()
    {
        $parser = $this->getParser();

        $in  = '<?php "$a->b"; "$a[b]"; "${a}"; "${a[b]}"; "${a.b}"';
        $out = '<?php "$a->b"; "$a[b]"; "{$a}"; "{$a[b]}"; "{${\'a\'.b}}"';

        $this->assertSame( $out, $parser->parse($in) );
        $this->assertSame( array(), $parser->getErrors() );
    }
}
