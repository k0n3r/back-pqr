<?php

namespace Saia\Pqr\webserviceGenerator;

class WsGenerator
{
    const DIRECTORY = "../ws/";

    const TYPE_CSS = 'css/';
    const TYPE_JS = 'js/';
    const TYPE_IMAGE = 'img/';
    const TYPE_FONT = 'fonts/';


    protected IWsHtml $IWsHtml;
    protected bool $generateSearch;
    protected string $nameFolderWs;
    protected string $rootPath;
    protected array $additionalFiles = [];
    protected array $registeredFiles = [];

    /**
     * Instancia encargada de generar el ws 
     *
     * @param IWsHtml => Object que implementara el ws, esta contiene todo el html
     * que se va a cargar
     * @param boolean $generateSearch => Generar el buscador del ws
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function __construct(IWsHtml $IWsHtml, string $nameFolderWs, bool $generateSearch = false)
    {
        global $rootPath;

        $this->IWsHtml = $IWsHtml;
        $this->generateSearch = $generateSearch;
        $this->nameFolderWs = $nameFolderWs;
        $this->rootPath = $rootPath;
    }

    /**
     * Adiciona archivos personalizados al ws y los carga
     * en el cuerpo del formulario indicado
     *
     * @param string $file
     * @param boolean $loadIn = 1 para cargar solo en el formulario, 
     *                          2 para cargar solo en el buscar
     *                          0 default => para cargar en ambos
     * 
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function addFiles(array $files, int $loadIn = 0)
    {
        // return [
        //     [
        //         'origin' => 'views/assets/node_modules/jquery/dist/jquery.min.js',
        //         'fieldName' => 'jquery.min.js',
        //         'type' => self::TYPE_JS
        //     ],

        foreach ($files as $file) {
            if (!in_array($file, $this->additionalFiles)) {
                $this->additionalFiles[];
                //aqui voy
            }
        }
    }

    /**
     * Crea los archivos del ws
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function create(): bool
    {
        $this->createFolders();

        if ($this->createForm()) {
            if ($this->generateSearch) {
                $this->createSearchForm();
            }
        }

        return true;
    }

    private function getWsContainerFolder()
    {
        return self::DIRECTORY . $this->nameFolderWs . "/";
    }

    private function createForm(): bool
    {
        $contentForm = $this->IWsHtml->getHtmlContentForm($this->registeredFiles, $this->generateSearch);
        $fileName = "{$this->rootPath}{$this->getWsContainerFolder()}index.html";

        if (!file_put_contents($fileName, $contentForm)) {
            throw new \Exception("No fue posible crear el formulario", 200);
        }

        $contentJs = $this->IWsHtml->getJsContentForm($this->registeredFiles, $this->generateSearch);
        $fileNameJs = "{$this->rootPath}{$this->getWsContainerFolder()}" . self::TYPE_JS . "index.js";

        if (!file_put_contents($fileNameJs, $contentJs)) {
            throw new \Exception("No fue posible crear el js del formulario", 200);
        }
        return true;
    }

    private function createSearchForm()
    {
        $contentForm = $this->IWsHtml->getHtmlContentSearchForm($this->registeredFiles, $this->generateSearch);
        $fileName = "{$this->rootPath}{$this->getWsContainerFolder()}buscar.html";

        if (!file_put_contents($fileName, $contentForm)) {
            throw new \Exception("No fue posible crear el formulario de busqueda", 200);
        }

        $contentJs = $this->IWsHtml->getJsContentSearchForm($this->registeredFiles, $this->generateSearch);
        $fileNameJs = "{$this->rootPath}{$this->getWsContainerFolder()}" . self::TYPE_JS . "buscar.js";

        if (!file_put_contents($fileNameJs, $contentJs)) {
            throw new \Exception("No fue posible crear el js de busqueda", 200);
        }
        return true;
    }

    private function createFolders(): void
    {
        $this->registerFile($this->copyFiles($this->defaultFiles()));

        $this->registerFile($this->copyFiles($this->additionalFiles), true);

        $this->copyDefaultFolder();
    }

    private function defaultFiles()
    {
        return [
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
            ],
            [
                'origin' => 'views/assets/theme/pages/img/progress/progress-circle-master.svg',
                'fieldName' => 'progress-circle-master.svg',
                'destination' => 'progress/',
                'type' => self::TYPE_IMAGE
            ]
        ];
    }

    private function copyDefaultFolder(): void
    {
        $folders = [
            [
                'origin' => 'views/assets/theme/assets/plugins/font-awesome/fonts/',
                'destination' => $this->getWsContainerFolder() . self::TYPE_FONT
            ]
        ];

        foreach ($folders as $folder) {
            $destination = $this->rootPath . $folder['destination'];
            if ($this->copyToDir($this->rootPath . $folder['origin'], $destination)) {
                throw new \Exception("No fue posible copiar fonts", 200);
            }
        }
    }

    /**
     * Copia los archivos
     *
     * @param array $files : archivos a copiar
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function copyFiles(array $files): array
    {
        $destinationFiles = [];
        foreach ($files as $file) {

            $origin = $this->rootPath . $file["origin"];
            chmod($origin, PERMISOS_ARCHIVOS);

            $routeFolder = $this->getWsContainerFolder() . $file['type'] . $file['destination'] ?? '';
            $newDir = $this->rootPath . $routeFolder;

            if (!file_exists($newDir)) {
                crear_destino($newDir);
            }
            $destination =  $newDir . $file['fieldName'];

            if (!copy($origin, $destination)) {
                throw new \Exception("No fue posible copiar los archivos", 200);
            }
            chmod($destination, PERMISOS_ARCHIVOS);

            $destinationFiles[] = $routeFolder;
        }
        return $destinationFiles;
    }

    protected function registerFile(array $files, bool $custom = false): void
    {
        $index = $custom ? 'custom' : 'default';

        $this->registeredFiles[$index] = array_merge($this->registeredFiles[$index], $files);
    }

    /**
     * Copia un archivo o directorio
     *
     * @param string $source Carpeta/archivo origen
     * @param string $dest Carpeta/archivo destino
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public static function copyToDir(string $source, string $dest): bool
    {
        if (is_link($source)) {
            return symlink(readlink($source), $dest);
        }

        if (is_file($source)) {
            return copy($source, $dest);
        }

        if (!is_dir($dest)) {
            crear_destino($dest);
        }

        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            self::copyToDir("$source/$entry", "$dest/$entry");
        }

        $dir->close();

        return true;
    }
}
