<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\GraphQL\Database;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\MetaModel\PropertyDefinition;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
class PassiveManyToManyRelationshipResolver extends AbstractPassiveRelationshipResolver
{
    /**
     * @inheritdoc
     */
    public static function canResolve(Type $type): bool
    {
        if (!isset($type->config['meta']) || !$type->config['meta'] instanceof PropertyDefinition) {
            return false;
        }

        $propertyDefinition = $type->config['meta'];

        return $propertyDefinition->isManyToManyRelationProperty();
    }

    /**
     * @inheritdoc
     */
    public function resolve($source, array $arguments, array $context, ResolveInfo $info)
    {
        Assert::keyExists($context, 'cache');
        Assert::isInstanceOf($context['cache'], FrontendInterface::class);

        $bufferIdentifier = $this->getCacheIdentifier('buffer');
        $buffer = $context['cache']->get($bufferIdentifier) ?: [];

        $keysIdentifier = $this->getCacheIdentifier('keys');
        $keys = $context['cache']->get($keysIdentifier) ?? [];

        if (!$context['cache']->has($bufferIdentifier)) {
            foreach ($this->getPropertyDefinition()->getRelationTableNames() as $table) {
                $builder = $this->getBuilder($info, $table, $keys);

                $this->onResolve($source, $arguments, array_merge($context, [
                    'builder' => $builder,
                ]), $info);

                $statement = $builder->execute();

                while ($row = $statement->fetch()) {
                    foreach ($this->getBufferIndexes($row) as $index) {
                        $buffer[$index][] = $row;
                    }
                }
            }

            $context['cache']->set($bufferIdentifier, $buffer);
        }

        $value = $this->onResolved($buffer[$source['uid']], $source, $arguments, $context, $info);

        return $this->getValue($value);
    }

    protected function getTable(): string
    {
        return $this->getPropertyDefinition()->getManyToManyTableName();
    }

    protected function getForeignKeyField(): string
    {
        return 'uid_local';
    }

    /**
     * @todo Make `tablenames` depended from the meta configuration.
     * @todo Create another test case were `tablenames` is not used.
     */
    protected function getBuilder(ResolveInfo $info, string $table, array $keys): QueryBuilder
    {
        $builder = parent::getBuilder($info, $table, $keys);

        $builder->innerJoin(
            $table,
            $this->getTable(),
            $this->getTable(),
            (string) $builder->expr()->andX(
                $builder->expr()->eq(
                    $this->getTable() . '.uid_foreign',
                    $builder->quoteIdentifier($table . '.uid')
                ),
                $builder->expr()->eq(
                    $this->getTable() . '.tablenames',
                    $builder->createNamedParameter($table)
                )
            )
        );

        return $builder;
    }

    protected function getColumns(ResolveInfo $info, QueryBuilder $builder, string $table)
    {
        $columns = parent::getColumns($info, $builder, $table);

        $columns[] = $builder->quoteIdentifier($this->getTable() . '.' . $this->getForeignKeyField())
            . ' AS ' . $builder->quoteIdentifier('__' . $this->getForeignKeyField());

        return $columns;
    }

    protected function getCondition(QueryBuilder $builder, array $keys): array
    {
        $condition = parent::getCondition($builder, $keys);

        $configuration = $this->getPropertyDefinition()->getConfiguration();
        $table = $this->getTable();

        foreach ($configuration['config']['MM_match_fields'] as $field => $match) {
            $condition[] = $builder->expr()->eq(
                $table . '.' . $field,
                $builder->createNamedParameter($match)
            );
        }

        return $condition;
    }
}