<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Spryker\Zed\Application\Communication;

use Spryker\Zed\Application\Communication\Bootstrap\Extension\AfterBootExtension;
use Spryker\Zed\Application\Communication\Bootstrap\Extension\BeforeBootExtension;
use Spryker\Zed\Application\Communication\Bootstrap\Extension\GlobalTemplateVariablesExtension;
use Spryker\Shared\Application\Communication\Application;
use Spryker\Shared\Application\Communication\Bootstrap;
use Spryker\Zed\Application\Communication\Bootstrap\Extension\RouterExtension;
use Spryker\Zed\Application\Communication\Bootstrap\Extension\ServiceProviderExtension;
use Spryker\Zed\Application\Communication\Bootstrap\Extension\TwigExtension;
use Spryker\Zed\Application\Communication\Plugin\Pimple;

class ZedBootstrap extends Bootstrap
{

    public function __construct()
    {
        parent::__construct($this->getBaseApplication());

        $this->addBeforeBootExtension(
            $this->getBeforeBootExtension()
        );

        $this->addAfterBootExtension(
            $this->getAfterBootExtension()
        );

        $this->addServiceProviderExtension(
            $this->getServiceProviderExtension()
        );

        $this->addRouterExtension(
            $this->getRouterExtension()
        );

        $this->addTwigExtension(
            $this->getTwigExtension()
        );

        $this->addGlobalTemplateVariableExtension(
            $this->getGlobalTemplateVariablesExtension()
        );
    }

    /**
     * @return Application
     */
    protected function getBaseApplication()
    {
        $application = new Application();

        $this->unsetSilexExceptionHandler($application);

        Pimple::setApplication($application);

        return $application;
    }

    /**
     * @param Application $application
     *
     * @return void
     */
    private function unsetSilexExceptionHandler(Application $application)
    {
        unset($application['exception_handler']);
    }

    /**
     * @return BeforeBootExtension
     */
    protected function getBeforeBootExtension()
    {
        return new BeforeBootExtension();
    }

    /**
     * @return AfterBootExtension
     */
    protected function getAfterBootExtension()
    {
        return new AfterBootExtension();
    }

    /**
     * @return ServiceProviderExtension
     */
    protected function getServiceProviderExtension()
    {
        return new ServiceProviderExtension();
    }

    /**
     * @return RouterExtension
     */
    protected function getRouterExtension()
    {
        return new RouterExtension();
    }

    /**
     * @return TwigExtension
     */
    protected function getTwigExtension()
    {
        return new TwigExtension();
    }

    /**
     * @return GlobalTemplateVariablesExtension
     */
    protected function getGlobalTemplateVariablesExtension()
    {
        return new GlobalTemplateVariablesExtension();
    }

}