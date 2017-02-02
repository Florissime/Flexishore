<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Rating;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\Rating\EntityFactory;
use Magento\Review\Model\Rating\OptionFactory;

class Save extends AbstractRating
{
    /**
     * @var RatingFactory
     */
    protected $_ratingFactory;

    /**
     * @var OptionFactory
     */
    protected $_ratingOptionFactory;

    public function __construct(Context $context, 
        Registry $frameworkRegistry, 
        EntityFactory $ratingEntityFactory, 
        RatingFactory $modelRatingFactory, 
        OptionFactory $ratingOptionFactory)
    {
        $this->_ratingFactory = $modelRatingFactory;
        $this->_ratingOptionFactory = $ratingOptionFactory;

        parent::__construct($context, $frameworkRegistry, $ratingEntityFactory);
    }

    public function execute()
    {
        $this->_initEnityId();

        if ( $this->getRequest()->getPost() ) {
            try {
                $ratingModel = $this->_ratingFactory->create();

                $stores = $this->getRequest()->getParam('stores');
                $stores[] = 0;
                if (!($rNew = !$this->getRequest()->getParam('id'))) {
                    $ratingModel->load($this->getRequest()->getParam('id'));
                    if (!$ratingModel->getId()) {
                        throw new \Exception(__('Rating with %1 id not found', $this->getRequest()->getParam('id')));
                    }
                } else {
                    $ratingModel->setIsAggregate($this->getRequest()->getParam('is_aggregate'));
                }
                $ratingModel->setRatingCode($this->getRequest()->getParam('rating_code'))
                      ->setRatingCodes($this->getRequest()->getParam('rating_codes'))
                      ->setStores($stores)
                      ->setEntityId($this->_coreRegistry->registry('entityId'))
                      ->save();

                $options = $this->getRequest()->getParam('option_title');

                if( is_array($options) ) {
                    $i = $ratingModel->getIsAggregate() && !$rNew ? 1 : 0;
                    $iStart = $ratingModel->getIsAggregate() ? 1 : 0;
                    foreach( $options as $key => $optionCode ) {
                        $optionModel = $this->_ratingOptionFactory->create();
                        $roNew = true;
                        if( !preg_match("/^add_([0-9]*?)$/", $key) ) {
                            $roNew = false;
                            $optionModel->setId($key);
                        }

                        if (!$roNew || $i>=$iStart) {
                            $optionModel->setCode($optionCode)
                                ->setValue($i)
                                ->setRatingId($ratingModel->getId())
                                ->setPosition($i)
                                ->save();
                        }
                        $i++;
                        if (!$ratingModel->getIsAggregate() && $i>1) {
                            break;
                        }
                    }
                }

                $this->messageManager->addSuccess(__('The rating has been saved.'));
                $this->_session->setRatingData(false);

                $this->_redirect('*/*/');
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_session->setRatingData((array)$this->getRequest()->getPost());
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->_redirect('*/*/');
    }
}
