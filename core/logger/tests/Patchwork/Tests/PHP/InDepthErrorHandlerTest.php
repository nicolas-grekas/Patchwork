<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\InDepthErrorHandler;

class InDepthErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    function testReset()
    {
        set_error_handler('var_dump', 0);
        $e = error_reporting(0);
        $line = __LINE__ + $notice;
        error_reporting($e);
        restore_error_handler();

        $e = array(
            'type' => E_NOTICE,
            'message' => 'Undefined variable: notice',
            'file' => __FILE__,
            'line' => $line,
        );
        $this->assertSame( $e, IndepthErrorHandler::getLastError() );

        InDepthErrorHandler::resetLastError();
        $this->assertNull( IndepthErrorHandler::getLastError() );
    }

    function testRegister()
    {
        $f = tempnam('/', 'test');
        $this->assertTrue(false !== $f);

        error_reporting(-1);

        $h = new InDepthErrorHandler(null, array('scream' => E_PARSE, 'trace' => 0));
        InDepthErrorHandler::register($h, $f);
        $h = InDepthErrorHandler::getHandler();
        $h->getLogger()->loggedGlobals = array();

        try
        {
            user_error('fake user error', E_USER_ERROR);
            $this->assertFalse( true );
        }
        catch (\Patchwork\PHP\InDepthRecoverableErrorException $e)
        {
            $h->handleUncaughtException($e);
        }

        if (function_exists('xdebug_disable')) xdebug_disable();
        eval("\x01"); // Uncatchable E_COMPILE_WARNING
        if (function_exists('xdebug_disable')) xdebug_enable();

        error_reporting(0);
        @eval('abc'); // Parse error to populate error_get_last()
        InDepthErrorHandler::shutdown();
        error_reporting(-1);

        $e = file_get_contents($f);

        unlink($f);

        $this->assertStringMatchesFormat(
'*** php-error ***
{"_":"1:array:3",
  "time": "%s %dus - %fms - %fms",
  "mem": "%d - %d",
  "data": {"_":"4:array:4",
    "mesg": "Uncaught \\\\Patchwork\\\\PHP\\\\InDepthRecoverableErrorException: fake user error",
    "type": "E_ERROR /home/nikos/patchwork/dumper/tests/Patchwork/Tests/PHP/InDepthErrorHandlerTest.php:43",
    "level": "1/-1",
    "exception": {"_":"8:Patchwork\\\\PHP\\\\InDepthRecoverableErrorException",
      "context": {"_":"9:array:2",
        "f": "' . $f . '",
        "h": {"_":"11:Patchwork\\\\PHP\\\\InDepthErrorHandler",
          "*:loggedErrors": -1,
          "*:screamErrors": 4,
          "*:thrownErrors": 4437,
          "*:scopedErrors": 0,
          "*:tracedErrors": 0,
          "*:logger": {"_":"17:Patchwork\\\\PHP\\\\Logger",
            "lineFormat": "%s",
            "loggedGlobals": [],
            "*:uniqId": %d,
            "*:logStream": {"_":"21:resource:stream",
              "wrapper_type": "plainfile",
              "stream_type": "STDIO",
              "mode": "ab",
              "unread_bytes": 0,
              "seekable": true,
              "uri": "' . $f . '",
              "timed_out": false,
              "blocked": true,
              "eof": false
            },
            "*:prevTime": %f,
            "*:startTime": %f,
            "*:isFirstEvent": true
          },
          "*:loggedTraces": []
        }
      },
      "*:message": "fake user error",
      "*:code": 0,
      "*:file": "' . __FILE__ . '",
      "*:line": 43,
      "*:severity": "E_USER_ERROR",
      "~:hash": "%x"
    }
  }
}
***
[%s] PHP Warning:  Unexpected character in input:  ' . "'\x01'" . ' (ASCII=1) state=0 in ' . __FILE__ . '(52) : eval()\'d code on line 1
*** php-error ***
{"_":"1:array:3",
  "time": "%s %dus - %fms - %fms",
  "mem": "%d - %d",
  "data": {"_":"4:array:3",
    "mesg": "syntax error, unexpected ' . (PHP_VERSION_ID >= 50400 ? 'end of file' : '$end') . '",
    "type": "E_PARSE ' . __FILE__ . '(56) : eval()\'d code:1",
    "level": "4/0"
  }
}
***
',
            $e
        );
    }
}
