<?php

namespace App\Bundles\pqr\Services;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Saia\core\DatabaseConnection;

use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\models\PqrHtmlField;

class PqrFormFieldService
{

    /**
     * Bandera que indica el numero minimo donde empezara el orden de los campos
     */
    const INITIAL_ORDER = 2;

    private PqrFormField $PqrFormField;
    private string $errorMessage;

    public function __construct(PqrFormField $PqrFormField)
    {
        $this->PqrFormField = $PqrFormField;
    }

    /**
     * Retorna el mensaje de error
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2021
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * Obtiene la instancia de PqrFormField actualizada
     *
     * @return PqrFormField
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getModel(): PqrFormField
    {
        return $this->PqrFormField;
    }

    /**
     * Crea el registro en la DB
     *
     * @param array $data
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2021
     */
    public function create(array $data): bool
    {
        if (!isset($data['fk_pqr_form'])) {
            $this->errorMessage = "Falta el identificador del formulario";
            return false;
        }

        $PqrForm = new PqrForm($data['fk_pqr_form']);

        $defaultFields = [
            'name' => $this->generateName($data['label']),
            'required' => 0,
            'show_anonymous' => 0,
            'fk_pqr_form' => $PqrForm->getPK(),
            'fk_campos_formato' => 0,
            'is_system' => 0,
            'orden' => ($PqrForm->countFields()) + self::INITIAL_ORDER,
            'active' => 1
        ];
        $attributes = array_merge($defaultFields, $data);

        return $this->update($attributes);
    }

    /**
     * Actualiza un registro
     * 
     * @param array $data
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2021
     */
    public function update(array $data): bool
    {

        if (isset($data['setting'])) {
            $data['setting'] = json_encode($data['setting']);
        }

        $this->PqrFormField->setAttributes($data);

        return $this->PqrFormField->save();
    }

    /**
     * Elimina un campo del formulario
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function delete(): bool
    {
        if ($this->PqrFormField->delete()) {
            if ($this->PqrFormField->fk_campos_formato) {
                if (!$this->PqrFormField->CamposFormato->delete()) {
                    $this->errorMessage = "No fue posible eliminar el campo";
                    return false;
                }
            }
            return true;
        } else {
            $this->errorMessage = "No fue posible eliminar";
            return false;
        }
    }

    /**
     * Actualiza el estado(active) del campo
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updateActive(int $status): bool
    {
        $attributes = [
            'active' => $status,
            'required' => 0,
            'required_anonymous' => 0
        ];

        if (
            $this->PqrFormField->name != 'sys_subtipo'
            && $this->PqrFormField->name != 'sys_dependencia'
        ) {
            $attributes['show_report'] = 0;
        }

        return $this->update($attributes);
    }


    /**
     * genera un nombre unico para el campo del formulario
     *
     * @param string $label
     * @param integer $pref
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function generateName(string $label, int $pref = 0): string
    {
        $label = $this->excludeReservedWords($label);
        $cadena = trim(preg_replace('/[^a-z]/', '_', strtolower($label)));
        $cadena = implode('_', array_filter(explode('_', $cadena)));
        $cadena = trim(substr($cadena, 0, 20), '_');

        $name = $pref ? "{$cadena}_{$pref}" : $cadena;

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
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2021
     */
    private function excludeReservedWords(string $label): string
    {
        $reservedWords = [
            'select', 'from', 'where', 'uniq', 'numero', 'fecha'
        ];

        return in_array($label, $reservedWords) ? $label . "_" : $label;
    }

    /**
     * Valida si la columna existe en la DB
     *
     * @param string $name
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2021
     */
    private function columnExistsDB(string $name): bool
    {
        $schema = DatabaseConnection::getInstance()->getSchemaManager();
        $Table = $schema->listTableDetails('ft_pqr');

        return $Table->hasColumn($name);
    }

    /**
     * Retorna listado de valores
     *
     * @param array $data
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getListField(array $data = []): array
    {
        $list = [];

        switch ($this->PqrFormField->PqrHtmlField->type) {
            case PqrHtmlField::TYPE_DEPENDENCIA:
                $list = $this->getDependencys($this->PqrFormField->getSetting(), $data);
                break;

            case PqrHtmlField::TYPE_LOCALIDAD:
                $list = $this->getListLocalidad($this->PqrFormField->getSetting(), $data);
                break;
        }

        return $list;
    }


    /**
     * Obtiene listado de localidades basados en la configuracion
     * del campo
     *
     * @param object $ObjSettings
     * @param array $data
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getDependencys(object $ObjSettings, array $data = []): array
    {
        $Qb = DatabaseConnection::getDefaultConnection()
            ->createQueryBuilder()
            ->select('iddependencia as id,nombre as text')
            ->from('dependencia');

        if ($data['id']) {
            $Qb->where('iddependencia=:iddependencia')
                ->setParameter(':iddependencia', $data['id'], Type::getType('integer'));

            return $Qb->execute()->fetchAll();
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
            foreach ($records as $row) {
                $ids[] = $row->id;
            }
            $Qb->andWhere('iddependencia in (:ids)')
                ->setParameter(':ids', $ids, Connection::PARAM_INT_ARRAY);
        }

        return $Qb->execute()->fetchAll();
    }

    /**
     * Obtiene las localidades basados en la configuracion
     * del campo
     *
     * @param object $ObjSettings
     * @param array $data
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getListLocalidad(object $ObjSettings, array $data = []): array
    {
        $Qb = DatabaseConnection::getDefaultConnection()
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

            return $Qb->execute()->fetchAll();
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

        return $Qb->execute()->fetchAll();
    }
}
