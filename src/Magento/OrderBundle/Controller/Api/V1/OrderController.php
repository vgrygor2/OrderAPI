<?php

namespace Magento\OrderBundle\Controller\Api\V1;

use Magento\OrderBundle\Entity\Order as OrderEntity;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\Serializer\SerializerBuilder;
use Psr\Log\LoggerInterface;
use Magento\OrderBundle\Model\Validator;
use SymfonyBundles\QueueBundle\Service\Queue;

/**
 * Class OrderController
 * @package Magento\OrderBundle\Controller\Api\V1
 * @author vgrygor@adobe.com
 */
class OrderController extends FOSRestController
{
    /**
     * @var \JMS\Serializer\Serializer
     */
    private $serializer;

    /**
     * @var OrderEntity
     */
    private $orderEntity;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var Queue
     */
    private $queue;

    /**
     * OrderController constructor.
     * @param OrderEntity $order
     * @param LoggerInterface $logger
     * @param Validator $validator
     * @param Queue $queue
     */
    public function __construct(
        OrderEntity $order,
        LoggerInterface $logger,
        Validator $validator,
        Queue $queue
    ) {
        $this->serializer = SerializerBuilder::create()->build();
        $this->orderEntity = $order;
        $this->logger = $logger;
        $this->validator = $validator;
        $this->queue = $queue;
    }

    /**
     * @Rest\Post("/orders", requirements={"_format"="json"})
     *
     * @param Request $request
     * @return Response
     */
    public function newAction(Request $request)
    {
        $orderData = $request->get('order');
        $errors = $this->validator->validateItems($orderData);
        if ($errors) {
            return new Response($errors['message'], $errors['code']);
        }

        $order = $this->getDoctrine()
            ->getRepository('MagentoOrderBundle:Order')
            ->findOneBy(array('order_id' => $orderData['id'], 'store_id' => $orderData['store_id']));

        if ($order) {
            return new Response(
                sprintf('Order with id %s in store %s already exists', $orderData['id'], $orderData['store_id']),
                Response::HTTP_BAD_REQUEST);
        }

        $entityManager = $this->getDoctrine()->getManager();
        try {
            $orderEntity = $this->orderEntity;
            $orderEntity->setOrderId($orderData['id']);
            $orderEntity->setStoreId($orderData['store_id']);
            $orderEntity->setItems(($this->serializer->serialize($orderData['lines'], 'json')));
            $entityManager->persist($orderEntity);
            $entityManager->flush();
            $this->queue->setName('new_order_email_queue');
            $this->queue->push(($this->serializer->serialize($orderData, 'json')));
        } catch (\Exception $e) {
            $this->logger->critical($e->getTraceAsString());
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response('Order successfully saved', Response::HTTP_CREATED);
    }

    /**
     * @Rest\Post("/store/orders")
     *
     * @param Request $request
     * @return Response
     */
    public function getAction(Request $request)
    {
        $store = $request->get('store');
        $errors = $this->validator->validate($store, array('store_id'));
        if ($errors) {
            return new Response($errors['message'], $errors['code']);
        }
        $searchCriteria = ['store_id' => $store['store_id']];
        if (isset($store['order_ids'])) {
            $searchCriteria['order_id'] = $store['order_ids'];
        }
        $orders = [];
        $repository = $this->getDoctrine()->getRepository('MagentoOrderBundle:Order');
        $result = $repository->findBy($searchCriteria);

        if ($result && is_array($result)) {
            foreach ($result as $order) {
                $orders[]['order_id'] = $order->getOrderId();
                $orders[]['store_id'] = $order->getStoreId();
                $orders[]['order_items'] = $order->getItems();
            }
        }
        if ($orders) {
            return new Response($this->serializer->serialize($orders, 'json'), Response::HTTP_OK);
        }
        return new Response('No data found.', Response::HTTP_NO_CONTENT);
    }
}
