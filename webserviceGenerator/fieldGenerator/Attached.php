<?php

namespace Saia\Pqr\webserviceGenerator\fieldGenerator;

use Saia\models\formatos\CamposFormato;
use Saia\Pqr\webserviceGenerator\IWsFields;

class Attached extends Field implements IWsFields
{
    public function __construct(CamposFormato $CamposFormato)
    {
        parent::__construct($CamposFormato);
        $this->divDropzone = "dropzone_{$this->CamposFormato->nombre}";
    }

    public function aditionalFiles(): array
    {
        return [];
    }

    public function htmlContent(): string
    {

        $title = $this->CamposFormato->ayuda ? " title='{$this->CamposFormato->ayuda}'" : '';
        $code = <<<PHP
        <div class='form-group form-group-default {$this->getRequiredClass()}' id='group_{$this->CamposFormato->nombre}'>
            <label{$title}>{$this->getLabel()}</label>
            <div class="dropzone" id="{$this->divDropzone}"></div>
            <input type="hidden" class="{$this->getRequiredClass()}" id="{$this->CamposFormato->nombre}" name="{$this->CamposFormato->nombre}">
        </div>
PHP;

        return $code;
    }

    public function jsContent()
    {
        $code = <<<JAVASCRIPT
            let baseUrl = $('script[data-baseurl]').data('baseurl');
            let options = {$this->CamposFormato->opciones};
            let loaded{$this->divDropzone}= [];

            let {$this->divDropzone} = new Dropzone("#{$this->divDropzone}", {
                url: baseUrl + 'app/temporal/cargar_anexos.php',
                dictDefaultMessage: 'Haga clic para elegir un archivo o Arrastre acá el archivo.',
                maxFilesize: options.longitud,
                maxFiles: options.cantidad,
                acceptedFiles: options.tipos,
                addRemoveLinks: true,
                dictRemoveFile: 'Eliminar',
                dictFileTooBig: 'Tamaño máximo {{maxFilesize}} MB',
                dictMaxFilesExceeded: 'Máximo'+options.cantidad+'archivos',
                params: {
                    token: localStorage.getItem('token'),
                    key: localStorage.getItem('key'),
                    dir: '{$this->CamposFormato->getFormat()->nombre}'
                },
                paramName: 'file',
                init: function () {

                    this.on('success', function (file, response) {
                        response = JSON.parse(response);

                        if (response.success) {
                            response.data.forEach(e => {
                                loaded{$this->divDropzone}.push(e);
                            });
                            $("[name='<?= $this->CamposFormato->nombre ?>']").val(
                                loaded{$this->divDropzone}.join(',')
                            )
                            // Download link
                            var anchorEl = document.createElement('a');
                            anchorEl.setAttribute('href', baseUrl + response.data[0]);
                            anchorEl.setAttribute('target', '_blank');
                            anchorEl.innerHTML = "Descargar";
                            anchorEl.classList.add('dz-remove');
                            file.previewTemplate.appendChild(anchorEl);
                        } else {
                            window.notification({
                                type: 'error',
                                message: response.message
                            });
                        }
                    });

                    this.on('removedfile', function (file) {
                        if (file.route) { //si elimina un anexo cargado antes
                            var index = loaded{$this->divDropzone}.findIndex(route => route == file.route);
                        } else {//si elimina un anexo recien cargado
                            var index = loaded{$this->divDropzone}.findIndex(route => file.status == 'success' && route.indexOf(file.upload.filename) != -1);
                        }

                        loaded{$this->divDropzone} = loaded{$this->divDropzone}.filter((e, i) => i != index);
                        $("[name='{$this->CamposFormato->nombre}']").val(
                            loaded{$this->divDropzone}.join(',')
                        );
                    });

                    this.on('maxfilesexceeded', function () {
                        $('.dz-error').remove();
                        window.notification({
                            type: 'error',
                            message: 'Ha superado el número máximo de anexos permitidos'
                        });
                    });
                }
            });
JAVASCRIPT;

        return $code;
    }
}
