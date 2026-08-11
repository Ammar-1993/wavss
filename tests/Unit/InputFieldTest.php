<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../scanner/classes/InputField.php';

class InputFieldTest extends TestCase
{
    public function testInputFieldGettersAndSetters()
    {
        $input = new InputField('user_id', 'username', 'form1', 'loginForm', 'admin', 'text', 1);

        $this->assertEquals('user_id', $input->getId());
        $this->assertEquals('username', $input->getName());
        $this->assertEquals('form1', $input->getIdOfForm());
        $this->assertEquals('loginForm', $input->getNameOfForm());
        $this->assertEquals('admin', $input->getValue());
        $this->assertEquals('text', $input->getType());
        $this->assertEquals(1, $input->getFormNum());

        $input->setId('pass_id');
        $input->setName('password');
        $input->setIdOfForm('form2');
        $input->setNameOfForm('registerForm');
        $input->setValue('secret');
        $input->setType('password');
        $input->setFormNum(2);

        $this->assertEquals('pass_id', $input->getId());
        $this->assertEquals('password', $input->getName());
        $this->assertEquals('form2', $input->getIdOfForm());
        $this->assertEquals('registerForm', $input->getNameOfForm());
        $this->assertEquals('secret', $input->getValue());
        $this->assertEquals('password', $input->getType());
        $this->assertEquals(2, $input->getFormNum());
    }
}
