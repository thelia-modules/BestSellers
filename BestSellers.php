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

use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Thelia\Module\BaseModule;

class BestSellers extends BaseModule
{
    /** @var string */
    public const DOMAIN_NAME = 'bestsellers';

    public const GET_BEST_SELLING_PRODUCTS = 'bestsellers.event.get_best_selling_products';

    public const BO_MESSAGE_DOMAIN = 'bestsellers.bo.default';

    /* Data cache lifetime in minutes */
    public const CACHE_LIFETIME_IN_MINUTES = 1440;

    public const LAST_15_DAYS = 'last_15_days';

    public const LAST_30_DAYS = 'last_30_days';

    public const LAST_3_MONTHS = 'last_3_months';

    public const LAST_6_MONTHS = 'last_6_months';

    public const LAST_YEAR = 'last_year';

    public const THIS_YEAR = 'this_year';

    public const ORDER_BY_NUMBER_OF_SALES = 'order_by_number_of_sales';

    public const ORDER_BY_SALES_REVENUE = 'order_by_sales_revenue';


    public const FIXED_DATE = 1;

    public const DATE_RANGE = 2;

    public function postActivation(ConnectionInterface $con = null): void
    {
        self::setConfigValue('order_types', '2,3,4');
    }

    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([__DIR__.'/I18n/*'])
            ->autowire()
            ->autoconfigure();
    }
}
