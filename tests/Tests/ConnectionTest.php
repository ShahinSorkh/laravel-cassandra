<?php

namespace ShSo\Lacassa\Tests;

use DB;
use ShSo\Lacassa\TestCase;

class ConnectionTest extends TestCase
{

    function testTest()
    {
        $this->assertEquals('DB', DB::class);
    }
}

