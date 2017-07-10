<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Functional\Spryker\Zed\Api\Business\Model\Processor\Post\Action;

use Codeception\TestCase\Test;
use Generated\Shared\Transfer\ApiMetaTransfer;
use Generated\Shared\Transfer\ApiRequestTransfer;
use Generated\Shared\Transfer\ApiResponseTransfer;
use Spryker\Zed\Api\ApiConfig;
use Spryker\Zed\Api\Business\Model\Processor\Post\Action\AddActionPostProcessor;

/**
 * @group Functional
 * @group Spryker
 * @group Zed
 * @group Api
 * @group Business
 * @group Model
 * @group Processor
 * @group Post
 * @group Action
 * @group AddActionPostProcessorTest
 */
class AddActionPostProcessorTest extends Test
{

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @return void
     */
    public function testProcessWithDefaultsPageOne()
    {
        $config = new ApiConfig();
        $processor = new AddActionPostProcessor($config);

        $apiRequestTransfer = new ApiRequestTransfer();
        $apiRequestTransfer->setResource('users');
        $apiRequestTransfer->setResourceAction(ApiConfig::ACTION_CREATE);

        $apiResponseTransfer = new ApiResponseTransfer();
        $apiMetaTransfer = new ApiMetaTransfer();
        $apiMetaTransfer->setResourceId(1);
        $apiResponseTransfer->setMeta($apiMetaTransfer);

        $apiResponseTransfer = $processor->process($apiRequestTransfer, $apiResponseTransfer);
        $this->assertSame(ApiConfig::HTTP_CODE_CREATED, $apiResponseTransfer->getCode());

        $this->assertSame('/api/rest/users/1', $apiResponseTransfer->getMeta()->getSelf());
    }

}