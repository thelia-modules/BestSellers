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
use ClassicRide\ClassicRide;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Translation\Translator;
use Thelia\Form\Exception\FormValidationException;

class ConfigController extends BaseAdminController
{
    public function setAction(Request $request)
    {
        $form = $this->createForm(Configuration::getName());
        $response = null;

        try {
            $configForm = $this->validateForm($form);
            $orderData = $configForm->get('order')->getData();
            $formData = $configForm->all();
            $startDateForm = $formData['start_date'];
            $endDateForm = $formData['end_date'];
            $dateRangeForm = $formData['date_range'];
            $dateType = $formData['date_type'];

            if (
                $startDateForm->getData() === null &&
                $endDateForm->getData() === null
            ) {
                throw new \Exception(
                    Translator::getInstance()->trans() .
                    'Please select a date range or a start and end date'
                );
            }
            $date_range = $dateRangeForm->getData();
            $startDate = $startDateForm->getData()->format('Y-m-d');
            $endDate = $endDateForm->getData()->format('Y-m-d');
            $dateType = $dateType->getData();

            BestSellers::setConfigValue('start_date', $startDate, null, true);
            BestSellers::setConfigValue('end_date', $endDate, null, true);
            BestSellers::setConfigValue('order_types', $orderData, null, true);
            BestSellers::setConfigValue('date_range', $date_range, null, true);
            BestSellers::setConfigValue('date_type', $dateType, null, true);

            $response = $this->render('module-configure', [
                'module_code' => 'BestSellers',
            ]);
        } catch (FormValidationException $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans(
                    'Error',
                    [],
                    ClassicRide::DOMAIN_NAME
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
                    ClassicRide::DOMAIN_NAME
                ),
                $e->getMessage(),
                $form
            );
            return $this->generateSuccessRedirect($form);
        }

        return $response;
    }
}
