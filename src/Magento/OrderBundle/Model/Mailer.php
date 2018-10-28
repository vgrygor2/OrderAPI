<?php

namespace Magento\OrderBundle\Model;

use \Swift_Mailer;
use \Swift_Message;
use \Swift_SmtpTransport;
use Psr\Log\LoggerInterface;

/**
 * Class Mailer
 * @package Magento\OrderBundle\Model
 * @author vgrygor@adobe.com
 */
class Mailer
{
    /**
     * @todo move this into settings
     * @var string
     */
    private $mailerFrom = 'magento@example.com';

    /**
     * @todo move this into settings
     * @var string
     */
    private $mailerTo = 'someemail@example.com';

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * Mailer constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * New order notification email
     *
     * @param array $data
     */
    public function newOrderNotification(array $data)
    {
        try {
            //@TODO use dependency injection instead of direct new object instantiation
            $transport = new Swift_SmtpTransport('mailer.com', 25);
            $mailer = new Swift_Mailer($transport);
            $message = (new Swift_Message('New order'))
                ->setFrom($this->mailerFrom)
                ->setTo($this->mailerTo)
                ->setBody('New order received with id ' . $data['id'], 'text/html');
            $mailer->send($message);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
