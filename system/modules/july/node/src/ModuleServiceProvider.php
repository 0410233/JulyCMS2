<?php

namespace July\Node;

use App\Providers\ModuleServiceProviderBase;

class ModuleServiceProvider extends ModuleServiceProviderBase
{
    /**
     * 获取实体类
     *
     * @return array
     */
    protected function discoverEntities()
    {
        return [
            \July\Node\Node::class,
        ];
    }

    protected function discoverActions()
    {
        return [
            \July\Node\Actions\RebuildIndex::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getModuleRoot()
    {
        return dirname(__DIR__);
    }

    /**
     * {@inheritdoc}
     */
    protected function getModuleName()
    {
        return 'node';
    }
}
