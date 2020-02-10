<?php

namespace Saia\Pqr\Controllers\WebserviceGenerator;

use Exception;
use Saia\controllers\UtilitiesController;
use Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator\Text;
use Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator\Radio;
use Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator\Select;
use Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator\Checkbox;
use Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator\Textarea;

abstract class WebserviceGenerator
{
    const TYPE_CSS = 'css/';
    const TYPE_JS = 'js/';
    const TYPE_IMAGE = 'images/';
    const TYPE_FONT = 'fonts/';

    const FOLDER_TO_GENERATE = [
        self::TYPE_CSS,
        self::TYPE_JS,
        self::TYPE_IMAGE,
        self::TYPE_FONT
    ];

    const FIELD_TYPE = [
        'textarea_cke' => Textarea::class,
        'textarea' => Textarea::class,
        'radio' => Radio::class,
        'checkbox' => Checkbox::class,
        'select' => Select::class,
        'text' => Text::class,
        'input' => Text::class
    ];

    /**
     * Debe retornar un array con las instancias de CamposFormato que va a generar
     * en el formulario del webservice
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    abstract protected function getFormatFields(): array;

    /**
     * Nombre del formulario que aparecera en el webservice
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    abstract protected function getNameForm(): string;

    /**
     * Debe retornar el codigo JS que va adicionarse al formulario
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    abstract protected function createJsContent(): string;

    /**
     * Contenido "Cuerpo" del formulario, aqui va toda la creacion de los campos
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    abstract protected function getContent(): string;

    /**
     * Directorio principal donde quedara el webservice
     *
     * @var string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public static $directory = '../client/';

    /**
     * Bandera que posiciona en la raiz del proyecto
     *
     * @var string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public $rootPath;

    /**
     * Almacena los scripts CSS registrados que se cargaran en el formulario
     * del webservice
     *
     * @var array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected $registeredCssFiles = [];

    /**
     * Almacena los scripts JS registrados que se cargaran en el formulario
     * del webservice
     *
     * @var array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected $registeredJsFiles = [];

    /**
     * Contenido adicional JS que se adicionara en el formulario,
     * este contenido es utilizado para inicializar librerias js
     * como select2 al crear un campo tipo select
     *
     * @var array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected $jsAditionalContent = [];

    /**
     * Genera el formulario webservice
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function generate()
    {
        global $rootPath;

        $this->rootPath = $rootPath;

        $this->generateDirectory()
            ->generateFiles();
    }

    /**
     * Crea los directorios donde se almacenara los scripts del webservice
     *
     * @return self
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function generateDirectory(): self
    {
        foreach (self::FOLDER_TO_GENERATE as $folder) {
            crear_destino($this->rootPath . $this->getRouteDirectory($folder));
        }

        return $this;
    }

    /**
     * Setea el directorio principal, en caso de querer ubicar el webservice en otro 
     * lugar
     *
     * @param string $directory : Nuevo directorio relativo a la raiz
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function setDirectory(string $directory): void
    {
        self::$directory = $directory;
    }


    /**
     * Obtiene la ruta del directorio principal del webservice
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getDirectory(): string
    {
        return self::$directory;
    }

    /**
     * Obtiene el directorio de los scripts segun el tipo recibido
     * (Ver Constantes TYPE de la clase)
     * 
     * @param string $folder
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getRouteDirectory(string $folder): string
    {
        if (!in_array($folder, self::FOLDER_TO_GENERATE)) {
            throw new Exception("Carpeta no registrada", 1);
        }
        return $this->getDirectory() . $folder;
    }

    /**
     * Genera los scripts del webservice
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function generateFiles()
    {
        $this->copyDefaultFiles()
            ->createAddForm();
    }

    /**
     * Copia los scripts por defecto que utilizara el webservice
     *
     * @return self
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function copyDefaultFiles(): self
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
            [
                'origin' => 'views/assets/node_modules/izitoast/dist/css/iziToast.min.css',
                'fieldName' => 'iziToast.min.css',
                'type' => self::TYPE_CSS
            ],
            [
                'origin' => 'views/assets/node_modules/izitoast/dist/js/iziToast.min.js',
                'fieldName' => 'iziToast.min.js',
                'type' => self::TYPE_JS
            ],
            [
                'origin' => 'views/assets/theme/assets/plugins/font-awesome/css/font-awesome.min.css',
                'fieldName' => 'font-awesome.min.css',
                'type' => self::TYPE_CSS
            ]
        ];

        $this->copyFiles($files);

        $folders = [
            [
                'origin' => 'views/assets/theme/assets/plugins/font-awesome/fonts/',
                'destination' => $this->getRouteDirectory(self::TYPE_FONT)
            ]
        ];

        foreach ($folders as $folder) {
            $destination = $this->rootPath . $folder['destination'];
            if (!UtilitiesController::copyToDir($this->rootPath . $folder['origin'], $destination)) {
                throw new Exception("No fue posible generar las fuentes", 1);
            }
        }

        return $this;
    }

    /**
     * Copia los archivos
     *
     * @param array $files : archivos a copiar
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function copyFiles(array $files): void
    {
        foreach ($files as $file) {

            $origin = $this->rootPath . $file["origin"];
            chmod($origin, PERMISOS_ARCHIVOS);

            $destination = $this->rootPath . $this->getRouteDirectory($file['type']) . $file['fieldName'];
            $this->registerFile($file['type'], $file['fieldName']);

            if (!copy($origin, $destination)) {
                throw new Exception("No fue posible generar los archivos por defecto", 1);
            }
            chmod($destination, PERMISOS_ARCHIVOS);
        }
    }

    /**
     * Principal encargada de generar todo el formulario del webservice
     *
     * @return self
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function createAddForm(): self
    {
        $content = $this->getContent();
        $this->generateJsFile();

        $html = $this->getHeader();
        $html .= $content;
        $html .= $this->getFooter();

        $fileName = "{$this->rootPath}{$this->getDirectory()}index.html";

        if (!file_put_contents($fileName, $html)) {
            throw new Exception("No fue posible crear el formulario", 1);
        }

        return $this;
    }

    /**
     * Genera los archivos JS utilizados para el formulario del webservice
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function generateJsFile(): void
    {
        $code = $this->createJsContent();

        $fileName = "{$this->rootPath}{$this->getRouteDirectory(self::TYPE_JS)}index.js";
        $this->registerFile(self::TYPE_JS, 'index.js');

        if (!file_put_contents($fileName, $code)) {
            throw new Exception("No fue posible crear el js", 1);
        }
    }

    /**
     * Contenido JS por defecto que genera la creacion de los campos y sus validaciones
     *
     * @param string $url : url donde enviara los datos del formulario
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function jsContentDefault(string $url): string
    {
        $contentAditional = $this->getJsAditionalContent();
        $baseUrl = ABSOLUTE_SAIA_ROUTE;

        $code = <<<JAVASCRIPT

$(function () {
    var baseUrl = '{$baseUrl}';

    {$contentAditional}

    $("#save_document").click(function () {
        $("#form_buttons").find('button,#spiner').toggleClass('d-none');
        $("#formulario").trigger('submit');
    });

    $("#formulario").validate({
        errorPlacement: function (error, element) {
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
        submitHandler: function (form) {
            let data = $('#formulario').serialize();

            $.post(
                `${baseUrl}{$url}`,
                data,
                function (response) {
                    if (response.success) {
                        notification({
                            color:'green',
                            position: "topCenter",
                            timeout: false,
                            message:'Su solicitud ha sido radicada, en los proximos minutos sera enviado toda la informacion al correo registrado'
                        });
                    } else {
                        console.error(response.message);
                        notification({
                            color:'red',
                            message:'No fue posible radicar su solicitud'
                        });
                    }
                },
                'json'
            )
            .fail(function () {
                console.error(...arguments)
            })
            .always(function () {
                toggleButton();
            });

            return false;
        },
        invalidHandler: function () {
            toggleButton();
        }
    });

    function toggleButton(message) {
        $("#form_buttons").find('button,#spiner').toggleClass('d-none');
    }

    function notification(options) {
        let iziDefaultOptions = {
            position: "topRight",
            timeout: 15000,
        };
        options = $.extend({}, iziDefaultOptions, options);
        iziToast.show(options);
    }

});
JAVASCRIPT;
        return $code;
    }

    /**
     * Obtiene el contenido del formulario, este es agregado en el index
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getContentDefault(): string
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
                    <div class='form-group px-0 pt-3' id='form_buttons'>
                        <button class='btn btn-complete' id='save_document' type='button'>Continuar</button>
                        <div class='progress-circle-indeterminate d-none' id='spiner'></div>
                    </div>
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
