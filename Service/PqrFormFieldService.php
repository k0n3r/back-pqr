<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Service;

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
use App\Bundles\pqr\Repository\PqrLookupRepository;
use App\Bundles\pqr\Repository\PqrResponseTimeRepository;
use App\Bundles\pqr\Entity\PqrHtmlField as PqrHtmlFieldEntity;
use App\Exception\ValidationFailedException;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Saia\controllers\generator\component\Distribution;
use Saia\controllers\generator\component\Rad;
use Saia\models\formatos\CampoOpciones;
use Saia\models\formatos\CamposFormato;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PqrFormFieldService
{
    public const int INITIAL_ORDER = 2;
    public const int DEFAULT_DAY   = 15;

    private array $CampoOpcionesSysTipo = [];
    private PqrFormFieldEntity $entity;
    private bool $isNew;
    protected bool $skipSubscriber = false;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TranslatorInterface $translator,
        private readonly PqrLookupRepository $pqrLookupRepository,
        ?int $id = null,
    ) {
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

    public function save(array $attributes): void
    {
        $attributes = $this->clearAttributes($attributes);

        if ($this->isNew) {
            $attributes = $this->processAttributesBeforeCreating($attributes);
            $this->applyAttributes($attributes);
            $this->em->persist($this->entity);
            $this->em->flush();
            $this->isNew = false;
            if (!$this->skipSubscriber) {
                $this->eventDispatcher->dispatch(new PqrFormFieldCreatedEvent($this));
            }

            return;
        }

        $this->update($attributes);
    }

    protected function update(array $attributes): void
    {
        $attributes = $this->processAttributesBeforeUpdating($this->clearAttributes($attributes));
        if (!$attributes) {
            throw new ValidationFailedException(
                $this->translator->trans('error_actualizar'),
            );
        }
        $this->applyAttributes($attributes);
        $this->em->flush();
        if (!$this->skipSubscriber) {
            $this->eventDispatcher->dispatch(new PqrFormFieldUpdateEvent($this));
        }
    }

    public function delete(): void
    {
        $fkCampos = $this->entity->getFkCamposFormato();

        $this->em->remove($this->entity);
        $this->em->flush();
        $this->eventDispatcher->dispatch(new PqrFormFieldDeleteEvent($this));

        if ($fkCampos && !(new CamposFormato($fkCampos))->getService()->delete()) {
            throw new ValidationFailedException(
                $this->translator->trans('no_fue_posible_eliminar_campo'),
            );
        }
    }

    public function skipSubscriber(): void
    {
        $this->skipSubscriber = true;
    }

    private function applyAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            match ($key) {
                'name' => $this->entity->setName((string)$value),
                'label' => $this->entity->setLabel((string)$value),
                'required' => $this->entity->setRequired((bool)$value),
                'anonymous' => $this->entity->setAnonymous((bool)$value),
                'show_report' => $this->entity->setShowReport((bool)$value),
                'required_anonymous' => $this->entity->setRequiredAnonymous((bool)$value),
                'setting' => $this->entity->setSetting((string)$value),
                'fk_pqr_html_field' => $this->entity->setHtmlField(
                    $this->em->getRepository(PqrHtmlFieldEntity::class)->find((int)$value),
                ),
                'fk_pqr_form' => $this->entity->setPqrForm(
                    $this->em->getRepository(PqrFormEntity::class)->find((int)$value),
                ),
                'fk_campos_formato' => $this->entity->setFkCamposFormato((int)$value),
                'is_system' => $this->entity->setIsSystem((bool)$value),
                'orden' => $this->entity->setOrden((int)$value),
                'active' => $this->entity->setActive((bool)$value),
                default => null,
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

    public function processAttributesBeforeCreating(array $attributes): array
    {
        if (!isset($attributes['fk_pqr_form'])) {
            throw new ValidationFailedException(
                $this->translator->trans('falta_identificador_formulario'),
            );
        }

        $pqrForm = $this->getPqrFormRepository()->find((int)$attributes['fk_pqr_form']);
        if (!$pqrForm) {
            throw new ValidationFailedException(
                $this->translator->trans('formulario_no_encontrado'),
            );
        }

        $fieldCount = $this->getPqrFormFieldRepository()->count(['pqrForm' => $pqrForm]);

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
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function updateActive(int $status): void
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
            $this->em->flush();
        }

        $this->update($attributes);
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
        $schemaManager = $this->connection->createSchemaManager();
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
        $allowedIds = null;
        if (!$ObjSettings->allDependency) {
            $allowedIds = array_map(static fn ($row) => (int)$row->id, $ObjSettings->options);
        }

        return $this->pqrLookupRepository->findDependenciesForField(
            isset($data['id']) ? (int)$data['id'] : null,
            array_key_exists('term', $data) ? (string)$data['term'] : null,
            $allowedIds,
        );
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
        return $this->pqrLookupRepository->findLocalitiesForField(
            isset($data['id']) ? (int)$data['id'] : null,
            isset($data['term']) ? (string)$data['term'] : null,
            !$ObjSettings->allCountry ? (int)$ObjSettings->country->id : null,
        );
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
     * Inicializa/actualiza los tiempos de respuesta y el balanceador del campo.
     * Ambos comparten el mismo flujo (desactivar + reactivar/crear); solo cambia
     * el repositorio y la construcción de la entidad concreta.
     *
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-06
     */
    private function addEditPqrResponseTimesAndBalancer(): void
    {
        $responseTimeFactory = fn (int $fkCampoOpciones, $Option) => (new PqrResponseTimeEntity())
            ->setFkCampoOpciones($fkCampoOpciones)
            ->setFkSysTipo($Option->getPK())
            ->setNumberDays($this->getDaysForSystipo($Option->valor))
            ->setActive(true);

        $balancerFactory = fn (int $fkCampoOpciones, $Option) => (new PqrBalancerEntity())
            ->setFkCampoOpciones($fkCampoOpciones)
            ->setFkSysTipo($Option->getPK())
            ->setFkGrupo(-1)
            ->setActive(true);

        if ($this->entity->getName() == PqrFormFieldEntity::FIELD_NAME_SYS_TIPO) {
            $this->syncSysTipoEntries($this->getPqrResponseTimeRepository(), $responseTimeFactory);
            $this->syncSysTipoEntries($this->getPqrBalancerRepository(), $balancerFactory);
        } else {
            $this->syncFieldOptionEntries($this->getPqrResponseTimeRepository(), $responseTimeFactory);
            $this->syncFieldOptionEntries($this->getPqrBalancerRepository(), $balancerFactory);
        }
    }

    /**
     * Sincroniza las entradas asociadas al campo por defecto sys_tipo,
     * usando fkCampoOpciones = -1 como marcador.
     *
     * @param ObjectRepository $repository Repositorio de PqrResponseTime o PqrBalancer
     * @param callable         $makeEntity fn(int $fkCampoOpciones, CampoOpciones $Option): object
     */
    private function syncSysTipoEntries(ObjectRepository $repository, callable $makeEntity): void
    {
        foreach ($repository->findBy(['fkCampoOpciones' => -1]) as $entity) {
            $entity->setActive(false);
        }
        $this->em->flush();

        foreach ($this->getSysTipoOptions() as $Option) {
            if (!$Option->estado) {
                continue;
            }

            $entity = $repository->findOneBy([
                'fkCampoOpciones' => -1,
                'fkSysTipo'       => $Option->getPK(),
            ]);

            if ($entity) {
                $entity->setActive(true);
            } else {
                $this->em->persist($makeEntity(-1, $Option));
            }
            $this->em->flush();
        }
    }

    /**
     * Sincroniza las entradas de los demás campos: por cada opción activa del
     * campo, crea/activa una entrada por cada opción activa de sys_tipo.
     *
     * @param ObjectRepository $repository Repositorio de PqrResponseTime o PqrBalancer
     * @param callable         $makeEntity fn(int $fkCampoOpciones, CampoOpciones $Option): object
     */
    private function syncFieldOptionEntries(ObjectRepository $repository, callable $makeEntity): void
    {
        $records = (new CamposFormato($this->entity->getFkCamposFormato()))->getCampoOpciones(['estado' => 1]);

        foreach ($records as $CampoOpciones) {
            foreach ($repository->findBy(['fkCampoOpciones' => $CampoOpciones->getPK()]) as $entity) {
                $entity->setActive(false);
            }
            $this->em->flush();

            if (!$CampoOpciones->estado) {
                continue;
            }

            foreach ($this->getSysTipoOptions() as $Option) {
                if (!$Option->estado) {
                    continue;
                }

                $entity = $repository->findOneBy([
                    'fkCampoOpciones' => $CampoOpciones->getPK(),
                    'fkSysTipo'       => $Option->getPK(),
                ]);

                if ($entity) {
                    $entity->setActive(true);
                } else {
                    $this->em->persist($makeEntity($CampoOpciones->getPK(), $Option));
                }
                $this->em->flush();
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

    private function getPqrFormRepository(): PqrFormRepository
    {
        return $this->em->getRepository(PqrFormEntity::class);
    }

    private function getPqrFormFieldRepository(): PqrFormFieldRepository
    {
        return $this->em->getRepository(PqrFormFieldEntity::class);
    }

    private function getPqrResponseTimeRepository(): PqrResponseTimeRepository
    {
        return $this->em->getRepository(PqrResponseTimeEntity::class);
    }

    private function getPqrBalancerRepository(): PqrBalancerRepository
    {
        return $this->em->getRepository(PqrBalancerEntity::class);
    }
}
