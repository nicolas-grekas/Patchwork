<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Logger;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    function testLogger()
    {
        $mem = fopen('php://memory', 'rb+');

        $e = array(
            'type' => E_USER_ERROR,
            'message' => 'Fake user error',
            'file' => 'fake',
            'line' => 1,
            'context' => new \Patchwork\PHP\InDepthRecoverableErrorException,
            'trace' => array(
                array('function' => 'fake-func2'),
                array('function' => 'fake-func1'),
            ),
        );

        $l = new Logger($mem, 1);
        $l->loggedGlobals = array();

        $l->logError($e, 1, 0, 2);

        $e = new \Exception('Abc');

        $l->log('x-test', $e);
        $l->log('x-test', $e);

        fseek($mem, 0);
        $l = stream_get_contents($mem);
        fclose($mem);

        $this->assertStringMatchesFormat(
'*** php-error ***
{"_":"1:array:3",
  "time": "1970-01-01T00:00:%d+00:00 000000us - 1000.000ms - 1000.000ms",
  "mem": "%d - %d",
  "data": {"_":"4:array:4",
    "mesg": "Fake user error",
    "type": "E_USER_ERROR fake:1",
    "context": {"_":"7:Patchwork\\\\PHP\\\\InDepthRecoverableErrorException",
      "*:message": "",
      "*:code": 0,
      "*:file": "' . __FILE__ . '",
      "*:line": 18,
      "*:severity": "E_ERROR",
      "~:hash": "%x"
    },
    "trace": {"_":"14:array:1",
      "0": {"_":"15:array:1",
        "call": "fake-func1()"
      }
    }
  }
}
***
*** x-test ***
%a
***
*** x-test ***
{"_":"1:array:3",
  "time": "%Sms",
  "mem": "%d - %d",
  "data": {"_":"4:Exception",
    "*:message": "Abc",
    "*:code": 0,
    "*:file": "' . __FILE__ . '",
    "*:line": 30,
    "Exception:trace": {"_":"9:array:1",
      "seeHash": "%x"
    }
  }
}
***
',
            $l
        );
    }
}
