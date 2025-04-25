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

namespace Dss\Opinion\Setup\Patch\Data;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class AddCanGiveOpinionAttribute implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * Constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param SetFactory $attributeSetFactory
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        protected ModuleDataSetupInterface $moduleDataSetup,
        protected CustomerSetupFactory $customerSetupFactory,
        protected SetFactory $attributeSetFactory,
        protected CustomerCollectionFactory $customerCollectionFactory,
        protected CustomerRepositoryInterface $customerRepository
    ) {
    }

    /**
     * Apply the data patch.
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $customerSetup->addAttribute(
            Customer::ENTITY,
            'can_give_opinion',
            [
                'label' => 'Can Give Opinion?',
                'input' => 'select',
                'type' => 'int',
                'default' => 1,
                'position' => 1000,
                'visible' => true,
                'system' => false,
                'required' => false,
                'user_defined' => true,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => true,
                'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
            ]
        );

        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'can_give_opinion');
        $attribute->addData([
            'used_in_forms' => [
                'adminhtml_customer',
            ]
        ]);

        $attribute->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId
        ]);
        $attribute->save();

        $customerCollection = $this->customerCollectionFactory->create();

        foreach ($customerCollection as $customerModel) {
            $customer = $this->customerRepository->getById((int)$customerModel->getId());
            $customer->setCustomAttribute('can_give_opinion', 1);
            $this->customerRepository->save($customer);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Revert the patch (remove the 'can_give_opinion' attribute).
     *
     * @return void
     */
    public function revert(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerSetup->removeAttribute(Customer::ENTITY, 'can_give_opinion');

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Get any patch aliases.
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Get patch dependencies.
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
