<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../scanner/classes/Form.php';

class FormTest extends TestCase
{
    public function testFormGettersAndSetters()
    {
        $form = new Form('form1', 'loginForm', 'POST', '/login.php', 1);

        $this->assertEquals('form1', $form->getId());
        $this->assertEquals('loginForm', $form->getName());
        $this->assertEquals('POST', $form->getMethod());
        $this->assertEquals('/login.php', $form->getAction());
        $this->assertEquals(1, $form->getFormNum());

        $form->setId('form2');
        $form->setName('registerForm');
        $form->setMethod('GET');
        $form->setAction('/register.php');
        $form->setFormNum(2);

        $this->assertEquals('form2', $form->getId());
        $this->assertEquals('registerForm', $form->getName());
        $this->assertEquals('GET', $form->getMethod());
        $this->assertEquals('/register.php', $form->getAction());
        $this->assertEquals(2, $form->getFormNum());
    }
}
