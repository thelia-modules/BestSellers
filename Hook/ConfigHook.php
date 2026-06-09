<?php

namespace BestSellers\Hook;

use BestSellers\Form\Configuration;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\Parser\ParserResolver;
use Thelia\Core\Form\TheliaFormFactory;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatusQuery;

class ConfigHook extends BaseHook
{
    public function __construct(
        private readonly TheliaFormFactory $formFactory,
        ?EventDispatcherInterface $dispatcher = null,
        ?ParserResolver $parserResolver = null,
    ) {
        parent::__construct($dispatcher, $parserResolver);
    }

    public static function getSubscribedHooks(): array
    {
        return [
            'module.configuration' => [
                ['type' => 'back', 'method' => 'onModuleConfiguration'],
            ],
        ];
    }

    public function onModuleConfiguration(HookRenderEvent $event): void
    {
        $request = $this->getRequest();
        $locale = $request?->getLocale() ?? 'en_US';

        $form = $this->formFactory->createForm(Configuration::getName());

        $event->add(
            $this->render('BestSellers/module-config.html.twig', [
                'form' => $form->createView()->getView(),
                'order_statuses' => $this->orderStatuses($locale),
            ])
        );
    }

    /**
     * @return list<array{id:int, code:string, color:string, title:string, position:int, orders:int}>
     */
    private function orderStatuses(string $locale): array
    {
        $statuses = OrderStatusQuery::create()
            ->orderByPosition()
            ->find();

        $rows = [];
        foreach ($statuses as $status) {
            $status->setLocale($locale);
            $rows[] = [
                'id' => (int) $status->getId(),
                'code' => (string) $status->getCode(),
                'color' => (string) $status->getColor(),
                'title' => (string) $status->getTitle(),
                'position' => (int) $status->getPosition(),
                'orders' => OrderQuery::create()->filterByStatusId($status->getId())->count(),
            ];
        }

        return $rows;
    }
}
