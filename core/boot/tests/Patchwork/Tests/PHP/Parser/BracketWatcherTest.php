<?php

namespace Patchwork\Tests\PHP;

class BracketWatcherTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser()
    {
        return new \Patchwork_PHP_Parser_BracketWatcher;
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
                    'parser' => 'Patchwork_PHP_Parser_BracketWatcher',
                )
            ),

            $parser->getErrors()
        );
    }
}
