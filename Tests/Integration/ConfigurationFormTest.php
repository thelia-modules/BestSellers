<?php

declare(strict_types=1);

namespace BestSellers\Tests\Integration;

use BestSellers\BestSellers;
use BestSellers\Form\Configuration;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Thelia\Core\Form\TheliaFormFactory;
use Thelia\Core\Template\ParserContext;
use Thelia\Test\IntegrationTestCase;

final class ConfigurationFormTest extends IntegrationTestCase
{
    public function testAFormSentBackWithAnErrorKeepsTheDatesEntered(): void
    {
        $container = static::getContainer();
        $form = $container->get(TheliaFormFactory::class)->createForm(Configuration::getName(), FormType::class);

        // No status checked: the form is refused, and the configuration page shows it again.
        $form->getForm()->submit([
            'order_types' => [],
            'date_type' => (string) BestSellers::FIXED_DATE,
            'date_range' => BestSellers::LAST_30_DAYS,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'order_by' => '',
        ]);
        $container->get(ParserContext::class)->addForm($form);

        $saved = $container->get('request_stack')->getCurrentRequest()->getSession()->getFormErrorInformation();
        $data = $saved[Configuration::class.':'.FormType::class]['data'];

        self::assertSame('2026-03-01', $data['start_date'] ?? null);
        self::assertSame('2026-03-31', $data['end_date'] ?? null);
    }
}
