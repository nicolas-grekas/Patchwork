<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class CodePathSplitterTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p = new Parser\BracketWatcher($p);
        $p = new Parser\ControlStructBracketer($p);
        $p = new Parser\CaseColonEnforcer($p);
        $p = new Parser\CodePathSplitter($p);

        return $p;
    }

    /**
     * @dataProvider parserProvider
     */
    function testParser($in, $out, $errors = array())
    {
        $parser = $this->getParser();

        $in = $parser->parse("<?php\n" . $in);
        $in = substr($in, 6);

        $this->assertSame( $out, $in );
        $this->assertSame( $errors, $parser->getErrors() );
    }

    function parserProvider()
    {
        $tests = <<<EOPHP

in:
  switch(X){case X;;case X:break;default;;}
out:
  switch(X){case X:
                   ;case X:
                           break;
                                 default:;}

in:
  switch(X){default:break;case X:;}
out:
  switch(X){
            default:break;
                          case X:
                                 ;}

EOPHP;

        $tests = str_replace("\r\n", "\n", $tests);
        $tests = explode("\nin:\n", $tests);
        unset($tests[0]);

        $data = array();

        foreach ($tests as $t)
        {
            $t = explode("\nout:\n", $t);
            $data[] = array(
                'in'  => rtrim($t[0]),
                'out' => rtrim($t[1]),
            );
        }

        return $data;
    }
}
