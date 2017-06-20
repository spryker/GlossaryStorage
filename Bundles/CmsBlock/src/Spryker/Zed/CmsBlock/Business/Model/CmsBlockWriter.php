<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\CmsBlock\Business\Model;

use Generated\Shared\Transfer\CmsBlockTransfer;
use Orm\Zed\CmsBlock\Persistence\SpyCmsBlock;
use Spryker\Shared\CmsBlock\CmsBlockConstants;
use Spryker\Zed\CmsBlock\Business\Exception\CmsBlockNotFoundException;
use Spryker\Zed\CmsBlock\Dependency\Facade\CmsBlockToTouchFacadeInterface;
use Spryker\Zed\CmsBlock\Persistence\CmsBlockQueryContainerInterface;
use Spryker\Zed\PropelOrm\Business\Transaction\DatabaseTransactionHandlerTrait;

class CmsBlockWriter implements CmsBlockWriterInterface
{

    use DatabaseTransactionHandlerTrait;

    /**
     * @var \Spryker\Zed\CmsBlock\Persistence\CmsBlockQueryContainerInterface
     */
    protected $cmsBlockQueryContainer;

    /**
     * @var \Spryker\Zed\CmsBlock\Business\Model\CmsBlockMapperInterface
     */
    protected $cmsBlockMapper;

    /**
     * @var \Spryker\Zed\CmsBlock\Business\Model\CmsBlockGlossaryWriterInterface
     */
    protected $cmsBlockGlossaryWriter;

    /**
     * @var \Spryker\Zed\CmsBlock\Business\Model\CmsBlockTemplateManagerInterface
     */
    protected $templateManager;

    /**
     * @var \Spryker\Zed\CmsBlock\Communication\Plugin\CmsBlockUpdatePluginInterface[]
     */
    protected $cmsBlockUpdatePlugins;

    /**
     * @param \Spryker\Zed\CmsBlock\Persistence\CmsBlockQueryContainerInterface $cmsBlockQueryContainer
     * @param \Spryker\Zed\CmsBlock\Business\Model\CmsBlockMapperInterface $cmsBlockMapper
     * @param \Spryker\Zed\CmsBlock\Business\Model\CmsBlockGlossaryWriterInterface $cmsBlockGlossaryWriter
     * @param \Spryker\Zed\CmsBlock\Dependency\Facade\CmsBlockToTouchFacadeInterface $touchFacade
     * @param \Spryker\Zed\CmsBlock\Business\Model\CmsBlockTemplateManagerInterface $cmsBlockTemplateManager
     * @param \Spryker\Zed\CmsBlock\Communication\Plugin\CmsBlockUpdatePluginInterface[] $updatePlugins
     */
    public function __construct(
        CmsBlockQueryContainerInterface $cmsBlockQueryContainer,
        CmsBlockMapperInterface $cmsBlockMapper,
        CmsBlockGlossaryWriterInterface $cmsBlockGlossaryWriter,
        CmsBlockToTouchFacadeInterface $touchFacade,
        CmsBlockTemplateManagerInterface $cmsBlockTemplateManager,
        array $updatePlugins
    ) {
        $this->cmsBlockQueryContainer = $cmsBlockQueryContainer;
        $this->cmsBlockMapper = $cmsBlockMapper;
        $this->cmsBlockGlossaryWriter = $cmsBlockGlossaryWriter;
        $this->touchFacade = $touchFacade;
        $this->templateManager = $cmsBlockTemplateManager;
        $this->cmsBlockUpdatePlugins = $updatePlugins;
    }

    /**
     * @var \Spryker\Zed\CmsBlock\Dependency\Facade\CmsBlockToTouchFacadeInterface
     */
    protected $touchFacade;

    /**
     * @param int $idCmsBlock
     *
     * @return void
     */
    public function activateById($idCmsBlock)
    {
        $this->handleDatabaseTransaction(function () use ($idCmsBlock) {
            $spyCmsBlock = $this->getCmsBlockById($idCmsBlock);
            $spyCmsBlock->setIsActive(true);
            $spyCmsBlock->save();

            $this->touchFacade->touchActive(CmsBlockConstants::RESOURCE_TYPE_CMS_BLOCK, $spyCmsBlock->getIdCmsBlock());
        });
    }

    /**
     * @param int $idCmsBlock
     *
     * @return void
     */
    public function deactivateById($idCmsBlock)
    {
        $this->handleDatabaseTransaction(function () use ($idCmsBlock) {
            $spyCmsBlock = $this->getCmsBlockById($idCmsBlock);
            $spyCmsBlock->setIsActive(false);
            $spyCmsBlock->save();

            $this->touchFacade->touchDeleted(CmsBlockConstants::RESOURCE_TYPE_CMS_BLOCK, $spyCmsBlock->getIdCmsBlock());
        });
    }

    /**
     * @param \Generated\Shared\Transfer\CmsBlockTransfer $cmsBlockTransfer
     *
     * @throws \Spryker\Zed\CmsBlock\Business\Exception\CmsBlockNotFoundException
     *
     * @return \Generated\Shared\Transfer\CmsBlockTransfer
     */
    public function updateCmsBlock(CmsBlockTransfer $cmsBlockTransfer)
    {
        $cmsBlockTransfer->requireIdCmsBlock();

        $spyCmsBlock = $this->getCmsBlockById($cmsBlockTransfer->getIdCmsBlock());
        $this->checkTemplateFileExists($cmsBlockTransfer->getFkTemplate());

        if ($spyCmsBlock === null) {
            throw new CmsBlockNotFoundException(
                sprintf(
                    'CMS Block with id "%d" was not found',
                    $cmsBlockTransfer->getIdCmsBlock()
                )
            );
        }

        $this->handleDatabaseTransaction(function () use ($cmsBlockTransfer, $spyCmsBlock) {
            $this->updateCmsBlockTransaction($cmsBlockTransfer, $spyCmsBlock);
            $this->updateCmsBlockPluginsTransaction($cmsBlockTransfer);
        });

        return $cmsBlockTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\CmsBlockTransfer $cmsBlockTransfer
     *
     * @return \Generated\Shared\Transfer\CmsBlockTransfer
     */
    public function createCmsBlock(CmsBlockTransfer $cmsBlockTransfer)
    {
        $cmsBlockTransfer->requireFkTemplate();

        $this->handleDatabaseTransaction(function () use ($cmsBlockTransfer) {
            $this->createCmsBlockTransaction($cmsBlockTransfer);
            $this->updateCmsBlockPluginsTransaction($cmsBlockTransfer);
        });

        return $cmsBlockTransfer;
    }

    /**
     * @param int $idCmsBlock
     *
     * @throws \Spryker\Zed\CmsBlock\Business\Exception\CmsBlockNotFoundException
     *
     * @return \Orm\Zed\CmsBlock\Persistence\SpyCmsBlock
     */
    protected function getCmsBlockById($idCmsBlock)
    {
        $spyCmsBlock = $this->cmsBlockQueryContainer
            ->queryCmsBlockById($idCmsBlock)
            ->findOne();

        if (!$spyCmsBlock) {
            throw new CmsBlockNotFoundException();
        }

        return $spyCmsBlock;
    }

    /**
     * @param int $idCmsBlockTemplate
     *
     * @return void
     */
    protected function checkTemplateFileExists($idCmsBlockTemplate)
    {
        $templateTransfer = $this->templateManager
            ->getTemplateById($idCmsBlockTemplate);

        $this->templateManager
            ->checkTemplateFileExists($templateTransfer->getTemplatePath());
    }

    /**
     * @param \Generated\Shared\Transfer\CmsBlockTransfer $cmsBlockTransfer
     * @param \Orm\Zed\CmsBlock\Persistence\SpyCmsBlock $spyCmsBlock
     *
     * @return void
     */
    protected function updateCmsBlockTransaction(CmsBlockTransfer $cmsBlockTransfer, SpyCmsBlock $spyCmsBlock)
    {
        if ($spyCmsBlock->getFkTemplate() !== $cmsBlockTransfer->getFkTemplate()) {
            $this->cmsBlockGlossaryWriter->deleteByCmsBlockId($spyCmsBlock->getIdCmsBlock());
        }

        $spyCmsBlock = $this->cmsBlockMapper->mapCmsBlockTransferToEntity($cmsBlockTransfer, $spyCmsBlock);
        $spyCmsBlock->save();

        if ($spyCmsBlock->getIsActive()) {
            $this->touchFacade->touchActive(CmsBlockConstants::RESOURCE_TYPE_CMS_BLOCK, $spyCmsBlock->getIdCmsBlock());
        }
    }

    /**
     * @param \Generated\Shared\Transfer\CmsBlockTransfer $cmsBlockTransfer
     *
     * @return void
     */
    protected function updateCmsBlockPluginsTransaction(CmsBlockTransfer $cmsBlockTransfer)
    {
        foreach ($this->cmsBlockUpdatePlugins as $updatePlugin) {
            $updatePlugin->handleUpdate($cmsBlockTransfer);
        }
    }

    /**
     * @param \Generated\Shared\Transfer\CmsBlockTransfer $cmsBlockTransfer
     *
     * @return void
     */
    protected function createCmsBlockTransaction(CmsBlockTransfer $cmsBlockTransfer)
    {
        $spyCmsBlock = new SpyCmsBlock();
        $spyCmsBlock = $this->cmsBlockMapper->mapCmsBlockTransferToEntity($cmsBlockTransfer, $spyCmsBlock);
        $spyCmsBlock->save();

        if ($spyCmsBlock->getIsActive()) {
            $this->touchFacade->touchActive(CmsBlockConstants::RESOURCE_TYPE_CMS_BLOCK, $spyCmsBlock->getIdCmsBlock());
        }

        $cmsBlockTransfer->setIdCmsBlock($spyCmsBlock->getIdCmsBlock());
    }

}
