<?php

namespace Unirgy\Dropship\Model;
use Magento\Framework\App\TemplateTypesInterface;
use Magento\Framework\Mail\Message;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;

class EmailTransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    public function __construct(
        \Magento\Framework\Mail\Template\FactoryInterface $templateFactory,
        \Magento\Framework\Mail\MessageInterface $message,
        \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Mail\TransportInterfaceFactory $mailTransportFactory
    ) {
        parent::__construct($templateFactory, $message, $senderResolver, $objectManager, $mailTransportFactory);
        $this->reset();
    }
    protected function prepareMessage()
    {
        $variables = $this->templateVars;
        if (!empty($variables['_BCC'])) {
            $bcc = $variables['_BCC'];
            if (is_string($bcc)) {
                $bcc = explode(',', $bcc);
            }
            foreach ($bcc as $e) {
                $this->message->addBcc($e);
            }
            unset($this->templateVars['_BCC']);
        }
        if (!empty($variables['_ATTACHMENTS'])) {
            foreach ((array)$variables['_ATTACHMENTS'] as $a) {
                if (is_string($a)) {
                    $a = array('filename'=>$a);
                }
                if (empty($a['content']) && (empty($a['filename']) || !is_readable($a['filename']))) {
                    throw new \Exception('Invalid attachment data: '.print_r($a, 1));
                }
                $this->message->createAttachment(
                    !empty($a['content']) ? $a['content'] : file_get_contents($a['filename']),
                    !empty($a['type']) ? $a['type'] : \Zend_Mime::TYPE_OCTETSTREAM,
                    !empty($a['disposition']) ? $a['disposition'] : \Zend_Mime::DISPOSITION_ATTACHMENT,
                    !empty($a['encoding']) ? $a['encoding'] : \Zend_Mime::ENCODING_BASE64,
                    basename($a['filename'])
                );
            }
            unset($this->templateVars['_ATTACHMENTS']);
        }
        return parent::prepareMessage();
    }
}