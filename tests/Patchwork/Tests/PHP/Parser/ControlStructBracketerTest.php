<?php

namespace Patchwork\Tests\PHP;

class ControlStructBracketerTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser()
    {
        $p = new \Patchwork_PHP_Parser_BracketWatcher;
        new \Patchwork_PHP_Parser_ControlStructBracketer($p);
        return $p;
    }

    function testParse()
    {
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

        $this->assertSame( $out, $this->getParser()->parse($in) );
    }
}
