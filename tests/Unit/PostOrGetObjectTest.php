<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../scanner/classes/PostOrGetObject.php';

class PostOrGetObjectTest extends TestCase
{
    public function testPostOrGetObjectGettersAndSetters()
    {
        $obj = new PostOrGetObject('my_param', 'my_value');

        $this->assertEquals('my_param', $obj->getName());
        $this->assertEquals('my_value', $obj->getValue());

        $obj->setName('new_param');
        $obj->setValue('new_value');

        $this->assertEquals('new_param', $obj->getName());
        $this->assertEquals('new_value', $obj->getValue());
    }
}
