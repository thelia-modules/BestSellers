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
use BestSellers\Form\Configuration;
use BestSellers\Service\ConfigurationWriter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Form\Exception\FormValidationException;

class ConfigController extends BaseAdminController
{
    public function __construct(
        private readonly ConfigurationWriter $configurationWriter,
    ) {
    }

    #[Route('/admin/module/bestsellers/config', name: 'bestsellers.configuration', methods: ['POST'])]
    public function setAction(): Response
    {
        // The page itself asks for the right to see the module; changing its configuration asks for the right to update it.
        if (null !== $response = $this->checkAuth([], ['BestSellers'], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(Configuration::getName());

        try {
            $configForm = $this->validateForm($form);

            $this->configurationWriter->save(
                $configForm->get('order_types')->getData(),
                (int) $configForm->get('date_type')->getData(),
                $configForm->get('start_date')->getData(),
                $configForm->get('end_date')->getData(),
                $configForm->get('date_range')->getData(),
                $configForm->get('order_by')->getData(),
            );

            $this->getSession()->getFlashBag()->add(
                'success',
                $this->getTranslator()->trans('The configuration has been saved.', [], BestSellers::BO_MESSAGE_DOMAIN),
            );
        } catch (FormValidationException $e) {
            // The configuration page shows the form again, with what was entered and the error of each field.
            $this->setupFormErrorContext(
                $this->getTranslator()->trans('Best sellers configuration', [], BestSellers::BO_MESSAGE_DOMAIN),
                $e->getMessage(),
                $form,
                $e,
            );
        }

        return $this->generateRedirectFromRoute('admin.module.configure', [], ['module_code' => 'BestSellers']);
    }
}
