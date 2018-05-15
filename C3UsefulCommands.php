<?php

namespace C3UsefulCommands;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Console\Application;
use C3UsefulCommands\Commands\SetDomainCommand;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Class ShyimProfiler
 * @package ShyimProfiler
 */
class C3UsefulCommands extends Plugin
{
    /**
     * @param ActivateContext $context
     */
    public function activate(ActivateContext $context)
    {
        $context->scheduleClearCache(InstallContext::CACHE_LIST_DEFAULT);
    }

    public function registerCommands(Application $application)
    {
        $application->add(new SetDomainCommand());
    }

}
