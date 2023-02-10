<?php

namespace App\Bundles\pqr\Services\controllers;

use App\Bundles\pqr\Services\models\PqrForm;
use Saia\controllers\generator\webservice\IWsFields;
use Saia\controllers\generator\webservice\WsFt;
use Saia\models\formatos\Formato;
use App\Bundles\pqr\Services\models\PqrFormField;

class WebservicePqr extends WsFt
{

    private PqrForm $PqrForm;
    private array $objectFieldsForAnonymous = [];
    private array $objectFields = [];

    public function __construct(Formato $Formato)
    {
        $this->PqrForm = PqrForm::getInstance();
        parent::__construct($Formato);
    }

    /**
     * @inheritDoc
     */
    public function getHtmlContentForm(array $filesToInclude, ?string $urlSearch): string
    {
        $this->addFilesToLoad($filesToInclude);
        $html = $urlSearch ? "<a href='$urlSearch'>Consultar</a>" : '';

        $values = array_merge($this->getDefaultValuesForHtmlContent(), [
            'emailLabel'    => $this->PqrForm->getRow('sys_email')->label,
            'showAnonymous' => (int)$this->PqrForm->show_anonymous,
            'showLabel'     => (int)$this->PqrForm->show_label,
            'hrefSearch'    => $html
        ]);

        return static::getContent(
            'src/Bundles/pqr/Services/controllers/templates/formPqr.html.php',
            $values
        );
    }

    /**
     * @inheritDoc
     */
    public function getJsContentForm(): string
    {
        $values = array_merge($this->getDefaultValuesForJsContent(), [
            'fieldsWithoutAnonymous' => json_encode($this->objectFields),
            'fieldsWithAnonymous'    => json_encode($this->objectFieldsForAnonymous),
            'urlSaveFt'              => 'api/pqr/captcha/saveDocument'
        ]);

        return static::getContent(
            'src/Bundles/pqr/Services/controllers/templates/formPqr.js.php',
            $values
        );
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
        $fields = [];

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
                    $fields[] = new $class($PqrFormField);
                    $this->setFieldsAnonymous($PqrFormField);
                }
            } else {
                if ($class = $this->resolveClass($PqrFormField->getCamposFormato()->etiqueta_html)) {
                    $fields[] = new $class($PqrFormField->getCamposFormato());
                    $this->setFieldsAnonymous($PqrFormField);
                }
            }
        }

        return $fields;
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

    /**
     * @inheritDoc
     */
    public function getHtmlContentSearchForm(array $filesToInclude, ?string $urlForm): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getJsContentSearchForm(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getMoreFilesToCopy(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getHtmlContentFormExposed(array $filesToInclude, bool $adicionar): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getJsContentFormExpose(bool $adicionar): string
    {
        return '';
    }
}
