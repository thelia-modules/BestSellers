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

namespace BestSellers\Controller;

use BestSellers\BestSellers;
use BestSellers\Service\BestSellersStatsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Model\OrderQuery;
use Twig\Environment;

class BestSellersController extends BaseAdminController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly BestSellersStatsService $statsService,
    ) {
    }

    public function listAction(Request $request): Response
    {
        if (null !== $response = $this->checkAuth(['admin.module'], [], AccessManager::VIEW)) {
            return $response;
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $order = (string) $request->query->get('order', 'sold_count_reverse');
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');

        [$minYear, $maxYear] = $this->resolveYearBounds();

        if (!$startDate) {
            $startDate = $minYear.'-01-01';
        }
        if (!$endDate) {
            $endDate = (new \DateTime())->format('Y-m-d');
        }

        $period = $this->statsService->resolvePeriod($startDate, $endDate);

        $result = $this->statsService->bestSellers(
            $period['start'],
            $period['end'],
            $order,
            $page,
            20,
            $request->getLocale(),
        );

        $context = [
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'pageCount' => (int) ceil($result['total'] / 20),
            'order' => $order,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'minYear' => $minYear,
            'maxYear' => $maxYear,
        ];

        return new Response(
            $this->twig->render('@BestSellersModule/backOffice/default-twig/best-sellers.html.twig', $context)
        );
    }

    /**
     * @return array{0:int, 1:int}
     */
    private function resolveYearBounds(): array
    {
        $maxYear = (int) (new \DateTime())->format('Y');
        $minYear = $maxYear;

        $firstOrder = OrderQuery::create()->orderByCreatedAt()->findOne();
        if ($firstOrder !== null) {
            $minYear = (int) $firstOrder->getCreatedAt()->format('Y');
        }

        return [$minYear, $maxYear];
    }
}
