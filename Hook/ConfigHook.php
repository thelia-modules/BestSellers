<?php

namespace BestSellers\Hook;

use BestSellers\BestSellers;
use BestSellers\Form\Configuration;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Form\TheliaFormFactory;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\ParserContext;
use Thelia\Core\Template\Parser\ParserResolver;

class ConfigHook extends BaseHook
{
    public function __construct(
        private readonly TheliaFormFactory $formFactory,
        private readonly ParserContext $parserContext,
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
        // A form sent back with errors comes again with what was entered; otherwise, the saved configuration.
        $form = $this->parserContext->getForm(Configuration::getName(), Configuration::class, FormType::class)
            ?? $this->formFactory->createForm(Configuration::getName(), FormType::class, $this->savedConfiguration());

        $event->add($this->addCSS('assets/css/bestsellers-config.css'));
        $event->add(
            $this->render('BestSellers/module-config.html.twig', [
                'form' => $form->createView()->getView(),
            ])
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function savedConfiguration(): array
    {
        return [
            'order_types' => array_values(array_filter(array_map('intval', explode(',', (string) BestSellers::getConfigValue('order_types'))))),
            'date_type' => (int) BestSellers::getConfigValue('date_type', (string) BestSellers::ALL_SALES),
            'date_range' => BestSellers::getConfigValue('date_range') ?: BestSellers::LAST_3_MONTHS,
            'start_date' => $this->savedDate('start_date'),
            'end_date' => $this->savedDate('end_date'),
            'order_by' => (string) BestSellers::getConfigValue('order_by'),
        ];
    }

    /**
     * A saved date the form can show, or null: an unreadable value leaves the field empty instead of breaking the page.
     */
    private function savedDate(string $name): ?string
    {
        $value = (string) BestSellers::getConfigValue($name);
        $date = \DateTime::createFromFormat('!Y-m-d', $value);

        return false !== $date && $date->format('Y-m-d') === $value ? $value : null;
    }
}
