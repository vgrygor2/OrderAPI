<?php

namespace Magento\OrderBundle\Model;

use Magento\OrderBundle\Model\Validator;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /**
     * @var Validator
     */
    private $validator;

    protected function setUp()
    {
        $this->validator = new Validator();
    }

    /**
     * @covers \Magento\OrderBundle\Model\Validator::validate
     */
    public function testValidate()
    {
        $data = [];
        $this->assertEquals(
            ['message' => 'Invalid data format.', 'code' => Response::HTTP_BAD_REQUEST],
            $this->validator->validate($data));
        $data = ['order_id' => 1, 'store_id' => 2];
        $paramList = ['item_id'];
        $this->assertEquals(
            ['message' => 'Invalid data format.', 'code' => Response::HTTP_BAD_REQUEST],
            $this->validator->validate($data, $paramList));
        $paramList = ['store_id', 'order_id'];
        $this->assertFalse($this->validator->validate($data, $paramList));
    }

    /**
     * @covers \Magento\OrderBundle\Model\Validator::validateItems
     */
    public function testValidateItems()
    {
        $data = ['order_id' => 1, 'store_id' => 2];
        $this->assertEquals(
            ['message' => 'Empty order items data.', 'code' => Response::HTTP_BAD_REQUEST],
            $this->validator->validateItems($data));
        $data = [
            'order_id' => 1,
            'store_id' => 2,
            'lines' => [["line_number" => 1, "sku" => "blue_sock"], ["line_number" => 2, "sku" => "red_sock"]]
        ];
        $this->assertFalse($this->validator->validateItems($data));
        $data = [
            'order_id' => 1,
            'store_id' => 2,
            'lines' => [["line_number" => 2, "sku" => "blue_sock"], ["line_number" => 3, "sku" => "red_sock"]]
        ];
        $this->assertEquals(
            ['message' => 'Order items are not sequential', 'code' => Response::HTTP_BAD_REQUEST],
            $this->validator->validateItems($data));
    }
}
