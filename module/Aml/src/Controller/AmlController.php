<?php

declare(strict_types=1);

namespace Aml\Controller;

use Aml\Service\AmlService;
use Aml\Traits\AmlTraitMethods;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class AmlController extends AbstractActionController
{
    use AmlTraitMethods;

    private $aml_service;

    public function __construct(AmlService $aml_service)
    {
        $this->setAmlService($aml_service);
    }

    ///////////////////////////////////////
    ////////// Getters & Setters //////////
    ///////////////////////////////////////

    private function getAmlService(): AmlService
    {
        return $this->aml_service;
    }
    private function setAmlService(AmlService $input): void
    {
        $this->aml_service = $input;
    }

    ////////////////////////////////////
    ////////// Public Methods //////////
    ////////////////////////////////////

    public function indexAction()
    {
        return new JsonModel([]);
    }

    public function listsAction()
    {
        $response = $this->getAmlService()->lists();
        return new JsonModel($response);
    }

    public function searchAction()
    {
        $data = $this->getRequest()->getContent();
        $data = @json_decode($data, true);
        if (empty($data)) {
            return new JsonModel([
                'error' => true,
                'message' => 'empty-request'
            ]);
        }
        $birthdate = ! empty($data['birthdate']) ? $this->sanitize($data['birthdate']) : null;
        $fullname = ! empty($data['fullname']) ? $this->sanitize($data['fullname']) : null;
        // backwards compatibility
        $name = ! empty($data['name']) ? $this->sanitize($data['name']) : null;
        $surname = ! empty($data['surname']) ? $this->sanitize($data['surname']) : null;
        if (empty($fullname) && (! empty($name) || ! empty($surname))) {
            $fullname = $this->sanitize($name . ' ' . $surname);
        }
        $response = $this->getAmlService()->search($fullname, $birthdate);
        return new JsonModel($response);
    }

    public function updateAction()
    {
        $id = $this->sanitize($this->params()->fromRoute('id', null));
        if (empty($id)) {
            $id = 'all';
        }
        if (isset($id) && ! preg_match("/^(?:[1-9]|all)$/uim", $id)) {
            return new JsonModel([
                'error' => true,
                'message' => 'unknown-operation-id'
            ]);
        }
        $response = $this->getAmlService()->update($id);
        return new JsonModel($response);
    }
}
