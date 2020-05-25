<?php

namespace Saia\Pqr\webserviceGenerator;

class WsGenerator
{
    const DIRECTORY = "../ws/";
    const FOLDER_CSS = 'css/';
    const FOLDER_JS = 'js/';
    const FOLDER_IMAGE = 'img/';
    const FOLDER_FONT = 'fonts/';
    const FOLDER_CUSTOM = 'custom/';

    const LOADIN_ALL = 0;
    const LOADIN_SEARCH = 2;
    const LOADIN_FORM = 1;


    protected IWsHtml $IWsHtml;
    protected bool $generateSearch;
    protected string $nameFolderWs;
    protected string $rootPath;

    protected array $loadAdditionalFiles = [];
    protected array $additionalFiles = [];

    private string $nameForm = 'index';
    private string $nameSearchForm = 'buscar';

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
     * Cambia el nombre que tendra el archivo del formulario
     *
     * @param string $value
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function setNameForm(string $value)
    {
        $this->nameForm = $value;
    }

    /**
     * Cambia el nombre que tendra el archivo del formulario de busqueda
     *
     * @param string $value
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function setNameSearchForm(string $value)
    {
        $this->nameSearchForm = $value;
    }

    /**
     * Copia y caarga archivos personalizados al ws
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
    public function loadAdditionalFiles(array $files, int $loadIn = self::LOADIN_ALL): void
    {
        foreach ($files as $file) {
            if (!in_array($file, $this->loadAdditionalFiles[$loadIn])) {
                $SplFileInfo = new \SplFileInfo($file);
                array_push($this->loadAdditionalFiles[$loadIn], [
                    'origin' => $file,
                    'fileName' => $SplFileInfo->getFilename(),
                    'destinationFolder' => $this->getDestinationFolder($SplFileInfo->getExtension())
                ]);
            }
        }
    }

    /**
     * Copia los archivos especificados y los incluye a la carpeta del ws,
     * los archivos los copiara en una de las siguientes 3 carpetas
     * js, css o custom
     *
     * @param array $files
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function addFiles(array $files): void
    {
        foreach ($files as $file) {
            $SplFileInfo = new \SplFileInfo($file);
            array_push($this->additionalFiles, [
                'origin' => $file,
                'fileName' => $SplFileInfo->getFilename(),
                'destinationFolder' => $this->getDestinationFolder($SplFileInfo->getExtension(), true)
            ]);
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
        $files = $this->createFolders();

        if ($this->createForm($files['form'])) {
            if ($this->generateSearch) {
                $this->createSearchForm($files['search']);
            }
        }

        return true;
    }

    /**
     * Crea el formulario principal del ws
     *
     * @param array $filesToLoad: Archivos a cargar en el formulario
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function createForm(array $filesToLoad): bool
    {
        $contentForm = $this->IWsHtml->getHtmlContentForm(
            $filesToLoad,
            $this->generateSearch ? $this->getRouteSearch() : null
        );
        $fileName = "{$this->rootPath}{$this->getRouteForm()}";

        if (!file_put_contents($fileName, $contentForm)) {
            throw new \Exception("No fue posible crear el formulario", 200);
        }

        $contentJs = $this->IWsHtml->getJsContentForm();
        $fileNameJs = "{$this->rootPath}{$this->getRouteForm(false)}";

        if (!file_put_contents($fileNameJs, $contentJs)) {
            throw new \Exception("No fue posible crear el js del formulario", 200);
        }
        return true;
    }

    /**
     * Obtiene la ruta del archivo buscar del formulario relativa a la raiz
     *
     * @param boolean $html => si desea obtener la url del archivo html, false traera el js
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getRouteForm(bool $html = true): string
    {
        return $html ? $this->getWsContainerFolder() . $this->nameForm . ".html"
            :  $this->getWsContainerFolder() . self::FOLDER_JS . $this->nameForm . ".js";
    }

    /**
     * Crea el formulario de busqueda del ws
     *
     * @param array $filesToLoad: Archivos a cargar en el formulario de busqueda
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function createSearchForm(array $filesToLoad): bool
    {
        $contentForm = $this->IWsHtml->getHtmlContentSearchForm(
            $this->registeredFiles,
            $this->getRouteForm()
        );
        $fileName = "{$this->rootPath}{$this->getRouteSearch()}";

        if (!file_put_contents($fileName, $contentForm)) {
            throw new \Exception("No fue posible crear el formulario de busqueda", 200);
        }

        $contentJs = $this->IWsHtml->getJsContentSearchForm();
        $fileNameJs = "{$this->rootPath}{$this->getRouteSearch(false)}";

        if (!file_put_contents($fileNameJs, $contentJs)) {
            throw new \Exception("No fue posible crear el js de busqueda", 200);
        }
        return true;
    }

    /**
     * Obtiene la ruta del archivo buscar del formulario relativa a la raiz
     *
     * @param boolean $html si desea obtener la url del archivo html, false traera el js
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getRouteSearch(bool $html = true): string
    {
        return $html ? $this->getWsContainerFolder() . $this->nameSearchForm . ".html"
            :  $this->getWsContainerFolder() . self::FOLDER_JS . $this->nameSearchForm . ".js";
    }

    /**
     * Obtiene la carpeta principal donde se guardara todo el ws
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getWsContainerFolder(): string
    {
        return self::DIRECTORY . $this->nameFolderWs . "/";
    }

    /**
     * Determina la carpeta donde guardara el archivo
     *
     * @param string $extension, extension del archivo
     * @param string $skip
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     * @throws Exception
     */
    protected function getDestinationFolder(string $extension, $skip = false): string
    {
        switch ($extension) {
            case 'css':
                $folder = self::FOLDER_CSS;
                break;
            case 'js':
                $folder = self::FOLDER_JS;
                break;
            default:
                if (!$skip)
                    throw new \Exception("No se permite cargan archivos diferentes de css o js", 1);
                break;
        }
        return $folder;
    }

    /**
     * Copia los archivos y crea la estructura de carpeta del ws
     * 
     *
     * @return array 
     *     [
     *      'form'=>array con los archivos que se pasaran al IWsHtml que deberan cargar en el formulario
     *      'search'=>array con los archivos que se pasaran al IWsHtml 
     *                que deberan cargar en el formulario de busqueda
     *     ]
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function createFolders(): array
    {
        $destinationFiles = $this->copyFiles($this->defaultFiles());

        $files = [
            'form' => $destinationFiles,
            'search' => $destinationFiles
        ];


        if ($this->loadAdditionalFiles) {
            if ($this->loadAdditionalFiles[self::LOADIN_ALL]) {
                $destinationFiles = $this->copyFiles(($this->additionalFiles[self::LOADIN_ALL]));
                $files['form'] = array_merge($files['form'], $destinationFiles);
                $files['search'] = array_merge($files['search'], $destinationFiles);
            }

            if ($this->loadAdditionalFiles[self::LOADIN_FORM]) {
                $destinationFiles = $this->copyFiles(($this->additionalFiles[self::LOADIN_FORM]));
                $files['form'] = array_merge($files['form'], $destinationFiles);
            }

            if ($this->loadAdditionalFiles[self::LOADIN_SEARCH]) {
                $destinationFiles = $this->copyFiles(($this->additionalFiles[self::LOADIN_SEARCH]));
                $files['search'] = array_merge($files['search'], $destinationFiles);
            }
        }

        if ($this->additionalFiles) {
            $this->copyFiles(($this->additionalFiles));
        }

        $this->copyDefaultFolder();

        return $files;
    }

    /**
     * Archivos que deben cargarse siempre para el buen funcionamiento del ws
     * como lo son bootstrap, jquery, etc
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function defaultFiles(): array
    {
        return [
            [
                'origin' => 'views/assets/node_modules/jquery/dist/jquery.min.js',
                'fileName' => 'jquery.min.js',
                'destinationFolder' => self::FOLDER_JS
            ],
            [
                'origin' => 'views/assets/node_modules/bootstrap/dist/js/bootstrap.min.js',
                'fileName' => 'bootstrap.min.js',
                'destinationFolder' => self::FOLDER_JS
            ],
            [
                'origin' => 'views/assets/node_modules/bootstrap/dist/css/bootstrap.min.css',
                'fileName' => 'bootstrap.min.css',
                'destinationFolder' => self::FOLDER_CSS
            ],
            [
                'origin' => 'views/assets/node_modules/jquery-validation/dist/jquery.validate.min.js',
                'fileName' => 'jquery.validate.min.js',
                'destinationFolder' => self::FOLDER_JS
            ],
            [
                'origin' => 'views/assets/node_modules/jquery-validation/dist/localization/messages_es.min.js',
                'fileName' => 'jquery.messages_es.min.js',
                'destinationFolder' => self::FOLDER_JS
            ],
            [
                'origin' => 'views/assets/theme/pages/css/pages.min.css',
                'fileName' => 'pages.min.css',
                'destinationFolder' => self::FOLDER_CSS
            ],
            [
                'origin' => 'views/assets/node_modules/izitoast/dist/css/iziToast.min.css',
                'fileName' => 'iziToast.min.css',
                'destinationFolder' => self::FOLDER_CSS
            ],
            [
                'origin' => 'views/assets/node_modules/izitoast/dist/js/iziToast.min.js',
                'fileName' => 'iziToast.min.js',
                'destinationFolder' => self::FOLDER_JS
            ],
            [
                'origin' => 'views/assets/theme/assets/plugins/font-awesome/css/font-awesome.min.css',
                'fileName' => 'font-awesome.min.css',
                'destinationFolder' => self::FOLDER_CSS
            ],
            [
                'origin' => 'views/assets/theme/pages/img/progress/progress-circle-master.svg',
                'fileName' => 'progress-circle-master.svg',
                'destinationFolder' => self::FOLDER_IMAGE,
                'subFolderDestination' => 'progress/'
            ]
        ];
    }

    /**
     * Carpeta que debe copiarse para el funcionamiento
     * del font-awesome
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function copyDefaultFolder(): void
    {
        $folders = [
            [
                'origin' => 'views/assets/theme/assets/plugins/font-awesome/fonts/',
                'destination' => $this->getWsContainerFolder() . self::FOLDER_FONT
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
     * Copia los archivos con estructura
     * [
     *  'origin'=>url archivo a copiar,
     *  'fileName'=> nombre final del archivo
     *  'folderDestination'=>carpeta donde quedara guardado el archivo
     * ]
     *
     * @param array $files : archivos a copiar
     * @return array ubicacion final de los archivos copiados
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function copyFiles(array $files): array
    {
        $destinationFiles = [];
        foreach ($files as $file) {

            $origin = $this->rootPath . $file["origin"];
            chmod($origin, PERMISOS_ARCHIVOS);

            $routeFolder = $this->getWsContainerFolder() . $file['destinationFolder'] . $file['subFolderDestination'] ?? '';
            $newDir = $this->rootPath . $routeFolder;

            if (!file_exists($newDir)) {
                crear_destino($newDir);
            }
            $destination =  $newDir . $file['fileName'];

            if (!copy($origin, $destination)) {
                throw new \Exception("No fue posible copiar los archivos", 200);
            }
            chmod($destination, PERMISOS_ARCHIVOS);

            $destinationFiles[] = $routeFolder;
        }
        return $destinationFiles;
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
