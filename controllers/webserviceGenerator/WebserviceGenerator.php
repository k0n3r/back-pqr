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

    abstract protected function getContent(): array;

    public static $directory = '../client/';
    public $rootPath;
    protected $registeredCssFiles = [];
    protected $registeredJsFiles = [];

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
            ->generateDefaultFiles()
            ->createAddForm();
    }


    protected function moveDefaultFiles(): self
    {
        $files = [
            [
                'origin' => 'views/assets/node_modules/jquery/dist/jquery.min.js',
                'destination' => $this->getRouteDirectory(self::TYPE_JS) . 'jquery.min.js',
                'type' => self::TYPE_JS
            ],
            [
                'origin' => 'views/assets/node_modules/bootstrap/dist/js/bootstrap.min.js',
                'destination' => $this->getRouteDirectory(self::TYPE_JS) . 'bootstrap.min.js',
                'type' => self::TYPE_JS
            ],
            [
                'origin' => 'views/assets/node_modules/bootstrap/dist/css/bootstrap.min.css',
                'destination' => $this->getRouteDirectory(self::TYPE_CSS) . 'bootstrap.min.css',
                'type' => self::TYPE_CSS
            ],
            [
                'origin' => 'views/assets/node_modules/jquery-validation/dist/jquery.validate.min.js',
                'destination' => $this->getRouteDirectory(self::TYPE_JS) . 'jquery.validate.min.js',
                'type' => self::TYPE_JS
            ],
            [
                'origin' => 'views/assets/node_modules/jquery-validation/dist/localization/messages_es.min.js',
                'destination' => $this->getRouteDirectory(self::TYPE_JS) . 'jquery.messages_es.min.js',
                'type' => self::TYPE_JS
            ]
        ];

        foreach ($files as $file) {

            $origin = $this->rootPath . $file["origin"];
            chmod($origin, PERMISOS_ARCHIVOS);

            $destination = $this->rootPath . $file['destination'];
            $this->registerFile($file['type'], basename($file['destination']));

            if (!copy($origin, $destination)) {
                throw new Exception("No fue posible copiar los archivos por defecto", 1);
            }
            chmod($destination, PERMISOS_ARCHIVOS);
        }

        return $this;
    }

    protected function generateDefaultFiles(): self
    {
        $files = [
            [
                'type' => self::TYPE_JS,
                'file' => 'custom.js'
            ],
            [
                'type' => self::TYPE_CSS,
                'file' => 'custom.css'
            ]
        ];

        foreach ($files as $file) {

            $newFile = "{$this->rootPath}{$this->getRouteDirectory($file['type'])}{$file['file']}";
            if (!file_exists($newFile)) {
                $text = "// Aqui va el contenido personalizado";
                if (!file_put_contents($newFile, $text)) {
                    throw new Exception("No fue posible crear el archivo {$file}", 1);
                }
            }
            $this->registerFile($file['type'], $file['file']);
        }

        return $this;
    }

    protected function createAddForm(): self
    {
        $content = $this->getContent();

        $html = $this->getHeader();
        $html .= $content;
        $html .= $this->getFooter();

        $fileName = "{$this->rootPath}{$this->getDirectory()}index.html";

        if (!file_put_contents($fileName, $html)) {
            throw new Exception("No fue posible crear el formulario", 1);
        }

        return $this;
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
                    FORMULARIO DE PQR
                </h5>
                <form name='formulario_formatos' id='formulario_formatos' role='form' autocomplete='off'>
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
