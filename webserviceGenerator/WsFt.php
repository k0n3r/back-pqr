<?php

namespace Saia\Pqr\webserviceGenerator;

use Saia\models\formatos\Formato;


class WsFt
{
    private Formato $Formato;
    private array $cssFiles = [];
    private array $jsFiles = [];

    public function __construct(Formato $Formato)
    {
        $this->Formato = $Formato;
    }

    protected function addFiles(array $files): void
    {
        foreach ($files as $file) {
            $SplFileInfo = new \SplFileInfo($file);
            if ($SplFileInfo->getExtension() == 'css') {
                if (!in_array($file, $this->cssFiles)) {
                    array_push($this->cssFiles, $file);
                }
            } else {
                if (!in_array($file, $this->jsFiles)) {
                    array_push($this->jsFiles, $file);
                }
            }
        }
    }

    protected function getCssLinks(): string
    {
        $links = '';
        foreach ($this->cssFiles as $file) {
            $links .= "\t" . '<link href="' . $file . '" rel="stylesheet" type="text/css" />' . "\n";
        }
        return $links;
    }

    protected function getScriptLinks()
    {
        $js = '';
        foreach ($this->JsFiles as $file) {
            $js .= "\t" . '<script type="text/javascript" src="' . $file . '"></script>' . "\n";
        }
        return $js;
    }
}
