<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\Services\models\PqrResponseTime;
use App\services\GlobalContainer;
use App\services\models\ModelService\ModelService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\models\PqrHtmlField;
use Saia\models\formatos\CampoOpciones;

class PqrFormFieldService extends ModelService
{

    /**
     * Bandera que indica el numero minimo donde empezara el orden de los campos
     */
    const INITIAL_ORDER = 2;
    const DEFAULT_DAY = 15;

    /**
     * Obtiene la instancia de PqrFormField actualizada
     *
     * @return PqrFormField
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getModel(): PqrFormField
    {
        return $this->Model;
    }

    /**
     * Crea el registro en la DB
     *
     * @param array $attributes
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    public function create(array $attributes): bool
    {
        if (!isset($attributes['fk_pqr_form'])) {
            $this->getErrorManager()->setMessage("Falta el identificador del formulario");
            return false;
        }

        $PqrForm = new PqrForm($attributes['fk_pqr_form']);

        $defaultFields = [
            'name' => $this->generateName(trim(strtolower($attributes['label']))),
            'required' => 0,
            'anonymous' => 0,
            'fk_pqr_form' => $PqrForm->getPK(),
            'fk_campos_formato' => 0,
            'is_system' => 0,
            'orden' => ($PqrForm->countFields()) + self::INITIAL_ORDER,
            'active' => 1
        ];
        $attributes = array_merge($defaultFields, $attributes);

        return $this->update($attributes);
    }

    /**
     * Actualiza un registro
     *
     * @param array $attributes
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    public function update(array $attributes): bool
    {

        if (isset($attributes['setting'])) {
            $attributes['setting'] = json_encode($attributes['setting']);
        }

        $this->getModel()->setAttributes($attributes);

        return $this->getModel()->save();
    }

    /**
     * Elimina un campo del formulario
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function delete(): bool
    {
        if ($this->getModel()->delete()) {
            if ($this->getModel()->fk_campos_formato) {
                if (!$this->getModel()->getCamposFormato()->delete()) {
                    $this->getErrorManager()->setMessage("No fue posible eliminar el campo");
                    return false;
                }
            }
            return true;
        } else {
            $this->getErrorManager()->setMessage("No fue posible eliminar");
            return false;
        }
    }

    /**
     * Actualiza el estado(active) del campo
     *
     * @param int $status
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function updateActive(int $status): bool
    {
        $attributes = [
            'active' => $status,
            'required' => 0,
            'required_anonymous' => 0
        ];

        if (
            $this->getModel()->name != 'sys_subtipo'
            && $this->getModel()->name != 'sys_dependencia'
        ) {
            $attributes['show_report'] = 0;
        }

        if (!$status && $this->isFieldTime()) {
            $this->getModel()->getPqrForm()->getService()
                ->editFieldTime(PqrFormField::getSysTipoField()->fk_campos_formato);
        }

        return $this->update($attributes);
    }


    /**
     * Valida si el campo es el campo que define los tiempos de respuesta
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-09
     */
    private function isFieldTime(): bool
    {
        $idCampoFormato = $this->getModel()->fk_campos_formato;
        $idField = $this->getModel()->getPqrForm()->fk_field_time;

        return $idCampoFormato == $idField;
    }

    /**
     * genera un nombre unico para el campo del formulario
     *
     * @param string  $label
     * @param integer $pref
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function generateName(string $label, int $pref = 0): string
    {
        $cadena = trim(preg_replace('/[^a-z]/', '_', $label), '_');
        $cadena = implode('_', array_filter(explode('_', $cadena)));
        $cadena = trim(substr($cadena, 0, 15), '_');

        $name = $pref ? "{$cadena}_$pref" : $cadena;

        if ($this->isReservedWords($name)) {
            $name = $pref ? "{$cadena}_$pref" : "{$cadena}_1";
        }

        if (PqrFormField::findAllByAttributes([
            'name' => $name
        ])) {
            $pref++;
            $name = $this->generateName($name, $pref);
        }

        if ($this->columnExistsDB($name)) {
            $pref++;
            $name = $this->generateName($name, $pref);
        }

        return $name;
    }

    /**
     * Palabras reservadas que no se deben usar
     *
     * @param string $label
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    private function isReservedWords(string $label): bool
    {
        $reservedWords = [
            'select',
            'from',
            'where',
            'and',
            'in',
            'or',
            'like',
            'is',
            'system',
            'uniq',
            'numero',
            'fecha',
            'idft'
        ];

        return in_array($label, $reservedWords);
    }

    /**
     * Valida si la columna existe en la DB
     *
     * @param string $name
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    private function columnExistsDB(string $name): bool
    {
        $schema = GlobalContainer::getConnection()->getSchemaManager();
        $Table = $schema->listTableDetails('ft_pqr');

        return $Table->hasColumn($name);
    }

    /**
     * Retorna listado de valores para los campos autocompletar
     *
     * @param array $data
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getListDataForAutocomplete(array $data = []): array
    {
        $list = [];

        switch ($this->getModel()->getPqrHtmlField()->type) {
            case PqrHtmlField::TYPE_DEPENDENCIA:
                $list = $this->getDependencys($this->getModel()->getSetting(), $data);
                break;

            case PqrHtmlField::TYPE_LOCALIDAD:
                $list = $this->getListLocalidad($this->getModel()->getSetting(), $data);
                break;
        }

        return $list;
    }


    /**
     * Obtiene listado de localidades basados en la configuracion
     * del campo
     *
     * @param object $ObjSettings
     * @param array  $data
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getDependencys(object $ObjSettings, array $data = []): array
    {
        $Qb = GlobalContainer::getConnection()
            ->createQueryBuilder()
            ->select('iddependencia as id,nombre as text')
            ->from('dependencia');

        if ($data['id']) {
            $Qb->where('iddependencia=:iddependencia')
                ->setParameter(':iddependencia', $data['id'], Type::getType('integer'));

            return $Qb->execute()->fetchAllAssociative();
        }

        $Qb->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($data['term']) {
            $Qb->andWhere('nombre like :nombre')
                ->setParameter(':nombre', '%' . $data['term'] . '%', Type::getType('string'));
        }

        if (!$ObjSettings->allDependency) {
            $records = $ObjSettings->options;
            $ids = [];
            foreach ($records as $row) {
                $ids[] = $row->id;
            }
            $Qb->andWhere('iddependencia in (:ids)')
                ->setParameter(':ids', $ids, Connection::PARAM_INT_ARRAY);
        }

        return $Qb->execute()->fetchAllAssociative();
    }

    /**
     * Obtiene las localidades basados en la configuracion
     * del campo
     *
     * @param object $ObjSettings
     * @param array  $data
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getListLocalidad(object $ObjSettings, array $data = []): array
    {
        $Qb = GlobalContainer::getConnection()
            ->createQueryBuilder()
            ->select("CONCAT(a.nombre,
            CONCAT(
                ' - ',
                CONCAT(
                    b.nombre,
                    CONCAT(
                        ' - ',
                        c.nombre   
                    )
                )
            )
        ) AS text", "a.idmunicipio as id")
            ->from('municipio', 'a')
            ->join('a', 'departamento', 'b', 'a.departamento_iddepartamento = b.iddepartamento')
            ->join('b', 'pais', 'c', 'b.pais_idpais = c.idpais');

        if ($data['id']) {
            $Qb->andWhere('idmunicipio=:idmunicipio')
                ->setParameter(':idmunicipio', $data['id'], Type::getType('integer'));

            return $Qb->execute()->fetchAllAssociative();
        }

        $Qb->where("CONCAT(a.nombre,CONCAT(' ',b.nombre)) like :query")
            ->andWhere('a.estado = 1 AND b.estado = 1 AND c.estado = 1')
            ->setParameter('query', "%{$data['term']}%")
            ->orderBy('a.nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if (!$ObjSettings->allCountry) {
            $Qb->andWhere('c.idpais=:idpais')
                ->setParameter(':idpais', $ObjSettings->country->id);
        }

        return $Qb->execute()->fetchAllAssociative();
    }

    /**
     * Crea o edita las opciones de tipo select, radio y checkbox
     *
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-05
     */
    public function addEditformatOptions(): void
    {
        $PqrFormField = $this->getModel();
        $CampoFormato = $PqrFormField->getCamposFormato();
        $llave = 0;
        foreach ($CampoFormato->getCampoOpciones() as $CampoOpciones) {

            if ((int)$CampoOpciones->llave > $llave) {
                $llave = (int)$CampoOpciones->llave;
            }
            if ((int)$CampoOpciones->estado) {
                $CampoOpciones->setAttributes([
                    'estado' => 0
                ]);
                $CampoOpciones->save();
            }
        }

        $data = $values = [];
        foreach ($PqrFormField->getSetting()->options as $option) {

            if ($CampoOpciones = CampoOpciones::findByAttributes([
                'valor' => $option->text,
                'fk_campos_formato' => $CampoFormato->getPK()
            ])) {
                $CampoOpcionesService = $CampoOpciones->getService();
                $CampoOpcionesService->save([
                    'estado' => 1
                ]);
                $id = $CampoOpcionesService->getModel()->llave;
            } else {
                $id = $llave + 1;
                $llave = $id;

                $CampoOpcionesService = (new CampoOpciones())->getService();
                $CampoOpcionesService->save([
                    'llave' => $id,
                    'valor' => $option->text,
                    'fk_campos_formato' => $CampoFormato->getPK(),
                    'estado' => 1
                ]);
            }

            $data[] = [
                'llave' => $id,
                'item' => $option->text
            ];
            $values[] = "$id,$option->text";
        }

        $CampoFormato->setAttributes([
            'opciones' => json_encode($data),
            'valor' => implode(';', $values)
        ]);
        $CampoFormato->save();

        if ($PqrFormField->getPqrHtmlField()->isValidFieldForResponseDays()) {
            $this->addEditPqrResponseTimes();
        }
    }

    /**
     * Inicializa los tiempos de respuesta
     *
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-06
     */
    private function addEditPqrResponseTimes(): void
    {
        if ($this->getModel()->name == PqrFormField::FIELD_NAME_SYS_TIPO) {
            $this->addEditPqrResponseTimesForSysTipo();
        } else {
            $this->addEditPqrResponseTimesForOtherFields();
        }
    }

    /**
     * Adiciona o edita los tiempos por defecto del campo por defecto sys_tipo
     *
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-06
     */
    private function addEditPqrResponseTimesForSysTipo(): void
    {
        $sysTipoOptions = $this->getSysTipoOptions();

        PqrResponseTime::executeUpdate([
            'active' => 0
        ], [
            'fk_campo_opciones' => -1
        ]);

        foreach ($sysTipoOptions as $Option) {
            if (!$Option->estado) {
                continue;
            }

            $PqrResponseTime = PqrResponseTime::findByAttributes([
                'fk_campo_opciones' => -1,
                'fk_sys_tipo' => $Option->getPK(),
            ]);

            if ($PqrResponseTime) {
                if (!$PqrResponseTime->active) {
                    $PqrResponseTime->getService()->save([
                        'active' => 1
                    ]);
                }
            } else {
                $PqrResponseTimeService = (new PqrResponseTime)->getService();
                $PqrResponseTimeService->save([
                    'fk_campo_opciones' => -1,
                    'fk_sys_tipo' => $Option->getPK(),
                    'number_days' => $this->getDaysForSystipo($Option->valor),
                    'active' => 1
                ]);
            }
        }


    }

    /**
     * Adiciona o edita los tiempos por defecto de los campos
     * donde se calculara el tiempo de respuesta
     *
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-06
     */
    private function addEditPqrResponseTimesForOtherFields(): void
    {
        $sysTipoOptions = $this->getSysTipoOptions();
        $records = $this->getModel()->getCamposFormato()->getCampoOpciones(['estado'=>1]);

        foreach ($records as $CampoOpciones) {
            PqrResponseTime::executeUpdate([
                'active' => 0
            ], [
                'fk_campo_opciones' => $CampoOpciones->getPK()
            ]);

            if (!$CampoOpciones->estado) {
                continue;
            }

            foreach ($sysTipoOptions as $Option) {
                if (!$Option->estado) {
                    continue;
                }

                $PqrResponseTime = PqrResponseTime::findByAttributes([
                    'fk_campo_opciones' => $CampoOpciones->getPK(),
                    'fk_sys_tipo' => $Option->getPK(),
                ]);

                if ($PqrResponseTime) {
                    if (!$PqrResponseTime->active) {
                        $PqrResponseTime->getService()->save([
                            'active' => 1
                        ]);
                    }
                } else {
                    $PqrResponseTimeService = (new PqrResponseTime)->getService();
                    $PqrResponseTimeService->save([
                        'fk_campo_opciones' => $CampoOpciones->getPK(),
                        'fk_sys_tipo' => $Option->getPK(),
                        'number_days' => $this->getDaysForSystipo($Option->valor),
                        'active' => 1
                    ]);
                }
            }
        }
    }

    /**
     * Retorna los dias por defecto que tendra el campo
     * sys_tipo
     *
     * @param string $text
     * @return int
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-06
     */
    private function getDaysForSystipo(string $text): int
    {
        $setting = PqrFormField::getSysTipoField()->getSetting()->options;
        foreach ($setting as $option) {
            if ($option->text == $text) {
                return (int)$option->dias ?: 1;
            }
        }
        return static::DEFAULT_DAY;
    }

    /**
     * @return CampoOpciones[]
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-06
     */
    private function getSysTipoOptions(): array
    {
        return $this->getModel()::getSysTipoField()->getCamposFormato()->getCampoOpciones(['estado'=>1]);
    }
}
