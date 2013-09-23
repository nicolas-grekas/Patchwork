<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class BracketWatcherTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;
        $p = new Parser\BracketWatcher($p);

        return $p;
    }

    function testParse()
    {
        $parser = $this->getParser();

        $in = <<<EOPHP
<?php
(]
EOPHP;

        $out = <<<EOPHP
<?php
(]
EOPHP;

        $this->assertSame( $out, $parser->parse($in) );

        $this->assertSame(
            array(
                array(
                    'type' => E_USER_WARNING,
                    'message' => 'Brackets are not correctly balanced',
                    'line' => 2,
                    'parser' => 'Patchwork\PHP\Parser\BracketWatcher',
                )
            ),

            $parser->getErrors()
        );
    }
}
