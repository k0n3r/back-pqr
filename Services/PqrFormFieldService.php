<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\Entity\PqrBalancer as PqrBalancerEntity;
use App\Bundles\pqr\Entity\PqrFormField as PqrFormFieldEntity;
use App\Bundles\pqr\Entity\PqrForm as PqrFormEntity;
use App\Bundles\pqr\Entity\PqrResponseTime as PqrResponseTimeEntity;
use App\Bundles\pqr\Event\PqrFormFieldCreatedEvent;
use App\Bundles\pqr\Event\PqrFormFieldDeleteEvent;
use App\Bundles\pqr\Event\PqrFormFieldUpdateEvent;
use App\Bundles\pqr\Repository\PqrBalancerRepository;
use App\Bundles\pqr\Repository\PqrFormFieldRepository;
use App\Bundles\pqr\Repository\PqrFormRepository;
use App\Bundles\pqr\Repository\PqrResponseTimeRepository;
use App\Bundles\pqr\Entity\PqrHtmlField as PqrHtmlFieldEntity;
use App\services\Service;
use App\services\ServiceEventDispatcher;
use Saia\models\Funcionario;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Saia\controllers\generator\component\Distribution;
use Saia\controllers\generator\component\Rad;
use Saia\models\formatos\CampoOpciones;
use Saia\models\formatos\CamposFormato;

class PqrFormFieldService extends Service
{
    public const int INITIAL_ORDER = 2;
    public const int DEFAULT_DAY   = 15;

    private array $CampoOpcionesSysTipo = [];
    private PqrFormFieldEntity $entity;
    private bool $isNew;
    protected bool $skipSubscriber = false;

    public function __construct(EntityManagerInterface $em, ?int $id = null, ?Funcionario $Funcionario = null)
    {
        parent::__construct($Funcionario);
        if ($id) {
            $this->entity = $em->getRepository(PqrFormFieldEntity::class)->find($id);
            $this->isNew  = false;
        } else {
            $this->entity = new PqrFormFieldEntity();
            $this->isNew  = true;
        }
    }

    public function getModel(): PqrFormFieldEntity
    {
        return $this->entity;
    }

    public function save(array $attributes): bool
    {
        $attributes = $this->clearAttributes($attributes);

        if ($this->isNew) {
            $attributes = $this->processAttributesBeforeCreating($attributes);
            if (!$attributes) {
                return false;
            }
            $this->applyAttributes($attributes);
            $this->getEntityManager()->persist($this->entity);
            $this->getEntityManager()->flush();
            $this->isNew = false;
            if (!$this->skipSubscriber) {
                $this->getIServiceEventDispatcher()->dispatch(ServiceEventDispatcher::EVENT_CREATED);
            }
            return true;
        }

        return $this->update($attributes);
    }

    protected function update(array $attributes): bool
    {
        $attributes = $this->processAttributesBeforeUpdating($this->clearAttributes($attributes));
        if (!$attributes) {
            $this->getErrorManager()->setMessage('error_actualizar');
            return false;
        }
        $this->applyAttributes($attributes);
        $this->getEntityManager()->flush();
        if (!$this->skipSubscriber) {
            $this->getIServiceEventDispatcher()->dispatch(ServiceEventDispatcher::EVENT_UPDATED);
        }
        return true;
    }

    public function delete(): bool
    {
        $fkCampos = $this->entity->getFkCamposFormato();

        $this->getEntityManager()->remove($this->entity);
        $this->getEntityManager()->flush();
        $this->getIServiceEventDispatcher()->dispatch(ServiceEventDispatcher::EVENT_DELETED);

        if ($fkCampos) {
            if (!(new CamposFormato($fkCampos))->getService()->delete()) {
                $this->getErrorManager()->setMessage('No fue posible eliminar el campo');
                return false;
            }
        }

        return true;
    }

    public function skipSubscriber(): void
    {
        $this->skipSubscriber = true;
    }

    private function applyAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            match ($key) {
                'name'               => $this->entity->setName((string)$value),
                'label'              => $this->entity->setLabel((string)$value),
                'required'           => $this->entity->setRequired((bool)$value),
                'anonymous'          => $this->entity->setAnonymous((bool)$value),
                'show_report'        => $this->entity->setShowReport((bool)$value),
                'required_anonymous' => $this->entity->setRequiredAnonymous((bool)$value),
                'setting'            => $this->entity->setSetting((string)$value),
                'fk_pqr_html_field'  => $this->entity->setHtmlField(
                    $this->getEntityManager()->getRepository(PqrHtmlFieldEntity::class)->find((int)$value)
                ),
                'fk_pqr_form'        => $this->entity->setPqrForm(
                    $this->getEntityManager()->getRepository(PqrFormEntity::class)->find((int)$value)
                ),
                'fk_campos_formato'  => $this->entity->setFkCamposFormato((int)$value),
                'is_system'          => $this->entity->setIsSystem((bool)$value),
                'orden'              => $this->entity->setOrden((int)$value),
                'active'             => $this->entity->setActive((bool)$value),
                default              => null,
            };
        }
    }

    private function clearAttributes(array $attributes): array
    {
        array_walk_recursive($attributes, static function (&$item): void {
            if (!is_null($item) && !is_numeric($item)) {
                $item = trim((string)$item);
            }
        });
        return $attributes;
    }

    /**
     * @inheritDoc
     */
    public function getEvents(): array
    {
        return [
            ServiceEventDispatcher::EVENT_CREATED => PqrFormFieldCreatedEvent::class,
            ServiceEventDispatcher::EVENT_UPDATED => PqrFormFieldUpdateEvent::class,
            ServiceEventDispatcher::EVENT_DELETED => PqrFormFieldDeleteEvent::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public function processAttributesBeforeCreating(array $attributes): false|array
    {
        if (!isset($attributes['fk_pqr_form'])) {
            $this->getErrorManager()->setMessage("Falta el identificador del formulario");

            return false;
        }

        $pqrForm = $this->getPqrFormRepository()->find((int)$attributes['fk_pqr_form']);
        if (!$pqrForm) {
            $this->getErrorManager()->setMessage("Formulario no encontrado");

            return false;
        }

        $fieldCount = $this->getPqrFormFieldRepository()->count(['fkPqrForm' => $pqrForm->getId()]);

        $defaultFields = [
            'name'              => $this->generateName(trim(strtolower($attributes['label']))),
            'required'          => 0,
            'anonymous'         => 0,
            'fk_pqr_form'       => $pqrForm->getId(),
            'fk_campos_formato' => 0,
            'is_system'         => 0,
            'orden'             => $fieldCount + self::INITIAL_ORDER,
            'active'            => 1,
        ];
        $attributes    = array_merge($defaultFields, $attributes);

        if (isset($attributes['setting'])) {
            $attributes['setting'] = json_encode($attributes['setting']);
        }

        return $attributes;
    }

    /**
     * @inheritDoc
     */
    public function processAttributesBeforeUpdating(array $attributes): false|array
    {
        if (isset($attributes['setting'])) {
            $attributes['setting'] = json_encode($attributes['setting']);
        }

        return $attributes;
    }

    /**
     * Elimina un campo del formulario
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */

    /**
     * Actualiza el estado(active) del campo
     *
     * @param int $status
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function updateActive(int $status): bool
    {
        $attributes = [
            'active'             => $status,
            'required'           => 0,
            'required_anonymous' => 0,
        ];

        if (
            $this->entity->getName() != 'sys_subtipo'
            && $this->entity->getName() != PqrFormFieldEntity::FIELD_NAME_SYS_DEPENDENCIA
        ) {
            $attributes['show_report'] = 0;
        }

        if (!$status && $this->isFieldTime()) {
            $sysTipoField = $this->getPqrFormFieldRepository()->findSysTipo();
            $pqrForm      = $this->entity->getPqrForm();
            $pqrForm->setFkFieldTime($sysTipoField ? $sysTipoField->getFkCamposFormato() : 0);
            $this->getEntityManager()->flush();
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
        return $this->entity->getFkCamposFormato() == $this->entity->getPqrForm()->getFkFieldTime();
    }

    /**
     * genera un nombre unico para el campo del formulario
     *
     * @param string $label
     * @param int    $pref
     *
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

        if ($this->getPqrFormFieldRepository()->findByName($name) !== null) {
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
     *
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
            'idft',
            'radicacion',
            'canal_recepcion',
            Rad::DISTRIBUCION,
            Distribution::DESTINO_INTERNO,
            Distribution::SELECT_MENSAJERIA,
            Rad::COLILLA,
            Rad::DIGITALIZACION,
            Rad::DESCRIPCION,
        ];

        return in_array($label, $reservedWords);
    }

    /**
     * Valida si la columna existe en la DB
     *
     * @param string $name
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    private function columnExistsDB(string $name): bool
    {
        $connection    = $this->serviceLocator->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $table         = $schemaManager->introspectTable('ft_pqr');

        return $table->hasColumn($name);
    }


    /**
     * Retorna listado de valores para los campos autocompletar
     *
     * @param array $data
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getListDataForAutocomplete(array $data = []): array
    {
        $list = [];

        switch ($this->entity->getHtmlField()->getType()) {
            case PqrHtmlFieldEntity::TYPE_DEPENDENCIA:
                $list = $this->getDependencys($this->entity->getSettingDecoded(), $data);
                break;

            case PqrHtmlFieldEntity::TYPE_LOCALIDAD:
                $list = $this->getListLocalidad($this->entity->getSettingDecoded(), $data);
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
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getDependencys(object $ObjSettings, array $data = []): array
    {
        $Qb = $this->serviceLocator
            ->getConnection()
            ->createQueryBuilder()
            ->select('iddependencia as id,nombre as text')
            ->from('dependencia');

        if ($data['id']) {
            $Qb
                ->where('iddependencia=:iddependencia')
                ->setParameter('iddependencia', $data['id'], ParameterType::INTEGER);

            return $Qb->executeQuery()->fetchAllAssociative();
        }

        $Qb
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if (isset($data['term'])) {
            $Qb->andWhere('nombre like :nombre');

            if ($data['term']) {
                $Qb->setParameter('nombre', '%'.$data['term'].'%');
            } else {
                $Qb->setParameter('nombre', $data['term']);
            }
        }

        if (!$ObjSettings->allDependency) {
            $records = $ObjSettings->options;
            $ids     = [];
            foreach ($records as $row) {
                $ids[] = $row->id;
            }
            $Qb
                ->andWhere('iddependencia in (:ids)')
                ->setParameter('ids', $ids, ArrayParameterType::INTEGER);
        }

        return $Qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Obtiene las localidades basados en la configuracion
     * del campo
     *
     * @param object $ObjSettings
     * @param array  $data
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getListLocalidad(object $ObjSettings, array $data = []): array
    {
        $Qb = $this->serviceLocator
            ->getConnection()
            ->createQueryBuilder()
            ->select(
                "CONCAT(a.nombre,
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
        ) AS text",
                "a.idmunicipio as id",
            )
            ->from('municipio', 'a')
            ->join('a', 'departamento', 'b', 'a.departamento_iddepartamento = b.iddepartamento')
            ->join('b', 'pais', 'c', 'b.pais_idpais = c.idpais');

        if ($data['id']) {
            $Qb
                ->andWhere('idmunicipio=:idmunicipio')
                ->setParameter('idmunicipio', $data['id'], ParameterType::INTEGER);

            return $Qb->executeQuery()->fetchAllAssociative();
        }

        $Qb
            ->where("CONCAT(a.nombre,CONCAT(' ',b.nombre)) like :query")
            ->andWhere('a.estado = 1 AND b.estado = 1 AND c.estado = 1')
            ->setParameter('query', "%{$data['term']}%")
            ->orderBy('a.nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if (!$ObjSettings->allCountry) {
            $Qb
                ->andWhere('c.idpais=:idpais')
                ->setParameter('idpais', $ObjSettings->country->id);
        }

        return $Qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Crea o edita las opciones de tipo select, radio y checkbox
     *
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-05
     */
    public function addEditformatOptions(): void
    {
        $CampoFormato = new CamposFormato($this->entity->getFkCamposFormato());
        $llave        = 0;
        foreach ($CampoFormato->getCampoOpciones() as $CampoOpciones) {
            if ((int)$CampoOpciones->llave > $llave) {
                $llave = (int)$CampoOpciones->llave;
            }
            if ((int)$CampoOpciones->estado) {
                $CampoOpciones->setAttributes(['estado' => 0]);
                $CampoOpciones->save();
            }
        }

        $data = $values = [];
        foreach ($this->entity->getSettingDecoded()->options as $option) {
            if ($CampoOpciones = CampoOpciones::findByAttributes([
                'valor'             => $option->text,
                'fk_campos_formato' => $CampoFormato->getPK(),
            ])) {
                $CampoOpcionesService = $CampoOpciones->getService();
                $CampoOpcionesService->save(['estado' => 1]);
                $id = $CampoOpcionesService->getModel()->llave;
            } else {
                $id    = $llave + 1;
                $llave = $id;

                $CampoOpcionesService = (new CampoOpciones())->getService();
                $CampoOpcionesService->save([
                    'llave'             => $id,
                    'valor'             => $option->text,
                    'fk_campos_formato' => $CampoFormato->getPK(),
                    'estado'            => 1,
                ]);
            }

            $data[]   = ['llave' => $id, 'item' => $option->text];
            $values[] = "$id,$option->text";
        }

        $CampoFormato->setAttributes([
            'opciones' => json_encode($data),
            'valor'    => implode(';', $values),
        ]);
        $CampoFormato->save();

        if ($this->entity->getHtmlField()->isValidFieldForResponseDaysOrBalance()) {
            $this->addEditPqrResponseTimesAndBalancer();
        }
    }

    /**
     * Inicializa los tiempos de respuesta
     *
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-06
     */
    private function addEditPqrResponseTimesAndBalancer(): void
    {
        if ($this->entity->getName() == PqrFormFieldEntity::FIELD_NAME_SYS_TIPO) {
            $this->addEditPqrResponseTimesForSysTipo();
            $this->addEditPqrBalancerForSysTipo();
        } else {
            $this->addEditPqrResponseTimesForOtherFields();
            $this->addEditPqrBalancerForOtherFields();
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
        $em             = $this->getEntityManager();

        $this->serviceLocator
            ->getConnection()->createQueryBuilder()
            ->update('pqr_response_times')
            ->set('active', ':active')
            ->where('fk_campo_opciones = :fkCampoOpciones')
            ->setParameter('active', 0, ParameterType::INTEGER)
            ->setParameter('fkCampoOpciones', -1, ParameterType::INTEGER)
            ->executeStatement();
        $em->clear();

        foreach ($sysTipoOptions as $Option) {
            if (!$Option->estado) {
                continue;
            }

            $pqrRt = $this->getPqrResponseTimeRepository()->findOneBy([
                'fkCampoOpciones' => -1,
                'fkSysTipo'       => $Option->getPK(),
            ]);

            if ($pqrRt) {
                $pqrRt->setActive(true);
            } else {
                $pqrRt = (new PqrResponseTimeEntity())
                    ->setFkCampoOpciones(-1)
                    ->setFkSysTipo($Option->getPK())
                    ->setNumberDays($this->getDaysForSystipo($Option->valor))
                    ->setActive(true);
                $em->persist($pqrRt);
            }
            $em->flush();
        }
    }

    private function addEditPqrBalancerForSysTipo(): void
    {
        $sysTipoOptions = $this->getSysTipoOptions();
        $em             = $this->getEntityManager();

        $this->serviceLocator
            ->getConnection()->createQueryBuilder()
            ->update('pqr_balancer')
            ->set('active', ':active')
            ->where('fk_campo_opciones = :fkCampoOpciones')
            ->setParameter('active', 0, ParameterType::INTEGER)
            ->setParameter('fkCampoOpciones', -1, ParameterType::INTEGER)
            ->executeStatement();
        $em->clear();

        foreach ($sysTipoOptions as $Option) {
            if (!$Option->estado) {
                continue;
            }

            $pqrBalancer = $this->getPqrBalancerRepository()->findOneBy([
                'fkCampoOpciones' => -1,
                'fkSysTipo'       => $Option->getPK(),
            ]);

            if ($pqrBalancer) {
                $pqrBalancer->setActive(true);
            } else {
                $pqrBalancer = (new PqrBalancerEntity())
                    ->setFkCampoOpciones(-1)
                    ->setFkSysTipo($Option->getPK())
                    ->setFkGrupo(-1)
                    ->setActive(true);
                $em->persist($pqrBalancer);
            }
            $em->flush();
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
        $records        = (new CamposFormato($this->entity->getFkCamposFormato()))->getCampoOpciones(['estado' => 1]);
        $em             = $this->getEntityManager();

        foreach ($records as $CampoOpciones) {
            $this->serviceLocator
                ->getConnection()->createQueryBuilder()
                ->update('pqr_response_times')
                ->set('active', ':active')
                ->where('fk_campo_opciones = :fkCampoOpciones')
                ->setParameter('active', 0, ParameterType::INTEGER)
                ->setParameter('fkCampoOpciones', $CampoOpciones->getPK(), ParameterType::INTEGER)
                ->executeStatement();
            $em->clear();

            if (!$CampoOpciones->estado) {
                continue;
            }

            foreach ($sysTipoOptions as $Option) {
                if (!$Option->estado) {
                    continue;
                }

                $pqrRt = $this->getPqrResponseTimeRepository()->findOneBy([
                    'fkCampoOpciones' => $CampoOpciones->getPK(),
                    'fkSysTipo'       => $Option->getPK(),
                ]);

                if ($pqrRt) {
                    $pqrRt->setActive(true);
                } else {
                    $pqrRt = (new PqrResponseTimeEntity())
                        ->setFkCampoOpciones($CampoOpciones->getPK())
                        ->setFkSysTipo($Option->getPK())
                        ->setNumberDays($this->getDaysForSystipo($Option->valor))
                        ->setActive(true);
                    $em->persist($pqrRt);
                }
                $em->flush();
            }
        }
    }

    private function addEditPqrBalancerForOtherFields(): void
    {
        $sysTipoOptions = $this->getSysTipoOptions();
        $records        = (new CamposFormato($this->entity->getFkCamposFormato()))->getCampoOpciones(['estado' => 1]);
        $em             = $this->getEntityManager();

        foreach ($records as $CampoOpciones) {
            $this->serviceLocator
                ->getConnection()->createQueryBuilder()
                ->update('pqr_balancer')
                ->set('active', ':active')
                ->where('fk_campo_opciones = :fkCampoOpciones')
                ->setParameter('active', 0, ParameterType::INTEGER)
                ->setParameter('fkCampoOpciones', $CampoOpciones->getPK(), ParameterType::INTEGER)
                ->executeStatement();
            $em->clear();

            if (!$CampoOpciones->estado) {
                continue;
            }

            foreach ($sysTipoOptions as $Option) {
                if (!$Option->estado) {
                    continue;
                }

                $pqrBalancer = $this->getPqrBalancerRepository()->findOneBy([
                    'fkCampoOpciones' => $CampoOpciones->getPK(),
                    'fkSysTipo'       => $Option->getPK(),
                ]);

                if ($pqrBalancer) {
                    $pqrBalancer->setActive(true);
                } else {
                    $pqrBalancer = (new PqrBalancerEntity())
                        ->setFkCampoOpciones($CampoOpciones->getPK())
                        ->setFkSysTipo($Option->getPK())
                        ->setFkGrupo(-1)
                        ->setActive(true);
                    $em->persist($pqrBalancer);
                }
                $em->flush();
            }
        }
    }

    /**
     * Retorna los dias por defecto que tendra el campo
     * sys_tipo
     *
     * @param string $text
     *
     * @return int
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-06
     */
    private function getDaysForSystipo(string $text): int
    {
        $sysTipoField = $this->getPqrFormFieldRepository()->findSysTipo();
        if (!$sysTipoField) {
            return static::DEFAULT_DAY;
        }

        $setting = $sysTipoField->getSettingDecoded()?->options ?? [];
        foreach ($setting as $option) {
            if ($option->text == $text) {
                return (int)$option->dias ?: 1;
            }
        }

        return static::DEFAULT_DAY;
    }

    /**
     * @return CampoOpciones[]
     */
    private function getSysTipoOptions(): array
    {
        if (!$this->CampoOpcionesSysTipo) {
            $sysTipoField = $this->getPqrFormFieldRepository()->findSysTipo();
            if ($sysTipoField) {
                $camposFormato              = new CamposFormato($sysTipoField->getFkCamposFormato());
                $this->CampoOpcionesSysTipo = $camposFormato->getCampoOpciones(['estado' => 1]);
            }
        }

        return $this->CampoOpcionesSysTipo;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->serviceLocator->getEntityManager();
    }

    private function getPqrFormRepository(): PqrFormRepository
    {
        return $this->getEntityManager()->getRepository(PqrFormEntity::class);
    }

    private function getPqrFormFieldRepository(): PqrFormFieldRepository
    {
        return $this->getEntityManager()->getRepository(PqrFormFieldEntity::class);
    }

    private function getPqrResponseTimeRepository(): PqrResponseTimeRepository
    {
        return $this->getEntityManager()->getRepository(PqrResponseTimeEntity::class);
    }

    private function getPqrBalancerRepository(): PqrBalancerRepository
    {
        return $this->getEntityManager()->getRepository(PqrBalancerEntity::class);
    }
}
