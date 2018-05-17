<?php

namespace C3UsefulCommands\Commands;

use Shopware\Commands\ShopwareCommand;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallUninstallPluginsCommand
 *
 * @package C3UsefulCommands\Commands
 */
class InstallUninstallPluginsCommand extends ShopwareCommand
{
    /**
     * Filename used if not overridden - from base of installation
     */
    const DEFAULT_FILENAME_INSTALL = 'install.txt';
    /**
     * Filename used if not overridden - from base of installation
     */
    const DEFAULT_FILENAME_UNINSTALL = 'uninstall.txt';
    /**
     * Activation defualts to no - can be turned on by --activate switch
     */
    const DEFAULT_ACTIVATION = 0;

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    protected $modelManager;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('c3:plugins:ensure')
            ->setDescription('Install/Uninstall plugins as per provided files')
            ->setHelp('Install and Uninstall files are expected to have plugin names one per line')
            ->addOption(
                'installFile',
                'i',
                InputOption::VALUE_REQUIRED,
                'Filename for list of plugins to ensure are installed. Defaults to checking ' . self::DEFAULT_FILENAME_INSTALL
            )->addOption(
                'uninstallFile',
                'u',
                InputOption::VALUE_REQUIRED,
                'Filename for list of plugins to insure are uninstalled. Defaults to checking ' . self::DEFAULT_FILENAME_UNINSTALL
            )->addOption(
                'activate',
                'a',
                InputOption::VALUE_NONE,
                'Whether plugins on install list should be automatically activated. Defaults to N'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $installFilename = $input->getOption('installFile') ?: self::DEFAULT_FILENAME_INSTALL;
        $uninstallFilename = $input->getOption('uninstallFile') ?: self::DEFAULT_FILENAME_UNINSTALL;
        $activate = ($input->getOption('activate') ?: self::DEFAULT_ACTIVATION) != 0;

        $pluginsToInstall = $this->getListFromFilename($installFilename);
        $pluginsToUninstall = $this->getListFromFilename($uninstallFilename);

        foreach ($pluginsToInstall as $plugin) {
            $this->ensureInstalled($plugin, $output, $activate);
        }

        foreach ($pluginsToUninstall as $plugin) {
            $this->ensureUninstalled($plugin, $output);
        }

        return 0;
    }

    /**
     * Get list of lines from provided filename (relative to base install)
     * If cannot find file, returns empty array
     *
     * @param string $filename
     *
     * @return array
     */
    protected function getListFromFilename($filename)
    {
        $base = $this->container->getParameter('shopware.app.rootdir');
        $fullFilename = $base . $filename;
        if (!file_exists($fullFilename)) {
            return [];
        }

        // File exists, so read into array
        $readList = explode("\n", file_get_contents($fullFilename));
        $list = [];
        foreach ($readList as $entry) {
            if ($entry != '') {
                $list[] = $entry;
            }
        }

        return $list;
    }

    /**
     * Ensure that the plugin with the given name is installed
     * (and optionally also activated on install)
     *
     * @param string $plugin
     * @param OutputInterface $output
     * @param bool $activate
     *
     * @return int
     */
    protected function ensureInstalled($plugin, $output, $activate)
    {
        $command = $this->getApplication()->find('sw:plugin:install');

        $arguments = array(
            'command' => 'sw:plugin:install',
            'plugin'  => $plugin,
        );
        if ($activate) {
            $arguments['--activate'] = true;
        }

        $installInput = new ArrayInput($arguments);
        $returnCode = $command->run($installInput, $output);

        return $returnCode;
    }

    /**
     * Ensure that the named plugin is uninstalled
     *
     * @param string $plugin
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function ensureUninstalled($plugin, $output)
    {
        $command = $this->getApplication()->find('sw:plugin:uninstall');

        $arguments = array(
            'command' => 'sw:plugin:uninstall',
            'plugin'  => $plugin,
        );

        $installInput = new ArrayInput($arguments);
        $returnCode = $command->run($installInput, $output);

        return $returnCode;
    }
}
