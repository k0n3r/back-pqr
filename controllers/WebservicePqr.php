<?php

namespace Saia\Pqr\controllers;

use Saia\Pqr\models\PqrForm;
use Saia\models\formatos\Formato;
use Saia\controllers\generator\webservice\WsFt;
use Saia\controllers\generator\webservice\IWsHtml;
use Saia\controllers\generator\webservice\WsGenerator;

class WebservicePqr extends WsFt implements IWsHtml
{

    private string $jsContent;
    private string $htmlContent;
    private array $moreFiles = [];
    private PqrForm $PqrForm;


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
            'fields' => $this->htmlContent,
            'nameForm' => mb_strtoupper($this->Formato->etiqueta),
            'linksCss' => $this->getCssLinks(),
            'scripts' => $this->getScriptLinks(),
            'hrefSearch' => $html
        ];

        return $this->getContent(
            'app/modules/back_pqr/controllers/templates/formPqr.html.php',
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
            'content' => $this->jsContent
        ];

        return $this->getContent(
            'app/modules/back_pqr/controllers/templates/formPqr.js.php',
            $values
        );
    }

    /**
     * @inheritDoc
     */
    public function getHtmlContentSearchForm(array $filesToInclude, string $urlForm): string
    {
        $this->addFilesToSearch($filesToInclude);
        $html = $urlForm ? "<a href='{$urlForm}'>Crear solicitud</a>" : '';

        $values = [
            'linksCss' => $this->getCssLinks(false),
            'scripts' => $this->getScriptLinks(false),
            'hrefSolicitud' => $html
        ];

        return $this->getContent(
            'app/controllers/generator/webservice/templates/search.html.php',
            $values
        );
    }

    /**
     * @inheritDoc
     */
    public function getJsContentSearchForm(): string
    {
        $values = [
            'formatId' => $this->Formato->getPK()
        ];

        return $this->getContent(
            'app/controllers/generator/webservice/templates/search.js.php',
            $values
        );
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
        $fields = $this->getFormatFields();

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
}
