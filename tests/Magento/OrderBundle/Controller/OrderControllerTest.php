<?php

namespace Magento\OrderBundle\Controller;

use Magento\OrderBundle\Controller\Api\V1\OrderController;
use PHPUnit\Framework\TestCase;

class OrderControllerTest extends TestCase
{
    /**
     * @covers Magento\OrderBundle\Controller\Api\V1\OrderController::newAction
     */
    public function testNewAction()
    {
        $orderData = [
            "id" => 33,
            "store_id" => 27,
            "lines" => [["line_number" => 1, "sku" => "blue_sock"], ["line_number" => 2, "sku" => "red_sock"]]
        ];
        $requestMock = $this->getMockBuilder("Symfony\Component\HttpFoundation\Request")
            ->disableOriginalConstructor()
            ->getMock();
        $orderEntityMock = $this->getMockBuilder("Magento\OrderBundle\Entity\Order")
            ->getMock();
        $loggerMock = $this->getMockBuilder("Monolog\Logger")
            ->disableOriginalConstructor()
            ->getMock();
        $validatorMock = $this->getMockBuilder("Magento\OrderBundle\Model\Validator")
            ->getMock();
        $queueMock = $this->getMockBuilder("SymfonyBundles\QueueBundle\Service\Queue")
            ->getMock();
        $entityManagerMock = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->setMethods(array('persist', 'flush'))
            ->disableOriginalConstructor()
            ->getMock();
        $repositoryMock = $this->getMockBuilder("Magento\OrderBundle\Repository\OrderRepository")
            ->disableOriginalConstructor()
            ->setMethods(["findOneBy"])
            ->getMock();
        $registryMock = $this->getMockBuilder("Doctrine\Bundle\DoctrineBundle\Registry")
            ->disableOriginalConstructor()
            ->getMock();
        $registryMock->expects($this->any())
            ->method("getRepository")
            ->willReturn($repositoryMock);
        $registryMock->expects($this->any())
            ->method("getManager")
            ->willReturn($entityManagerMock);
        $orderControllerMock = $this->getMockBuilder(OrderController::class)
            ->setConstructorArgs([$orderEntityMock, $loggerMock, $validatorMock, $queueMock])
            ->setMethods(["getDoctrine"])
            ->getMock();
        $orderControllerMock->expects($this->any())
            ->method("getDoctrine")
            ->willReturn($registryMock);
        $requestMock->expects($this->any())
            ->method("get")
            ->with('order')
            ->willReturn($orderData);
        $validatorMock->expects($this->once())
            ->method('validateItems');
        $response = $orderControllerMock->newAction($requestMock);
        $this->assertEquals('Order successfully saved', $response->getContent());
        $this->assertEquals(201, $response->getStatusCode());
    }
}
