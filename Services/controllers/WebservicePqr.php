<?php

namespace App\Bundles\pqr\Services\controllers;

use App\Bundles\pqr\Services\models\PqrForm;
use Saia\controllers\generator\webservice\IWsFields;
use Saia\models\formatos\Formato;
use App\Bundles\pqr\Services\models\PqrFormField;
use Saia\controllers\generator\webservice\WsFt;
use Saia\controllers\generator\webservice\IWsHtml;

class WebservicePqr extends WsFt implements IWsHtml
{

    private PqrForm $PqrForm;
    private array $objectFieldsForAnonymous = [];
    private array $objectFields = [];

    public function __construct(Formato $Formato)
    {
        parent::__construct($Formato);
        $this->PqrForm = PqrForm::getInstance();
    }

    /**
     * @inheritDoc
     */
    public function getHtmlContentForm(array $filesToInclude, ?string $urlSearch): string
    {
        $this->addFilesToLoad($filesToInclude);
        $html = $urlSearch ? "<a href='$urlSearch'>Consultar</a>" : '';

        $values = [
            'recaptchaPublicKey' => $_SERVER['APP_RECAPTCHA_PUBLIC_KEY'],
            'emailLabel' => $this->PqrForm->getRow('sys_email')->label,
            'showAnonymous' => (int)$this->PqrForm->show_anonymous,
            'showLabel' => (int)$this->PqrForm->show_label,
            'contentFields' => $this->htmlContent,
            'nameForm' => mb_strtoupper($this->Formato->etiqueta),
            'linksCss' => $this->getLinks(self::TYPE_CSS),
            'scripts' => $this->getLinks(self::TYPE_JS),
            'hrefSearch' => $html
        ];

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
        $values = [
            'recaptchaPublicKey' => $_SERVER['APP_RECAPTCHA_PUBLIC_KEY'],
            'baseUrl' => $_SERVER['APP_DOMAIN'],
            'formatId' => $this->Formato->getPK(),
            'content' => $this->jsContent,
            'fieldsWithoutAnonymous' => json_encode($this->objectFields),
            'fieldsWithAnonymous' => json_encode($this->objectFieldsForAnonymous)
        ];

        return static::getContent(
            'src/Bundles/pqr/Services/controllers/templates/formPqr.js.php',
            $values
        );
    }

    /**
     * @inheritDoc
     */
    public function getHtmlContentSearchForm(array $filesToInclude, string $urlForm = null): string
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
        return $this->moreFiles;
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

        $records = $this->PqrForm->PqrFormFields;
        $specialFields = [
            'tratamiento',
            'localidad',
            'dependencia'
        ];

        foreach ($records as $PqrFormField) {
            if (!$PqrFormField->active || !$PqrFormField->fk_campos_formato) {
                continue;
            }

            if (in_array($PqrFormField->PqrHtmlField->type, $specialFields)) {
                if ($class = $this->resolveCustomClass(ucfirst($PqrFormField->PqrHtmlField->type))) {
                    $fields[] = new $class($PqrFormField);
                    $this->setFieldsAnonymous($PqrFormField);
                }
            } else {
                if ($class = $this->resolveClass($PqrFormField->CamposFormato->etiqueta_html)) {
                    $fields[] = new $class($PqrFormField->CamposFormato);
                    $this->setFieldsAnonymous($PqrFormField);
                }
            }
        }

        return $fields;
    }

    private function setFieldsAnonymous(PqrFormField $PqrFormField): void
    {
        array_push($this->objectFields, [
            'name' => $PqrFormField->name,
            'required' => (int)$PqrFormField->required,
        ]);
        array_push($this->objectFieldsForAnonymous, [
            'name' => $PqrFormField->name,
            'show' => (int)$PqrFormField->anonymous,
            'required' => (int)($PqrFormField->anonymous ? $PqrFormField->required_anonymous : 0)
        ]);
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
}
