<?php

namespace Saia\Pqr\Controllers\WebserviceGenerator;

use Exception;
use Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator\Date;
use Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator\Text;
use Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator\Radio;
use Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator\Hidden;
use Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator\Select;
use Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator\Checkbox;
use Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator\Dropzone;
use Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator\Textarea;

abstract class WebserviceGenerator
{
    const TYPE_CSS = 'css/';
    const TYPE_JS = 'js/';
    const TYPE_IMAGE = 'images/';

    const FOLDER_TO_GENERATE = [
        self::TYPE_CSS,
        self::TYPE_JS,
        self::TYPE_IMAGE
    ];

    const FIELD_TYPE = [
        'textarea_cke' => Textarea::class,
        'textarea' => Textarea::class,
        'fecha' => Date::class,
        'radio' => Radio::class,
        'checkbox' => Checkbox::class,
        'select' => Select::class,
        'archivo' => Dropzone::class,
        'hidden' => Hidden::class,
        'text' => Text::class,
        'input' => Text::class
    ];

    abstract protected function getFormatFields(): array;
    abstract protected function getNameForm(): string;


    public static $directory = '../client/';
    public $rootPath;
    protected $registeredCssFiles = [];
    protected $registeredJsFiles = [];
    protected $jsAditionalContent = [];

    public function generate()
    {
        global $rootPath;

        $this->rootPath = $rootPath;

        $this->generateDirectory()
            ->generateFiles();
    }

    protected function generateDirectory(): self
    {
        foreach (self::FOLDER_TO_GENERATE as $folder) {
            crear_destino($this->rootPath . $this->getRouteDirectory($folder));
        }

        return $this;
    }

    protected function setDirectory(string $directory): void
    {
        self::$directory = $directory;
    }

    protected function getDirectory(): string
    {
        return self::$directory;
    }

    protected function getRouteDirectory(string $folder): string
    {
        if (!in_array($folder, self::FOLDER_TO_GENERATE)) {
            throw new Exception("Carpeta no registrada", 1);
        }
        return $this->getDirectory() . $folder;
    }

    protected function generateFiles()
    {
        $this->moveDefaultFiles()
            ->createAddForm();
    }


    protected function moveDefaultFiles(): self
    {

        $files = [
            [
                'origin' => 'views/assets/node_modules/jquery/dist/jquery.min.js',
                'fieldName' => 'jquery.min.js',
                'type' => self::TYPE_JS
            ],
            [
                'origin' => 'views/assets/node_modules/bootstrap/dist/js/bootstrap.min.js',
                'fieldName' => 'bootstrap.min.js',
                'type' => self::TYPE_JS
            ],
            [
                'origin' => 'views/assets/node_modules/bootstrap/dist/css/bootstrap.min.css',
                'fieldName' => 'bootstrap.min.css',
                'type' => self::TYPE_CSS
            ],
            [
                'origin' => 'views/assets/node_modules/jquery-validation/dist/jquery.validate.min.js',
                'fieldName' => 'jquery.validate.min.js',
                'type' => self::TYPE_JS
            ],
            [
                'origin' => 'views/assets/node_modules/jquery-validation/dist/localization/messages_es.min.js',
                'fieldName' => 'jquery.messages_es.min.js',
                'type' => self::TYPE_JS
            ],
            [
                'origin' => 'views/assets/theme/pages/css/pages.min.css',
                'fieldName' => 'pages.min.css',
                'type' => self::TYPE_CSS
            ],
        ];

        $this->copyFiles($files);

        return $this;
    }

    protected function copyFiles(array $files): void
    {
        foreach ($files as $file) {

            $origin = $this->rootPath . $file["origin"];
            chmod($origin, PERMISOS_ARCHIVOS);

            $destination = $this->rootPath . $this->getRouteDirectory($file['type']) . $file['fieldName'];
            $this->registerFile($file['type'], $file['fieldName']);

            if (!copy($origin, $destination)) {
                throw new Exception("No fue posible copiar los archivos por defecto", 1);
            }
            chmod($destination, PERMISOS_ARCHIVOS);
        }
    }

    protected function createAddForm(): self
    {
        $content = $this->getContent();
        $this->createJsContent();

        $html = $this->getHeader();
        $html .= $content;
        $html .= $this->getFooter();

        $fileName = "{$this->rootPath}{$this->getDirectory()}index.html";

        if (!file_put_contents($fileName, $html)) {
            throw new Exception("No fue posible crear el formulario", 1);
        }

        return $this;
    }

    protected function createJsContent(): void
    {
        $contentAditional = $this->getJsAditionalContent();

        $code = <<<JAVASCRIPT
$(function() {
    {$contentAditional}
});

$("#formulario").validate({
    errorPlacement: function(error, element) {
        let node = element[0];

        if (
            node.tagName == "SELECT" &&
            node.className.indexOf("select2") !== false
        ) {
            error.addClass("pl-2");
            element.next().append(error);
        } else {
            error.insertAfter(element);
        }
    },
    submitHandler: function(form) {
        alert("formulario correcto!")
    }
});
JAVASCRIPT;

        $fileName = "{$this->rootPath}{$this->getRouteDirectory(self::TYPE_JS)}index.js";
        $this->registerFile(self::TYPE_JS, 'index.js');

        if (!file_put_contents($fileName, $code)) {
            throw new Exception("No fue posible crear el js", 1);
        }
    }

    protected function getContent(): string
    {
        $code = '';
        foreach ($this->getFormatFields() as $CamposFormato) {
            $class = $this->resolveClass($CamposFormato->etiqueta_html);
            $GenerateFieldContent = new GenerateFieldContent(new $class($CamposFormato));
            $code .= $GenerateFieldContent->getContent();

            if ($files = $GenerateFieldContent->getAditionalFiles()) {
                $this->copyFiles($files);
            }

            if ($content = $GenerateFieldContent->getJsAditionalContent()) {
                $this->addContentJs($content);
            }
        }

        return $code;
    }

    protected function addContentJs(string $content): void
    {
        array_push($this->jsAditionalContent, $content);
    }

    protected function getHeader(): string
    {
        $linkCss = $this->getCssRoute();

        $code = <<<PHP
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <meta charset="utf-8" />
    <title>SGDA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=10.0, shrink-to-fit=no" />
    <meta name="apple-mobile-web-app-capable" content="yes">{$linkCss}
</head>

<body>
    <div class='container-fluid container-fixed-lg col-lg-8' style="overflow: auto;height:100vh">
        <div class='card card-default'>
            <div class='card-body'>
                <h5 class='text-black w-100 text-center'>
                    {$this->getNameForm()}
                </h5>
                <form name='formulario' id='formulario' role='form' autocomplete='off'>
PHP;

        return $code;
    }

    protected function getCssRoute(): string
    {
        $data = "\n\r";
        foreach ($this->registeredCssFiles as $file) {
            $data .= "\t" . '<link href="' . self::TYPE_CSS . $file . '" rel="stylesheet" type="text/css" />' . "\n";
        }

        return $data;
    }

    protected function getJsRoute(): string
    {
        $data = "\n\r";
        foreach ($this->registeredJsFiles as $file) {
            $data .= "\t" . '<script type="text/javascript" src="' . self::TYPE_JS . $file . '"></script>' . "\n";
        }

        return $data;
    }

    protected function getFooter(): string
    {
        $scriptJs = $this->getJsRoute();

        $code = <<<PHP
                </form>
            </div>
        </div>
    </div>
    {$scriptJs}
</body>
</html>
PHP;

        return $code;
    }

    protected function getJsAditionalContent(): string
    {
        return implode("\n", $this->jsAditionalContent);
    }

    protected function resolveClass(string $type)
    {
        if (!array_key_exists($type, self::FIELD_TYPE)) {
            throw new Exception("El tipo de campo no ha sido registrado", 1);
        }
        return self::FIELD_TYPE[$type];
    }

    protected function registerFile(string $type, string $file): void
    {

        if ($type == self::TYPE_JS) {
            array_push($this->registeredJsFiles, $file);
        } else if ($type == self::TYPE_CSS) {
            array_push($this->registeredCssFiles, $file);
        } else {
            throw new Exception("tipo de archivo no valido para registrar", 1);
        }
    }
}
