<?php

namespace App\Bundles\pqr\Services\controllers;

use App\Bundles\pqr\Services\models\PqrForm;
use Saia\models\formatos\Formato;
use App\Bundles\pqr\Services\models\PqrFormField;
use Saia\controllers\generator\webservice\WsFt;
use Saia\controllers\generator\webservice\IWsHtml;
use Saia\controllers\generator\webservice\WsGenerator;

class WebservicePqr extends WsFt implements IWsHtml
{

    private string $jsContent;
    private string $htmlContent;
    private array $moreFiles = [];
    private PqrForm $PqrForm;
    private array $objectFieldsForAnonymous = [];
    private array $objectFields = [];

    public function __construct(Formato $Formato)
    {
        parent::__construct($Formato);
        $this->PqrForm = PqrForm::getPqrFormActive();
    }

    /**
     * @inheritDoc
     */
    public function getHtmlContentForm(array $filesToInclude, ?string $urlSearch): string
    {
        $this->setContentForm($filesToInclude);
        $moreFiles = [];
        if ($this->moreFiles) {
            $moreFiles = WsGenerator::getRouteFile($this->moreFiles);
        }
        if ($moreFiles) {
            $lastFiles = array_slice($filesToInclude, -2);
            array_splice($filesToInclude, -2, 2, $moreFiles);
            $filesToInclude = array_merge($filesToInclude, $lastFiles);
        }

        $this->addFilesToForm($filesToInclude);

        $html = $urlSearch ? "<a href='{$urlSearch}'>Consultar</a>" : '';

        $values = [
            'showAnonymous' => (int) $this->PqrForm->show_anonymous,
            'showLabel' => (int) $this->PqrForm->show_label,
            'contentFields' => $this->htmlContent,
            'nameForm' => mb_strtoupper($this->Formato->etiqueta),
            'linksCss' => $this->getCssLinks(),
            'scripts' => $this->getScriptLinks(),
            'hrefSearch' => $html
        ];

        return $this->getContent(
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
            'baseUrl' => ABSOLUTE_SAIA_ROUTE,
            'formatId' => $this->Formato->getPK(),
            'content' => $this->jsContent,
            'fieldsWithoutAnonymous' => json_encode($this->objectFields),
            'fieldsWithAnonymous' => json_encode($this->objectFieldsForAnonymous)
        ];

        return $this->getContent(
            'src/Bundles/pqr/Services/controllers/templates/formPqr.js.php',
            $values
        );
    }

    /**
     * @inheritDoc
     */
    public function getHtmlContentSearchForm(array $filesToInclude, string $urlForm): string
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
     * Setea las variables que tendran el contenido del formulario
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function setContentForm(): void
    {
        $codeHtml = $codeJs = '';
        $fields = $this->getFields();

        foreach ($fields as $IWsFields) {
            ($files = $IWsFields->aditionalFiles()) ?
                $this->moreFiles = array_merge($this->moreFiles, $files) : '';

            $codeHtml .= $IWsFields->htmlContent() . "\n";

            $codeJs .= $IWsFields->jsContent() ?
                $IWsFields->jsContent() . "\n" : '';
        }

        $this->jsContent = $codeJs;
        $this->htmlContent = $codeHtml;
    }

    /**
     * Obtiene los campos que seran creados para el cuerpo
     * del ws
     *
     * @return IWsFields[]
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getFields(): array
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
            'required' => (int) $PqrFormField->required,
        ]);
        array_push($this->objectFieldsForAnonymous, [
            'name' => $PqrFormField->name,
            'show' => (int) $PqrFormField->anonymous,
            'required' => (int) ($PqrFormField->anonymous ? $PqrFormField->required_anonymous : 0)
        ]);
    }


    /**
     * Resuelve la clase a utilizar para los campos especiales
     *
     * @param String $typeField
     * @return string|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function resolveCustomClass(String $typeField): ?string
    {
        $className = "App\\Bundles\\pqr\\Services\\controllers\\customFields\\$typeField";
        if (class_exists($className)) {
            return $className;
        }
        return null;
    }
}
