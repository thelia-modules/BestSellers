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

namespace BestSellers\Form;

use BestSellers\BestSellers;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\Count;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;
use Thelia\Model\OrderStatusQuery;

/**
 * The values come from the data the form is created with (see ConfigHook), never from a field
 * default: a form shown again after an error keeps what the administrator entered.
 */
class Configuration extends BaseForm
{
    protected function buildForm(): void
    {
        $form = $this->formBuilder;
        $translator = Translator::getInstance();

        $statuses = $this->orderStatuses();

        $form->add('order_types', ChoiceType::class, [
            'choices' => array_keys($statuses),
            'choice_label' => static fn (int $statusId): string => $statuses[$statusId]['title'],
            'choice_attr' => static fn (int $statusId): array => ['data-color' => $statuses[$statusId]['color']],
            'choice_translation_domain' => false,
            'multiple' => true,
            'expanded' => true,
            'constraints' => [
                new Count(
                    min: 1,
                    minMessage: $translator->trans('Choose at least one order status.', [], BestSellers::DOMAIN_NAME),
                ),
            ],
        ]);

        $form->add('date_type', ChoiceType::class, [
            'choices' => [
                'All sales' => BestSellers::ALL_SALES,
                'Date range' => BestSellers::DATE_RANGE,
                'Fixed dates' => BestSellers::FIXED_DATE,
            ],
            'choice_translation_domain' => false,
            'expanded' => true,
            'required' => true,
        ]);

        $form->add('date_range', ChoiceType::class, [
            'choices' => [
                $translator->trans('Last 15 days', [], BestSellers::DOMAIN_NAME) => BestSellers::LAST_15_DAYS,
                $translator->trans('Last 30 days', [], BestSellers::DOMAIN_NAME) => BestSellers::LAST_30_DAYS,
                $translator->trans('The last 3 months', [], BestSellers::DOMAIN_NAME) => BestSellers::LAST_3_MONTHS,
                $translator->trans('The last 6 months', [], BestSellers::DOMAIN_NAME) => BestSellers::LAST_6_MONTHS,
                $translator->trans('The last 12 months', [], BestSellers::DOMAIN_NAME) => BestSellers::LAST_YEAR,
                $translator->trans('Since the beginning of the year', [], BestSellers::DOMAIN_NAME) => BestSellers::THIS_YEAR,
            ],
            'required' => true,
        ]);

        // Dates as Y-m-d strings: a form shown again after an error keeps them (objects are dropped from the session).
        $form->add('start_date', DateType::class, [
            'required' => false,
            'widget' => 'single_text',
            'input' => 'string',
        ]);
        $form->add('end_date', DateType::class, [
            'required' => false,
            'widget' => 'single_text',
            'input' => 'string',
        ]);

        $form->add('order_by', ChoiceType::class, [
            'choices' => [
                $translator->trans('Order chosen by the theme', [], BestSellers::DOMAIN_NAME) => '',
                $translator->trans('Number of sales', [], BestSellers::DOMAIN_NAME) => BestSellers::ORDER_BY_NUMBER_OF_SALES,
                $translator->trans('Sales revenue', [], BestSellers::DOMAIN_NAME) => BestSellers::ORDER_BY_SALES_REVENUE,
            ],
            'required' => false,
            'placeholder' => false,
        ]);
    }

    /**
     * @return array<int, array{title: string, color: string}> the order statuses by id, in their order of the back office
     */
    private function orderStatuses(): array
    {
        $locale = $this->getRequest()->getLocale();
        $statuses = [];

        foreach (OrderStatusQuery::create()->orderByPosition()->find() as $status) {
            $status->setLocale($locale);
            $statuses[(int) $status->getId()] = [
                'title' => (string) ($status->getTitle() ?: $status->getCode()),
                'color' => (string) $status->getColor(),
            ];
        }

        return $statuses;
    }

    public static function getName(): string
    {
        return 'bestsellers_configuration';
    }
}
