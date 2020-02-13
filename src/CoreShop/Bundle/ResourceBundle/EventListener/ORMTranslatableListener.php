<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2020 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Bundle\ResourceBundle\EventListener;

use CoreShop\Component\Resource\Metadata\MetadataInterface;
use CoreShop\Component\Resource\Metadata\RegistryInterface;
use CoreShop\Component\Resource\Model\TranslatableInterface;
use CoreShop\Component\Resource\Model\TranslationInterface;
use CoreShop\Component\Resource\Translation\TranslatableEntityLocaleAssignerInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ORMTranslatableListener implements EventSubscriber
{
    private $resourceMetadataRegistry;
    private $translatableEntityLocaleAssigner;

    public function __construct(
        RegistryInterface $resourceMetadataRegistry,
        ContainerInterface $container
    ) {
        $this->resourceMetadataRegistry = $resourceMetadataRegistry;
        $this->translatableEntityLocaleAssigner = $container->get('coreshop.translatable_entity_locale_assigner');
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::loadClassMetadata,
            Events::postLoad,
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $classMetadata = $eventArgs->getClassMetadata();
        $reflection = $classMetadata->reflClass;

        if (!$reflection || $reflection->isAbstract()) {
            return;
        }

        if ($reflection->implementsInterface(TranslatableInterface::class)) {
            $this->mapTranslatable($classMetadata);
        }

        if ($reflection->implementsInterface(TranslationInterface::class)) {
            $this->mapTranslation($classMetadata);
        }
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();

        if (!$entity instanceof TranslatableInterface) {
            return;
        }

        $this->translatableEntityLocaleAssigner->assignLocale($entity);
    }

    private function mapTranslatable(ClassMetadata $metadata): void
    {
        $className = $metadata->name;

        try {
            $resourceMetadata = $this->resourceMetadataRegistry->getByClass($className);
        } catch (\InvalidArgumentException $exception) {
            return;
        }

        if (!$resourceMetadata->hasParameter('translation')) {
            return;
        }

        /** @var MetadataInterface $translationResourceMetadata */
        $translationResourceMetadata = $this->resourceMetadataRegistry->get($resourceMetadata->getAlias() . '_translation');

        if (!$metadata->hasAssociation('translations')) {
            $metadata->mapOneToMany([
                'fieldName' => 'translations',
                'targetEntity' => $translationResourceMetadata->getClass('model'),
                'mappedBy' => 'translatable',
                'fetch' => ClassMetadataInfo::FETCH_EXTRA_LAZY,
                'indexBy' => 'locale',
                'cascade' => ['persist', 'merge', 'remove'],
                'orphanRemoval' => true,
            ]);
        }
    }

    private function mapTranslation(ClassMetadata $metadata): void
    {
        $className = $metadata->name;

        try {
            $resourceMetadata = $this->resourceMetadataRegistry->getByClass($className);
        } catch (\InvalidArgumentException $exception) {
            return;
        }

        /** @var MetadataInterface $translatableResourceMetadata */
        $translatableResourceMetadata = $this->resourceMetadataRegistry->get(str_replace('_translation', '', $resourceMetadata->getAlias()));

        if (!$metadata->hasAssociation('translatable')) {
            $metadata->mapManyToOne([
                'fieldName' => 'translatable',
                'targetEntity' => $translatableResourceMetadata->getClass('model'),
                'inversedBy' => 'translations',
                'joinColumns' => [[
                    'name' => 'translatable_id',
                    'referencedColumnName' => 'id',
                    'onDelete' => 'CASCADE',
                    'nullable' => false,
                ]],
            ]);
        }

        if (!$metadata->hasField('locale')) {
            $metadata->mapField([
                'fieldName' => 'locale',
                'type' => 'string',
                'nullable' => false,
                'length' => 5,
            ]);
        }

        // Map unique index.
        $columns = [
            $metadata->getSingleAssociationJoinColumnName('translatable'),
            'locale',
        ];

        if (!$this->hasUniqueConstraint($metadata, $columns)) {
            $constraints = isset($metadata->table['uniqueConstraints']) ? $metadata->table['uniqueConstraints'] : [];

            $constraints[$metadata->getTableName() . '_uniq_trans'] = [
                'columns' => $columns,
            ];

            $metadata->setPrimaryTable([
                'uniqueConstraints' => $constraints,
            ]);
        }
    }

    private function hasUniqueConstraint(ClassMetadata $metadata, array $columns): bool
    {
        if (!isset($metadata->table['uniqueConstraints'])) {
            return false;
        }

        foreach ($metadata->table['uniqueConstraints'] as $constraint) {
            if (!array_diff($constraint['columns'], $columns)) {
                return true;
            }
        }

        return false;
    }
}
