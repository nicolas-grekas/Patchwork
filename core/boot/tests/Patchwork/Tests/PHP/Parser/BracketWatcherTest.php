<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class BracketWatcherTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser()
    {
        return new Parser\BracketWatcher;
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
                    'type' => 512,
                    'message' => 'Brackets are not correctly balanced',
                    'line' => 2,
                    'parser' => 'Patchwork\PHP\Parser\BracketWatcher',
                )
            ),

            $parser->getErrors()
        );
    }
}
