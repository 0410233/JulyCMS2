<?php

namespace July\Core\EntityField;

use App\Utils\Pocket;
use App\Utils\PocketUserInterface;
use Illuminate\Support\Str;
use July\Core\EntityField\Exceptions\FieldTypeNotFoundException;
use July\Core\EntityField\EntityFieldBase;
use July\Core\EntityField\FieldTypeDefinitions\DefinitionBase;
use Symfony\Component\Finder\Finder;

class FieldType implements PocketUserInterface
{
    /**
     * 可用的字段类型定义
     *
     * @var array
     */
    protected static $definitions = [];

    /**
     * 绑定的字段类型定义
     *
     * @var \July\Core\EntityField\FieldTypeDefinitions\DefinitionBase|null
     */
    protected $definition;

    /**
     * @param  \July\Core\EntityField\FieldTypeDefinitions\DefinitionBase|null $definition
     */
    public function __construct(DefinitionBase $definition = null)
    {
        $this->definition = $definition;
    }

    /**
     * 获取字段类型定义类实例
     *
     * @param  \July\Core\EntityField\EntityFieldBase|string $id 类型定义 id
     * @return self
     */
    public static function find($id)
    {
        if ($definition = static::getDefinition($id)) {
            return new static($definition);
        }

        return null;
    }

    /**
     * 获取字段类型定义类实例，失败则抛出错误
     *
     * @param  \July\Core\EntityField\EntityFieldBase|string $id 类型定义 id
     * @return static
     *
     * @throws \July\Core\EntityField\Exceptions\FieldTypeNotFoundException
     */
    public static function findOrFail($id)
    {
        if ($fieldType = static::find($id)) {
            return $fieldType;
        }

        throw new FieldTypeNotFoundException();
    }

    /**
     * 获取字段类型列表
     *
     * @return array
     */
    public static function all()
    {
        if (empty(static::$definitions)) {
            static::discoverDefinitions();
        }

        $definitions = [];
        foreach (static::$definitions as $class => $id) {
            $definitions[$id] = [
                'class' => $class,
                'id' => $id,
                'label' => $class::get('label'),
                'description' => $class::get('description'),
            ];
        }

        return $definitions;
    }

    /**
     * 根据字段类型别名获取字段类型定义类
     *
     * @param  \July\Core\EntityField\EntityFieldBase|string $id 字段类型 id 或字段类型定义对象
     * @return \July\Core\EntityField\FieldTypeDefinitions\DefinitionBase|null
     */
    public static function getDefinition($id)
    {
        if (empty(static::$definitions)) {
            static::discoverDefinitions();
        }

        $field = null;
        if ($id instanceof EntityFieldBase) {
            $field = $id;
            $id = $field->getAttributeValue('field_type_id');
        }

        if (is_string($id)) {
            if ($definition = array_search($id, static::$definitions, true)) {
                return new $definition($field);
            }
        }

        return null;
    }

    /**
     * 查找定义类
     *
     * @return void
     */
    public static function discoverDefinitions()
    {
        if (config('app.env') !== 'production') {
            static::$definitions = static::discoverDefinitionsFromFiles();
            return;
        }

        $pocket = new Pocket(static::class);
        $key = 'definitions';
        if ($definitions = $pocket->get($key)) {
            static::$definitions = $definitions->value();
        } else {
            $pocket->put($key, static::$definitions = static::discoverDefinitionsFromFiles());
        }
    }

    /**
     * 查找定义类
     *
     * @return array
     */
    protected static function discoverDefinitionsFromFiles()
    {
        $definitions = [];

        $path = 'july/Core/EntityField/FieldTypeDefinitions';
        $prefix = 'July\\Core\\EntityField\\FieldTypeDefinitions\\';

        $finder = Finder::create()
            ->files()
            ->name('*.php')
            ->in(base_path($path));

        foreach ($finder as $file) {
            $class = $prefix.$file->getBasename('.php');
            if (static::isDefinitionClass($class)) {
                $definitions[$class] = $class::get('id');
            }
        }

        return $definitions;
    }

    /**
     * 判断一个类是否类型定义类
     *
     * @param string $class
     * @return bool
     */
    public static function isDefinitionClass(string $class)
    {
        if (! class_exists($class)) {
            return false;
        }

        $ref = new \ReflectionClass($class);
        return $ref->isInstantiable() && $ref->isSubclassOf(DefinitionBase::class);
    }

    /**
     * 获取字段默认值
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        if ($this->definition) {
            return $this->definition->default_value;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPocketId()
    {
        $key = static::class;
        if ($this->definition) {
            $key .= '/'.$this->definition->id;
        }

        return $key;
    }

    public function __call($name, array $arguments)
    {
        return $this->definition->$name(...$arguments);
    }
}