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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class Configuration extends BaseForm
{
    protected function buildForm(): void
    {
        $form = $this->formBuilder;
        $translator = Translator::getInstance();

        $form->add('order', TextType::class, [
            'data' => BestSellers::getConfigValue('order_types'),
            'required' => true,
            'empty_data' => '2,3,4',
        ]);

        $startDateString = BestSellers::getConfigValue('start_date');
        $endDateString = BestSellers::getConfigValue('end_date');

        $startDate = $startDateString ? new \DateTime($startDateString) : null;
        $endDate = $endDateString ? new \DateTime($endDateString) : null;

        $form->add('start_date', DateType::class, [
            'data' => $startDate,
            'required' => false,
            'widget' => 'single_text',
        ]);
        $form->add('end_date', DateType::class, [
            'data' => $endDate,
            'required' => false,
            'widget' => 'single_text',
        ]);

        $form->add('date_range', ChoiceType::class, [
            'data' => BestSellers::getConfigValue('date_range'),
            'choices' => [
                $translator->trans('Last 15 days', [], BestSellers::DOMAIN_NAME ) => 'last_15_days',
                $translator->trans('Last 30 days', [], BestSellers::DOMAIN_NAME ) => 'last_30_days',
                $translator->trans('The last 6 months', [], BestSellers::DOMAIN_NAME ) => 'last_6_months',
                $translator->trans( 'The last year', [], BestSellers::DOMAIN_NAME ) => 'last_year',
                $translator->trans('Since the beginning of the year', [], BestSellers::DOMAIN_NAME ) => 'this_year',
            ],
            'required' => true,
            'mapped' => false,
        ]);

        $form->add('date_type', ChoiceType::class, [
            'data' => BestSellers::getConfigValue('date_type'),
            'label' => 'Type de dates',
            'choices' => [
                $translator->trans('Fixed dates', [], BestSellers::DOMAIN_NAME ) => 1,
                $translator->trans('Date range', [], BestSellers::DOMAIN_NAME ) => 2,
            ],
            'required' => true,
            'mapped' => false,
        ]);
    }
    public static function getName()
    {
        return 'bestsellers_configuration';
    }
}
