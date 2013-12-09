<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class DestructorCatcherTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p = new Parser\Normalizer($p);
        $p = new Parser\BracketWatcher($p);
        $p = new Parser\StringInfo($p);
        $p = new Parser\NamespaceInfo($p);
        $p = new Parser\ScopeInfo($p);
        $p = new Parser\DestructorCatcher($p);

        return $p;
    }

    /**
     * @dataProvider parserProvider
     */
    function testParser($in, $out, $errors = array())
    {
        $parser = $this->getParser();

        $in = $parser->parse('<?php ' . $in);
        $in = substr($in, 6);
        $in = trim(preg_replace('/\s+/', '', $in));
        $out = trim(preg_replace('/\s+/', '', $out));

        $this->assertSame( $out, $in );
        $this->assertSame( $errors, $parser->getErrors() );
    }

    function parserProvider()
    {
        return array(
            array(
                'in'  => 'class a{function __destruct(){}}',
                'out' => <<<'EOPHP'
class a
{
    function __destruct()
    {
        try {
        } catch(\Exception $e) {
            if (empty($e->__destructorException)) {
                $e = array($e, array_slice($e->getTrace(), -1));
                $e[0]->__destructorException = isset($e[1][0]["line"])
                        || ! isset($e[1][0]["class"])
                        || strcasecmp("__destruct", $e[1][0]["function"])
                    ? 1
                    : $e[1][0]["class"];
                $e = $e[0];
            }

            if ( isset($e->__destructorException)
              && __CLASS__ === $e->__destructorException
              && 1 === count(debug_backtrace(2, 2)) ) {
                $e = array($e, set_exception_handler("var_dump"));
                restore_exception_handler();
                if (isset($e[1])) call_user_func($e[1], $e = $e[0]) + exit(255);
            }

            throw $e;
        }
    }
}
EOPHP
            ),
        );
    }
}
