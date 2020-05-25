<?php

namespace Saia\Pqr\webserviceGenerator;

use Saia\Pqr\webserviceGenerator\IWsHtml;

class WsFormulario extends WsFt implements IWsHtml
{
    private array $cssFilesOfFields = [];
    private array $jsFilesOfFields = [];

    /**
     * @inheritDoc
     */
    public function getHtmlContentForm(array $filesToInclude, ?string $urlSearch): string
    {
        $this->addFiles($filesToInclude);

        $valuesTemplate = [
            'fields' => $this->getBodyForm($urlSearch),
            'nameForm' => mb_strtoupper($this->Formato->etiqueta),
            'linksCss' => $this->getCssLinks(),
            'scripts' => $this->getScriptLinks()
        ];
        extract($valuesTemplate);

        ob_start();
        include 'templates/form.php';
        return ob_get_clean();
    }

    /**
     * @inheritDoc
     */
    public function getJsContentForm(): string
    {
        $code = '';
        return $code;
    }

    /**
     * @inheritDoc
     */
    public function getHtmlContentSearchForm(array $filesToInclude, string $urlForm): string
    {
        $code = '';
        return $code;
    }

    /**
     * @inheritDoc
     */
    public function getJsContentSearchForm(): string
    {
        $code = '';
        return $code;
    }


    private function getBodyForm(?string $urlSearch)
    {
        $CamposFormato = $this->Formato->getFields();
    }
}
