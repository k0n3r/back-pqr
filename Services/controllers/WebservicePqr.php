<?php

namespace App\Bundles\pqr\Services\controllers;

use App\Bundles\pqr\Services\models\PqrForm;
use Saia\controllers\generator\webservice\IWsFields;
use Saia\controllers\generator\webservice\WsFt;
use Saia\models\formatos\Formato;
use App\Bundles\pqr\Services\models\PqrFormField;

class WebservicePqr extends WsFt
{

    protected PqrForm $PqrForm;
    private array $objectFieldsForAnonymous = [];
    private array $objectFields = [];
    private bool $isProcessFields = false;
    private array $fields = [];

    public function __construct(Formato $Formato)
    {
        $this->PqrForm = PqrForm::getInstance();
        parent::__construct($Formato);
    }

    public function getOtherValuesFromForm(): array
    {
        return [
            'emailLabel'    => $this->PqrForm->getRow('sys_email')->label,
            'showAnonymous' => (int)$this->PqrForm->show_anonymous,
            'showLabel'     => (int)$this->PqrForm->show_label
        ];
    }

    public function getOtherValuesFromJsForm(): array
    {
        return [
            'fieldsWithoutAnonymous' => json_encode($this->getFieldsWithoutAnonymous()),
            'fieldsWithAnonymous'    => json_encode($this->getFieldsWithAnonymous()),
            'urlSaveFt'              => $_SERVER['APP_RECAPTCHA_PUBLIC_KEY'] ? '/api/pqr/captcha/saveDocument' : '/api/pqr/webservice/saveDocument'
        ];
    }

    /**
     * Obtiene los campos que seran creados para el cuerpo
     * del ws
     *
     * @return IWsFields[]
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    protected function getFormatFields(): array
    {
        $this->processFields();

        return $this->fields;
    }

    /**
     * Obtiene los campos sin anonimo
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-03-22
     */
    public function getFieldsWithoutAnonymous(): array
    {
        $this->processFields();

        return $this->objectFields;
    }

    /**
     * Obtiene los campos anonimos
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-03-22
     */
    public function getFieldsWithAnonymous(): array
    {
        $this->processFields();

        return $this->objectFieldsForAnonymous;
    }

    private function setFieldsAnonymous(PqrFormField $PqrFormField): void
    {
        $this->objectFields[] = [
            'name'     => $PqrFormField->name,
            'required' => (int)$PqrFormField->required,
            'type'     => $PqrFormField->getPqrHtmlField()->type_saia
        ];
        $this->objectFieldsForAnonymous[] = [
            'name'     => $PqrFormField->name,
            'show'     => (int)$PqrFormField->anonymous,
            'required' => (int)($PqrFormField->anonymous ? $PqrFormField->required_anonymous : 0),
            'type'     => $PqrFormField->getPqrHtmlField()->type_saia
        ];
    }


    /**
     * Resuelve la clase a utilizar para los campos especiales
     *
     * @param String $typeField
     * @return string|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    protected function resolveCustomClass(string $typeField): ?string
    {
        $className = "App\\Bundles\\pqr\\Services\\controllers\\customFields\\$typeField";
        if (class_exists($className)) {
            return $className;
        }
        return null;
    }

    private function processFields(): void
    {
        if ($this->isProcessFields) {
            return;
        }

        $records = $this->PqrForm->getPqrFormFields();
        $specialFields = [
            'tratamiento',
            'localidad',
            'dependencia'
        ];

        foreach ($records as $PqrFormField) {
            if (!$PqrFormField->active || !$PqrFormField->fk_campos_formato) {
                continue;
            }

            if (in_array($PqrFormField->getPqrHtmlField()->type, $specialFields)) {
                if ($class = $this->resolveCustomClass(ucfirst($PqrFormField->getPqrHtmlField()->type))) {
                    $this->fields[] = new $class($PqrFormField);
                    $this->setFieldsAnonymous($PqrFormField);
                }
            } else {

                $ComponentBuilder = $PqrFormField->getCamposFormato()->getComponentBuilder();
                if ($ComponentBuilder->supportWs() && $PqrFormField->getCamposFormato()->isVisibleFieldAdd()) {
                    $this->fields[] = $ComponentBuilder;
                    $this->setFieldsAnonymous($PqrFormField);
                }
            }
        }
        $this->isProcessFields = true;
    }
}
