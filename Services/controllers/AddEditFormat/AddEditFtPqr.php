<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat;

use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Exception\SaiaException;
use Saia\controllers\generator\component\Distribution;
use Saia\controllers\generator\component\Hidden;
use Saia\controllers\generator\component\Rad;
use Saia\models\formatos\CampoOpciones;
use Saia\models\formatos\Formato;
use App\Bundles\pqr\Services\models\PqrFormField;
use Saia\controllers\SessionController;
use Saia\models\formatos\CamposFormato;
use Doctrine\DBAL\Types\Types;

class AddEditFtPqr implements IAddEditFormat
{
    /**
     * Intancia de PqrForm
     */
    private PqrForm $PqrForm;

    /**
     * @param PqrForm $PqrForm
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function __construct(PqrForm $PqrForm)
    {
        $this->PqrForm = $PqrForm;
    }

    /**
     * @inheritDoc
     */
    public function updateChange(): bool
    {
        $this->PqrForm->fk_formato ?
            $this->updateForm() : $this->createForm();

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getFormat(): Formato
    {
        return $this->PqrForm->getFormatoFk();
    }

    /**
     * Genera la creacion del formato
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function createForm(): void
    {
        $this->createRecordInFormat()
            ->addEditRecordsInFormatFields()
            ->addOtherFields();
    }

    /**
     * Actualiza la generacion del formato
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function updateForm(): void
    {
        $this->updateRecordInFormat()
            ->addEditRecordsInFormatFields()
            ->addOtherFields();
    }


    /**
     * Obtiene los datos por defecto para la creacion del registro en Formato
     *
     * @param bool         $edit
     * @param Formato|null $Formato
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getFormatDefaultData(bool $edit = false, ?Formato $Formato = null): array
    {
        $name = $this->PqrForm->name;
        $data = [
            'nombre'                    => $name,
            'etiqueta'                  => $this->PqrForm->label,
            'cod_padre'                 => 0,
            'contador_idcontador'       => $this->PqrForm->fk_contador,
            'nombre_tabla'              => "ft_$name",
            'ruta_mostrar'              => "views/modules/pqr/formatos/$name/mostrar.php",
            'ruta_editar'               => "views/modules/pqr/formatos/$name/editar.html",
            'ruta_adicionar'            => "views/modules/pqr/formatos/$name/adicionar.html",
            'ruta_buscar'               => "views/modules/pqr/formatos/$name/buscar.html",
            'encabezado'                => 1,
            'cuerpo'                    => '<p>{*showContent*}</p><p>{*mostrar_estado_proceso*}</p>',
            'pie_pagina'                => 0,
            'margenes'                  => '25,25,30,25',
            'orientacion'               => 0,
            'papel'                     => 'Letter',
            'funcionario_idfuncionario' => SessionController::getValue('idfuncionario'),
            'detalle'                   => 0,
            'tipo_edicion'              => 0,
            'item'                      => 0,
            'font_size'                 => 11,
            'banderas'                  => 'e', //Aprobacion automatica
            'mostrar_pdf'               => 1,
            'orden'                     => 0,
            'fk_categoria_formato'      => '3',
            'paginar'                   => 0,
            'pertenece_nucleo'          => 0,
            'descripcion_formato'       => 'Modulo de PQR',
            'version'                   => 1,
            'module'                    => 'pqr',
            'publicar'                  => 1,
            'formato_fecha_radicado'    => 'Ymd',
            'webservice'                => 1,
            'clase_ws'                  => 'App\Bundles\pqr\Services\generadoresWs\GenerateWsPqr'
        ];

        if ($edit) {
            if ($Formato->clase_ws) {
                unset($data['clase_ws']);
            }
            unset($data['contador_idcontador']);
            unset($data['encabezado']);
            unset($data['pie_pagina']);
            unset($data['margenes']);
            unset($data['orientacion']);
            unset($data['papel']);
            unset($data['funcionario_idfuncionario']);
            unset($data['fk_categoria_formato']);
            unset($data['formato_fecha_radicado']);
        }

        return $data;
    }

    /**
     * Crea el registro en Formato
     *
     * @return self
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function createRecordInFormat(): self
    {
        $FormatoService = (new Formato())->getService();
        $FormatoService->save($this->getFormatDefaultData());

        $id = $FormatoService->getModel()->getPK();

        $this->PqrForm->setAttributes([
            'fk_formato' => $id
        ]);
        $this->PqrForm->save();

        if (!$Respuesta = Formato::findByAttributes([
            'nombre' => 'pqr_respuesta'
        ])) {
            throw new SaiaException("No se encontro el formato RESPUESTA PQR", 1);
        }

        $Respuesta->setAttributes([
            'cod_padre' => $id
        ]);
        $Respuesta->save();

        return $this;
    }

    /**
     * Actualiza el registro en Formato
     *
     * @return self
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function updateRecordInFormat(): self
    {
        $Formato = new Formato($this->PqrForm->fk_formato);
        $Formato->setAttributes($this->getFormatDefaultData(true, $Formato));
        $Formato->save();

        return $this;
    }

    /**
     * Adiciona o actualiza los registros para la creacion de los campos del formulario
     *
     * @return self
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function addEditRecordsInFormatFields(): self
    {
        $fields = $this->PqrForm->getPqrFormFields();
        foreach ($fields as $PqrFormField) {
            if (!$PqrFormField->fk_campos_formato) {
                $this->createRecordInFormatFields($PqrFormField);
            } else {
                $this->updateRecordInFormatFields($PqrFormField);
            }

            if (
                $PqrFormField->getPqrHtmlField()->isValidForOptions()
                && $PqrFormField->getSetting()->options
            ) {
                $PqrFormField->getService()->addEditformatOptions();
            }
        }
        return $this;
    }

    /**
     * Resuelve la clase a utilizar basado en la
     * etiqueta html del campo
     *
     * @param String $fieldType
     * @return string|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function resolveClass(string $fieldType): ?string
    {
        $className = "App\\Bundles\\pqr\\Services\\controllers\\AddEditFormat\\fields\\$fieldType";
        if (class_exists($className)) {
            return $className;
        }
        return null;
    }

    /**
     * Obtiene los datos por defecto para la creacion o actualizacion de un campo del formulario
     *
     * @param PqrFormField $PqrFormField
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getFormatFieldData(PqrFormField $PqrFormField): array
    {
        $fieldType = $PqrFormField->getPqrHtmlField()->type_saia;

        if (!$className = $this->resolveClass($fieldType)) {
            throw new SaiaException("No se encontro la clase para el tipo $fieldType", 1);
        }
        $Fields = new $className($PqrFormField);

        return $Fields->getValues();
    }

    /**
     * Crea un nuevo campo del formulario
     *
     * @param PqrFormField $PqrFormField
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function createRecordInFormatFields(PqrFormField $PqrFormField): void
    {
        $CamposFormatoService = (new CamposFormato())->getService();
        $CamposFormatoService->save($this->getFormatFieldData($PqrFormField));
        $id = $CamposFormatoService->getModel()->getPK();

        $PqrFormField->setAttributes([
            'fk_campos_formato' => $id
        ]);

        $PqrFormField->save();
    }

    /**
     * Adiciona campos adicionales predeterminados
     * al formulario
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function addOtherFields(): void
    {
        $fields = [
            'sys_estado'                    => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'fila_visible'      => 0,
                'obligatoriedad'    => 0,
                'orden'             => 0,
                'nombre'            => 'sys_estado',
                'etiqueta'          => 'Estado de la PQR',
                'tipo_dato'         => 'string',
                'longitud'          => '30',
                'predeterminado'    => FtPqr::ESTADO_PENDIENTE,
                'etiqueta_html'     => Hidden::getIdentification(),
                'acciones'          => null,
                'placeholder'       => 'Estado de la PQR',
                'listable'          => 1,
                'opciones'          => null,
                'ayuda'             => null,
                'longitud_vis'      => null
            ],
            'sys_tercero'                   => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'fila_visible'      => 0,
                'obligatoriedad'    => 0,
                'orden'             => 0,
                'nombre'            => 'sys_tercero',
                'etiqueta'          => 'Destinatario de la Respuesta',
                'tipo_dato'         => 'integer',
                'longitud'          => '11',
                'predeterminado'    => 0,
                'etiqueta_html'     => Hidden::getIdentification(),
                'acciones'          => null,
                'placeholder'       => 'Destinatario de la respuesta',
                'listable'          => 1,
                'opciones'          => null,
                'ayuda'             => null,
                'longitud_vis'      => null
            ],
            'sys_fecha_vencimiento'         => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'fila_visible'      => 0,
                'obligatoriedad'    => 0,
                'orden'             => 0,
                'nombre'            => 'sys_fecha_vencimiento',
                'etiqueta'          => 'Fecha vecimiento PQR',
                'tipo_dato'         => 'datetime',
                'longitud'          => null,
                'predeterminado'    => null,
                'etiqueta_html'     => Hidden::getIdentification(),
                'acciones'          => null,
                'placeholder'       => null,
                'listable'          => 1,
                'opciones'          => '{"hoy":false,"tipo":"date"}',
                'ayuda'             => null,
                'longitud_vis'      => null
            ],
            'sys_fecha_terminado'           => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'fila_visible'      => 0,
                'obligatoriedad'    => 0,
                'orden'             => 0,
                'nombre'            => 'sys_fecha_terminado',
                'etiqueta'          => 'Fecha Terminacion PQR',
                'tipo_dato'         => 'datetime',
                'longitud'          => null,
                'predeterminado'    => null,
                'etiqueta_html'     => Hidden::getIdentification(),
                'acciones'          => null,
                'placeholder'       => null,
                'listable'          => 1,
                'opciones'          => '{"hoy":false,"tipo":"date"}',
                'ayuda'             => null,
                'longitud_vis'      => null
            ],
            'sys_anonimo'                   => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'fila_visible'      => 0,
                'obligatoriedad'    => 0,
                'orden'             => 0,
                'nombre'            => 'sys_anonimo',
                'etiqueta'          => 'Anonimo',
                'tipo_dato'         => 'integer',
                'longitud'          => 1,
                'predeterminado'    => 0,
                'etiqueta_html'     => Hidden::getIdentification(),
                'acciones'          => null,
                'placeholder'       => null,
                'listable'          => 1,
                'opciones'          => '{"type":"hidden"}',
                'ayuda'             => null,
                'longitud_vis'      => null
            ],
            'sys_frecuencia'                => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'fila_visible'      => 0,
                'obligatoriedad'    => 0,
                'orden'             => 0,
                'nombre'            => 'sys_frecuencia',
                'etiqueta'          => 'Frecuencia de la PQR',
                'tipo_dato'         => 'integer',
                'longitud'          => 1,
                'predeterminado'    => 0,
                'etiqueta_html'     => Hidden::getIdentification(),
                'acciones'          => null,
                'placeholder'       => 'Frecuencia de la PQR',
                'listable'          => 1,
                'opciones'          => null,
                'ayuda'             => '1,Bajo 2,Medio 3,Alto',
                'longitud_vis'      => null
            ],
            'sys_impacto'                   => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'fila_visible'      => 0,
                'obligatoriedad'    => 0,
                'orden'             => 0,
                'nombre'            => 'sys_impacto',
                'etiqueta'          => 'Impacto de la PQR',
                'tipo_dato'         => 'integer',
                'longitud'          => 1,
                'predeterminado'    => 0,
                'etiqueta_html'     => Hidden::getIdentification(),
                'acciones'          => null,
                'placeholder'       => 'Impacto de la PQR',
                'listable'          => 1,
                'opciones'          => null,
                'ayuda'             => '1,Bajo 2,Medio 3,Alto',
                'longitud_vis'      => null
            ],
            'sys_severidad'                 => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'fila_visible'      => 0,
                'obligatoriedad'    => 0,
                'orden'             => 0,
                'nombre'            => 'sys_severidad',
                'etiqueta'          => 'Severidad de la PQR',
                'tipo_dato'         => 'integer',
                'longitud'          => 1,
                'predeterminado'    => 0,
                'etiqueta_html'     => Hidden::getIdentification(),
                'acciones'          => null,
                'placeholder'       => 'Severidad de la PQR',
                'listable'          => 1,
                'opciones'          => null,
                'ayuda'             => '1,Bajo 2,Medio 3,Alto',
                'longitud_vis'      => null
            ],
            'sys_oportuno'                  => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'fila_visible'      => 0,
                'obligatoriedad'    => 0,
                'orden'             => 0,
                'nombre'            => 'sys_oportuno',
                'etiqueta'          => 'Oportunidad en las respuestas',
                'tipo_dato'         => 'string',
                'longitud'          => 50,
                'predeterminado'    => FtPqr::OPORTUNO_PENDIENTES_SIN_VENCER,
                'etiqueta_html'     => Hidden::getIdentification(),
                'acciones'          => null,
                'placeholder'       => 'Oportunidad en las respuestas',
                'listable'          => 1,
                'opciones'          => null,
                'ayuda'             => null,
                'longitud_vis'      => null
            ],
            'radicacion'                    => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'fila_visible'      => 1,
                'obligatoriedad'    => 0,
                'orden'             => 0,
                'nombre'            => 'radicacion',
                'etiqueta'          => 'Radiación',
                'tipo_dato'         => 'string',
                'longitud'          => 255,
                'predeterminado'    => null,
                'etiqueta_html'     => Rad::getIdentification(),
                'acciones'          => 'a,e,b',
                'placeholder'       => null,
                'listable'          => 0,
                'opciones'          => null,
                'ayuda'             => null,
                'longitud_vis'      => null
            ],
            Rad::DESCRIPCION                => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'nombre'            => Rad::DESCRIPCION,
                'etiqueta'          => 'Asunto',
                'tipo_dato'         => Types::STRING,
                'longitud'          => 255,
                'obligatoriedad'    => 1,
                'acciones'          => 'a,e,p',
                'etiqueta_html'     => Hidden::getIdentification(),
                'orden'             => 108,
                'fila_visible'      => 1,
                'placeholder'       => 'campo texto',
                'listable'          => 0
            ],
            Rad::DISTRIBUCION               => Rad::getAttributesMoreFields(
                $this->PqrForm->fk_formato,
                Rad::DISTRIBUCION
            ),
            Distribution::DESTINO_INTERNO   => array_merge(Distribution::getAttributesMoreFields(
                $this->PqrForm->fk_formato,
                Distribution::DESTINO_INTERNO
            ), [
                'opciones' => '{"tipo_seleccion":"unico","multiple_destino":false,"dependenciaCargo":true}'
            ]),
            Distribution::SELECT_MENSAJERIA => array_merge(Distribution::getAttributesMoreFields(
                $this->PqrForm->fk_formato,
                Distribution::SELECT_MENSAJERIA
            ), [
                'campoOpciones' => Distribution::getAttributesMoreFieldsOptions(0, Distribution::SELECT_MENSAJERIA)
            ]),
            Rad::COLILLA                    => array_merge(Rad::getAttributesMoreFields(
                $this->PqrForm->fk_formato,
                Rad::COLILLA
            ), [
                'campoOpciones' => Rad::getAttributesMoreFieldsOptions(0, Rad::COLILLA)
            ]),
            Rad::DIGITALIZACION             => Rad::getAttributesMoreFields(
                $this->PqrForm->fk_formato,
                Rad::DIGITALIZACION
            )
        ];

        foreach ($fields as $name => $data) {
            $options = $data['campoOpciones'] ?? ($data['campoOpciones'] = []);
            unset($data['campoOpciones']);

            if ($CamposFormato = CamposFormato::findByAttributes([
                'nombre'            => $name,
                'formato_idformato' => $this->PqrForm->fk_formato
            ])) {
                $CamposFormato->setAttributes($data);
                $CamposFormato->save();

                if ($options) {
                    $this->createOrUpdateOptions($options, $CamposFormato->getPK());
                }
            } else {
                $CamposFormatoService = (new CamposFormato())->getService();
                $CamposFormatoService->save($data);
                if ($options) {
                    $this->createOrUpdateOptions($options, $CamposFormatoService->getModel()->getPK());
                }
            }
        }
    }

    /**
     * @param array $options
     * @param int   $fieldId
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-10-19
     */
    public function createOrUpdateOptions(array $options, int $fieldId)
    {
        foreach ($options as $option) {
            $option['fk_campos_formato'] = $fieldId;
            $CampoOpciones = CampoOpciones::findByAttributes([
                'llave'             => $option['llave'],
                'fk_campos_formato' => $fieldId
            ]);

            if ($CampoOpciones) {
                $CampoOpciones->setAttributes($option);
                $CampoOpciones->save();
            } else {
                $CampoOpcionesService = (new CampoOpciones())->getService();
                $CampoOpcionesService->save($option);
            }
        }
    }

    /**
     * Actualiza un campo del formulario
     *
     * @param PqrFormField $PqrFormField
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function updateRecordInFormatFields(PqrFormField $PqrFormField): void
    {
        $CamposFormato = new CamposFormato($PqrFormField->fk_campos_formato);
        $CamposFormato->setAttributes($this->getFormatFieldData($PqrFormField));
        $CamposFormato->save();
    }
}
