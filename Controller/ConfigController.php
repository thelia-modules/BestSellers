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

use BestSellers\Form\Configuration;
use BestSellers\Service\ConfigurationWriter;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Translation\Translator;
use Thelia\Form\Exception\FormValidationException;

class ConfigController extends BaseAdminController
{
    public function __construct(
        private readonly ConfigurationWriter $configurationWriter,
    ) {
    }

    #[Route('/admin/module/bestsellers/config', name: 'bestsellers.configuration', methods: ['POST'])]
    public function setAction()
    {
        $form = $this->createForm(Configuration::getName());

        try {
            $configForm = $this->validateForm($form);
            $formData = $configForm->all();

            $this->configurationWriter->save(
                $configForm->get('order')->getData(),
                $formData['date_type']->getData(),
                $formData['start_date']->getData(),
                $formData['end_date']->getData(),
                $formData['date_range']->getData(),
                $formData['order_by']->getData(),
            );

            $response = $this->redirectAction();

        } catch (FormValidationException $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans(
                    'Error',
                    [],
                ),
                $e->getMessage(),
                $form
            );

            return $this->generateSuccessRedirect($form);
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans(
                    'Error',
                    [],
                ),
                $e->getMessage(),
                $form
            );
            return $this->generateSuccessRedirect($form);
        }

        return $response;
    }
    public function redirectAction()
    {
        return $this->generateRedirectFromRoute('admin.module.configure', [], ['module_code' => 'BestSellers']);
    }
}
