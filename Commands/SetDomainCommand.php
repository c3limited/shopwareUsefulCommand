<?php

namespace C3UsefulCommands\Commands;

use Shopware\Commands\ShopwareCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetDomainCommand extends ShopwareCommand
{
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
            ->setName('c3:shop-domain:set')
            ->setDescription('Set domain for shop.')
            ->addArgument(
                'domain',
                InputArgument::REQUIRED,
                'Domain to set for given shop'
            )->addOption(
                'shopId',
                'i',
                InputOption::VALUE_OPTIONAL,
                'ShopId to set domain for.'
            )->addOption(
                'shopName',
                null,
                InputOption::VALUE_OPTIONAL,
                'shopName to set domain for.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $shopId = $input->getOption('shopId');
        $shopName = $input->getOption('shopName');

        if ($shopId === null && $shopName === null) {
            $output->writeln("Please specify --shopId or --shopName");
            return 1;
        }

        $domain = $input->getArgument('domain');

        try {
            $shop = $this->getShopFromIdOrName($shopId, $shopName);
        } catch (\Doctrine\ORM\NonUniqueResultException $e) {
            $output->writeln("Error finding shop");
            return 2;
        }

        if ($shop === null) {
            if ($shopId !== null) {
                $output->writeln("Could not find a shop with ID {$shopId}");
            } else {
                $output->writeln("Could not find a shop with name {$shopName}");
            }

            return 3;
        }

        $shop->setHost($domain);

        try {
            $this->modelManager->flush($shop);
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            $output->writeln("Error saving shop");
            return 4;
        }

        $output->writeln("Success updating shop host");
        return 0;
    }

    /**
     * Get shop by ID or return null on failure
     *
     * @param int|null $shopId
     * @param string|null $shopName
     *
     * @return \Shopware\Models\Shop\Shop|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getShopFromIdOrName($shopId, $shopName)
    {
        $this->modelManager = $this->container->get('models');

        /** @var $repository \Shopware\Models\Shop\Repository */
        $repository = $this->modelManager->getRepository(\Shopware\Models\Shop\Shop::class);
        $builder = $repository->getQueryBuilder();
        if ($shopId !== null) {
            $builder->andWhere('shop.id=:shopId');
            $builder->setParameter('shopId', $shopId);
        } else {
            $builder->andWhere('shop.name=:shopName');
            $builder->setParameter('shopName', $shopName);
        }
        $shop = $builder->getQuery()->getOneOrNullResult();

        return $shop;
    }
}
