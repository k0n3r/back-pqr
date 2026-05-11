<?php

namespace App\Bundles\pqr\Services\controllers;

use App\Bundles\pqr\Entity\PqrForm as PqrFormEntity;
use App\Bundles\pqr\Entity\PqrFormField as PqrFormFieldEntity;
use App\Bundles\pqr\Repository\PqrFormFieldRepository;
use App\Service\LegacyServiceLocator;
use Saia\controllers\generator\webservice\IWsFields;
use Saia\controllers\generator\webservice\WsFt;
use Saia\models\formatos\CamposFormato;
use Saia\models\formatos\Formato;

class WebservicePqr extends WsFt
{
    protected PqrFormEntity $PqrForm;
    private PqrFormFieldRepository $fieldRepo;
    private array $objectFieldsForAnonymous = [];
    private array $objectFields = [];
    private bool $isProcessFields = false;
    private array $fields = [];

    public function __construct(Formato $Formato)
    {
        $em = LegacyServiceLocator::getInstance()->getEntityManager();
        $this->PqrForm    = $em->getRepository(PqrFormEntity::class)->findActiveOrFail();
        $this->fieldRepo  = $em->getRepository(PqrFormFieldEntity::class);
        parent::__construct($Formato);
    }

    public function getOtherValuesFromForm(): array
    {
        return [
            'emailLabel'    => $this->fieldRepo->findByName('sys_email')?->getLabel() ?? '',
            'showAnonymous' => (int)$this->PqrForm->isShowAnonymous(),
            'showLabel'     => (int)$this->PqrForm->isShowLabel(),
            'nameForm'      => $this->PqrForm->getLabel(),
        ];
    }

    public function getOtherValuesFromJsForm(): array
    {
        return [
            'fieldsWithoutAnonymous' => json_encode($this->getFieldsWithoutAnonymous()),
            'fieldsWithAnonymous'    => json_encode($this->getFieldsWithAnonymous()),
            'urlSaveFt'              => $_SERVER['APP_RECAPTCHA_PUBLIC_KEY'] ? '/api/pqr/captcha/saveDocument' : '/api/pqr/webservice/saveDocument',
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

    private function setFieldsAnonymous(PqrFormFieldEntity $field): void
    {
        $typeSaia = $field->getHtmlField()->getTypeSaia();
        $this->objectFields[] = [
            'name'     => $field->getName(),
            'required' => (int)$field->isRequired(),
            'type'     => $typeSaia,
        ];
        $this->objectFieldsForAnonymous[] = [
            'name'     => $field->getName(),
            'show'     => (int)$field->isAnonymous(),
            'required' => (int)($field->isAnonymous() ? $field->isRequiredAnonymous() : 0),
            'type'     => $typeSaia,
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

        $records = $this->fieldRepo->findByPqrFormOrdered($this->PqrForm->getId());
        $specialFields = [
            'tratamiento',
            'localidad',
            'dependencia',
        ];

        foreach ($records as $field) {
            if (!$field->isActive() || !$field->getFkCamposFormato()) {
                continue;
            }

            $htmlType = $field->getHtmlField()->getType();
            if (in_array($htmlType, $specialFields)) {
                if ($class = $this->resolveCustomClass(ucfirst($htmlType))) {
                    $this->fields[] = new $class($field);
                    $this->setFieldsAnonymous($field);
                }
            } else {
                $camposFormato   = new CamposFormato($field->getFkCamposFormato());
                $ComponentBuilder = $camposFormato->getComponentBuilder();
                if ($ComponentBuilder->supportWs() && $camposFormato->isVisibleFieldAdd()) {
                    $this->fields[] = $ComponentBuilder;
                    $this->setFieldsAnonymous($field);
                }
            }
        }
        $this->isProcessFields = true;
    }
}
