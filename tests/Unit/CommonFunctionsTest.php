<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../scanner/functions/commonFunctions.php';

class CommonFunctionsTest extends TestCase
{
    public function testGetSiteBeingTested()
    {
        // Case 1: Simple file path
        $this->assertEquals('http://example.com/', getSiteBeingTested('http://example.com/search.php'));

        // Case 2: No file path, just domain
        $this->assertEquals('http://example.com', getSiteBeingTested('http://example.com'));

        // Case 3: Domain with trailing slash
        $this->assertEquals('http://example.com/', getSiteBeingTested('http://example.com/'));

        // Case 4: Deep directory structure with a file
        $this->assertEquals('http://127.0.0.1/testsitewithvulns/', getSiteBeingTested('http://127.0.0.1/testsitewithvulns/search.php'));

        // Case 5: Deep directory structure without a file
        $this->assertEquals('http://example.com/dir/subdir/', getSiteBeingTested('http://example.com/dir/subdir/'));
    }
}
