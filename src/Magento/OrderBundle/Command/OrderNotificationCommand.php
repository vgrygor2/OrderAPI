<?php

namespace Magento\OrderBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\OrderBundle\Model\Mailer;
use SymfonyBundles\QueueBundle\Service\Queue;
use JMS\Serializer\SerializerBuilder;
use Psr\Log\LoggerInterface;

/**
 * Class OrderNotificationCommand
 * @package Magento\OrderBundle\Command
 * @author vgrygor@adobe.com
 */
class OrderNotificationCommand extends Command
{
    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var \JMS\Serializer\Serializer
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * OrderNotificationCommand constructor.
     * @param null $name
     * @param Mailer $mailer
     * @param Queue $queue
     * @param LoggerInterface $logger
     */
    public function __construct(
        $name = null,
        Mailer $mailer,
        Queue $queue,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->queue = $queue;
        $this->serializer = SerializerBuilder::create()->build();
        $this->logger = $logger;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('magento:new-order:notification')
            ->setDescription('New order email notification');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->queue->setName('new_order_email_queue');
        if ($this->queue->count() == 0) {
            $output->writeln('No data in queue');
            return;
        }
        $serializedData = $this->queue->pop();
        try {
            $data = $this->serializer->deserialize($serializedData, 'array', 'json');
            if (is_array($data)) {
                $this->mailer->newOrderNotification($data);
            }
        } catch (\Exception $e) {
            $this->queue->push($serializedData);
            $this->logger->critical($e->getMessage());
        }
    }
}
