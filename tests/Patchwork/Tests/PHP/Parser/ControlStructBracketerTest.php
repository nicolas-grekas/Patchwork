<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class ControlStructBracketerTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;
        $p = new Parser\BracketWatcher($p);
        new Parser\ControlStructBracketer($p);

        return $p;
    }

    function testParse()
    {
        $parser = $this->getParser();

        $in = <<<EOPHP
<?php
if (0) ;
if (1) {}
if (2): endif;
if (3) if (1) ; else ; else if (2) ;
if (4) do ; while (1); if (2) ; else ;
if (4) while (1) if (2) ; else ;
if (5) switch (1) {}
EOPHP;

        $out = <<<EOPHP
<?php
if (0) {;}
if (1) {}
if (2): endif;
if (3) {if (1) {;} else {;}} else if (2) {;}
if (4) {do {;} while (1);} if (2) {;} else {;}
if (4) {while (1) {if (2) {;} else {;}}}
if (5) {switch (1) {}}
EOPHP;

        $this->assertSame( $out, $parser->parse($in) );
        $this->assertSame( array(), $parser->getErrors() );
    }
}
