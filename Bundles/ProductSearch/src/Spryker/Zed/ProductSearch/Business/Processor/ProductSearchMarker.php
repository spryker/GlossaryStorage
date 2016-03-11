<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductSearch\Business\Processor;

use Spryker\Zed\ProductSearch\Dependency\Facade\ProductSearchToTouchInterface;
use Spryker\Zed\ProductSearch\Persistence\ProductSearchQueryContainerInterface;

class ProductSearchMarker implements ProductSearchMarkerInterface
{

    /**
     * @var \Spryker\Zed\ProductSearch\Dependency\Facade\ProductSearchToTouchInterface
     */
    protected $touchFacade;

    /**
     * @var \Spryker\Zed\ProductSearch\Persistence\ProductSearchQueryContainerInterface
     */
    protected $productSearchQueryContainer;

    /**
     * @param \Spryker\Zed\ProductSearch\Dependency\Facade\ProductSearchToTouchInterface $touchFacade
     * @param \Spryker\Zed\ProductSearch\Persistence\ProductSearchQueryContainerInterface $productSearchQuery
     */
    public function __construct(ProductSearchToTouchInterface $touchFacade, ProductSearchQueryContainerInterface $productSearchQuery)
    {
        $this->touchFacade = $touchFacade;
        $this->productSearchQueryContainer = $productSearchQuery;
    }

    /**
     * @param int $idProduct
     * @param \Generated\Shared\Transfer\LocaleTransfer[] $localeCollection
     *
     * @return void
     */
    public function activateProductSearch($idProduct, array $localeCollection)
    {
        $this->markProductSearchable($idProduct, $localeCollection, true);

        $this->touchFacade->touchActive('searchableProduct', $idProduct);
    }

    /**
     * @param int $idProduct
     * @param \Generated\Shared\Transfer\LocaleTransfer[] $localeCollection
     *
     * @return void
     */
    public function deactivateProductSearch($idProduct, array $localeCollection)
    {
        $this->markProductSearchable($idProduct, $localeCollection, false);

        $this->touchFacade->touchInactive('searchableProduct', $idProduct);
    }

    /**
     * @param int $idProduct
     * @param \Generated\Shared\Transfer\LocaleTransfer[] $localeCollection
     * @param bool $searchable
     *
     * @return void
     */
    protected function markProductSearchable($idProduct, array $localeCollection, $searchable)
    {
        foreach ($localeCollection as $code => $localeTransfer) {
            $searchableProduct = $this->productSearchQueryContainer
                ->queryByProductAndLocale($idProduct, $localeTransfer->getIdLocale())
                ->findOneOrCreate();

            $searchableProduct->setIsSearchable($searchable);
            $searchableProduct->save();
        }
    }

}
