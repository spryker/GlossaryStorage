<?php

namespace SprykerFeature\Zed\Discount\Communication\Form;

use SprykerFeature\Zed\Discount\Persistence\Propel\SpyDiscountVoucherPoolCategory;
use SprykerFeature\Zed\Discount\Persistence\Propel\SpyDiscountVoucherPoolCategoryQuery;
use SprykerFeature\Zed\Gui\Communication\Form\AbstractForm;
use Symfony\Component\Validator\Constraints\NotBlank;

class PoolCategoryForm extends AbstractForm
{
    const COL_NAME = 'name';

    /**
     * @var SpyDiscountVoucherPoolCategory $poolCategory
     */
    protected $poolCategory;

    /**
     * @param SpyDiscountVoucherPoolCategoryQuery $poolCategoryQuery
     * @param int $idPoolCategory
     */
    public function __construct(SpyDiscountVoucherPoolCategoryQuery $poolCategoryQuery, $idPoolCategory)
    {
        $this->poolCategory = $poolCategoryQuery->findOneByIdDiscountVoucherPoolCategory($idPoolCategory);
    }

    /**
     * Prepares form
     *
     * @return $this
     */
    protected function buildFormFields()
    {
        $this
            ->addText(self::COL_NAME, [
                'constraints' => [
                    new NotBlank(),
                ],
            ])
        ;
    }

    /**
     * @return array
     */
    protected function populateFormFields()
    {
        $name = ($this->poolCategory instanceof SpyDiscountVoucherPoolCategory)
            ? $this->poolCategory->getName()
            : ''
        ;

        return [
            self::COL_NAME => $name,
        ];
    }

}