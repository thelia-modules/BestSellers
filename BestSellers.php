<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */

/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */

namespace BestSellers;

use ApiPlatform\Metadata\AsOperationMutator;
use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Thelia\Domain\Catalog\Product\ProductSortProviderInterface;
use Thelia\Module\BaseModule;

class BestSellers extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'bestsellers';

    const GET_BEST_SELLING_PRODUCTS = 'bestsellers.event.get_best_selling_products';

    const BO_MESSAGE_DOMAIN = 'bestsellers.bo.default';

    /* Data cache lifetime in minutes */
    const CACHE_LIFETIME_IN_MINUTES = 1440;

    const LAST_15_DAYS = 'last_15_days';

    const LAST_30_DAYS = 'last_30_days';

    const LAST_3_MONTHS = 'last_3_months';

    const LAST_6_MONTHS = 'last_6_months';

    const LAST_YEAR = 'last_year';

    const THIS_YEAR = 'this_year';

    const ORDER_BY_NUMBER_OF_SALES = 'order_by_number_of_sales';

    const ORDER_BY_SALES_REVENUE = 'order_by_sales_revenue';


    const FIXED_DATE = 1;

    const DATE_RANGE = 2;

    public function postActivation(ConnectionInterface $con = null): void
    {
        self::setConfigValue('order_types', '2,3,4');
    }

    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $excluded = [__DIR__.'/I18n/*', __DIR__.'/Tests/*'];

        // The sort by sales needs a core that reads module sorts and an API Platform that mutates operations.
        if (!interface_exists(ProductSortProviderInterface::class) || !class_exists(AsOperationMutator::class)) {
            $excluded[] = __DIR__.'/Api/*';
            $excluded[] = __DIR__.'/Sort/*';
        }

        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude($excluded)
            ->autowire(true)
            ->autoconfigure(true);
    }
}
