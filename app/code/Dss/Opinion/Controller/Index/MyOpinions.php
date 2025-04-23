<?php

declare(strict_types=1);

/**
 * Digit Software Solutions.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 *
 * @category  Dss
 * @package   Dss_Opinion
 * @author    Extension Team
 * @copyright Copyright (c) 2025 Digit Software Solutions. ( https://digitsoftsol.com )
 */

namespace Dss\Opinion\Controller\Index;

use Dss\Opinion\Model\Config;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;

class MyOpinions implements HttpGetActionInterface
{
    /**
     * Constructor
     *
     * @param PageFactory $pageFactory
     * @param HttpContext $httpContext
     * @param Config $config
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        protected PageFactory $pageFactory,
        protected HttpContext $httpContext,
        protected Config $config,
        protected RedirectFactory $resultRedirectFactory,
        protected ManagerInterface $messageManager
    ) {
    }

    /**
     * Execute method to render the 'My Opinions' page for logged-in customers
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        if (!$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('customer/account/login');
        }

        if (!$this->config->isProductOpinionEnabled()) {
            $this->messageManager->addErrorMessage(__('The product opinion feature is currently disabled.'));
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('customer/account');
        }

        return $this->pageFactory->create();
    }
}
