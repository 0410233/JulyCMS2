<?php

namespace App\EntityField;

use App\EntityField\FieldTypes\FieldTypeManager;
use App\Entity\EntityBase;
use App\Entity\Exceptions\InvalidEntityException;
use App\Models\ModelBase;
use App\Services\Translation\TranslatableInterface;
use App\Services\Translation\TranslatableTrait;
use App\Utils\Types;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class FieldBase extends ModelBase implements TranslatableInterface
{
    use TranslatableTrait;

    /**
     * 主键
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 主键类型
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * 模型主键是否递增
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * 可批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'field_type_id',
        'is_reserved',
        'is_global',
        'group_title',
        'search_weight',
        'maxlength',
        'label',
        'description',
        'is_required',
        'helpertext',
        'default_value',
        'options',
        'rules',
        'placeholder',
        'langcode',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_reserved' => 'boolean',
        'is_global' => 'boolean',
        'search_weight' => 'int',
        'maxlength' => 'int',
        'is_required' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'delta',
    ];

    /**
     * 字段所属实体
     *
     * @var \App\Entity\EntityBase
     */
    protected $entity;

    /**
     * 获取模型模板数据
     *
     * @return array
     */
    public static function template()
    {
        return [
            'id' => null,
            'field_type_id' => null,
            'label' => null,
            'description' => null,
            'is_reserved' => false,
            'is_global' => false,
            'group_title' => null,
            'search_weight' => 0,
            'maxlength' => 0,
            'is_required' => false,
            'helpertext' => null,
            'default_value' => null,
            'options' => null,
            'rules' => null,
            'placeholder' => null,
            'langcode' => langcode('content'),
        ];
    }

    /**
     * 获取实体类
     *
     * @return string
     */
    abstract public static function getEntityClass();

    /**
     * 获取实体字段类
     *
     * @return string
     */
    public static function getMoldClass()
    {
        return static::getEntityClass()::getMoldClass();
    }

    /**
     * 获取类型字段关联类
     *
     * @return string
     */
    public static function getPivotClass()
    {
        return static::getEntityClass()::getPivotClass();
    }

    /**
     * 获取字段绑定实体的实体名
     *
     * @return string
     */
    public static function getBoundEntityName()
    {
        return static::getEntityClass()::getEntityName();
    }

    /**
     * 获取绑定的实体
     *
     * @return \App\Entity\EntityBase|null
     */
    public function getBoundEntity()
    {
        return $this->entity;
    }

    /**
     * 绑定到实体
     *
     * @param  \App\Entity\EntityBase $entity
     * @return $this
     *
     * @throws \App\Entity\Exceptions\InvalidEntityException
     */
    public function bindEntity(EntityBase $entity)
    {
        $class = $this->getEntityClass();
        if ($entity instanceof $class) {
            $this->entity = $entity;
            return $this;
        } else {
            throw new InvalidEntityException('字段无法绑定到实体：'.get_class($entity));
        }
    }

    /**
     * 限定可检索字段
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchable($query)
    {
        return $query->where('search_weight', '>', 0);
    }

    /**
     * 限定全局字段
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsGlobal($query)
    {
        return $query->where('is_global', true);
    }

    /**
     * 限定预设字段
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsReserved($query)
    {
        return $query->where('is_reserved', true);
    }

    /**
     * 限定预设字段
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsPreseted($query)
    {
        return $query->where('is_global', true)->orWhere('is_reserved', true);
    }

    /**
     * 限定候选字段
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsOptional($query)
    {
        return $query->where(['is_global' => false, 'is_reserved' => false]);
    }

    /**
     * 将字段按预设类型分组
     *
     * @return \Illuminate\Support\Collection
     */
    public static function groupbyPresetType()
    {
        return static::all()->groupBy(function(FieldBase $field) {
            if ($field->is_global) {
                return 'global';
            } elseif ($field->is_reserved) {
                return 'reserved';
            } else {
                return 'optional';
            }
        });
    }

    /**
     * 将字段分为预设和非预设两组
     *
     * @return \Illuminate\Support\Collection
     */
    public static function bisect()
    {
        return static::all()->groupBy(function(FieldBase $field) {
            if ($field->is_global || $field->is_reserved) {
                return 'preseted';
            } else {
                return 'optional';
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getLangcode()
    {
        if ($this->entity) {
            return $this->entity->getLangcode();
        }
        return $this->contentLangcode ?? $this->getOriginalLangcode();
    }

    /**
     * label 属性的 Get Mutator
     *
     * @param  string|null $label
     * @return string
     */
    public function getLabelAttribute($label)
    {
        if ($this->pivot) {
            $label = $this->pivot->label;
        }
        return trim($label);
    }

    /**
     * description 属性的 Get Mutator
     *
     * @param  string|null $description
     * @return string
     */
    public function getDescriptionAttribute($description)
    {
        if ($this->pivot) {
            $description = $this->pivot->description;
        }
        return trim($description);
    }

    /**
     * maxlength 属性的 Get Mutator
     *
     * @param  string|null $maxlength
     * @return string
     */
    public function getMaxlengthAttribute($maxlength)
    {
        if ($this->pivot) {
            $maxlength = $this->pivot->maxlength;
        }
        return (int) $maxlength;
    }

    /**
     * is_required 属性的 Get Mutator
     *
     * @param  bool|int $required
     * @return bool
     */
    public function getIsRequiredAttribute($required)
    {
        if ($this->pivot) {
            $required = $this->pivot->is_required;
        }
        return (bool) $required;
    }

    /**
     * helpertext 属性的 Get Mutator
     *
     * @param  string|null $helpertext
     * @return string
     */
    public function getHelpertextAttribute($helpertext)
    {
        if ($this->pivot) {
            $helpertext = $this->pivot->helpertext;
        }
        return trim($helpertext);
    }

    /**
     * default_value 属性的 Get Mutator
     *
     * @param  string|null $defaultValue
     * @return string
     */
    public function getDefaultValueAttribute($defaultValue)
    {
        if ($this->pivot) {
            $defaultValue = $this->pivot->default_value;
        }
        return Types::cast($defaultValue, $this->getFieldType()->getCaster());
    }

    /**
     * options 属性的 Get Mutator
     *
     * @param  string|null $options
     * @return string
     */
    public function getOptionsAttribute($options)
    {
        if ($this->pivot) {
            $options = $this->pivot->options;
        }
        return trim($options);
    }

    /**
     * rules 属性的 Get Mutator
     *
     * @return int
     */
    public function getRulesAttribute($rules)
    {
        if ($this->pivot) {
            return $this->pivot->rules;
        }
        return $rules;
    }

    /**
     * placeholder 属性的 Get Mutator
     *
     * @param  string|null $placeholder
     * @return string
     */
    public function getPlaceholderAttribute($placeholder)
    {
        if ($this->pivot) {
            $placeholder = $this->pivot->placeholder;
        }
        return trim($placeholder);
    }

    /**
     * delta 属性的 Get Mutator
     *
     * @return int
     */
    public function getDeltaAttribute()
    {
        if ($this->pivot) {
            return $this->pivot->delta;
        }
        return 0;
    }

    /**
     * 获取字段类型对象
     *
     * @return \App\EntityField\FieldTypes\FieldTypeBase
     */
    public function getFieldType()
    {
        // 尝试从缓存获取数据
        if ($result = $this->cachePipe(__FUNCTION__)) {
            return $result->value();
        }
        return FieldTypeManager::findOrFail($this->attributes['field_type_id'])->bindField($this);
    }

    /**
     * 生成表单控件
     *
     * @param  mixed $value 字段值
     * @return string
     */
    public function render($value = null)
    {
        return $this->getFieldType()->render($value);
    }

    /**
     * 获取字段值模型
     *
     * @return \App\EntityField\FieldValueBase
     */
    public function getValueModel()
    {
        if ($model = $this->cachePipe(__FUNCTION__)) {
            return $model->value();
        }
        return $this->getFieldType()->getValueModel();
    }

    /**
     * 获取字段默认值
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->getDefaultValueAttribute($this->attributes['default_value'] ?? null);
    }

    /**
     * 获取所有字段值
     *
     * @return array
     */
    public function getValues()
    {
        if ($value = $this->cachePipe(__FUNCTION__)) {
            return $value->value();
        }
        return $this->getValueModel()->values();
    }

    /**
     * 获取所有字段值，不区分语言
     *
     * @return array[]
     */
    public function getValueRecords()
    {
        if ($value = $this->cachePipe(__FUNCTION__)) {
            return $value->value();
        }
        return $this->getValueModel()->records();
    }

    /**
     * 获取字段值
     *
     * @return mixed
     */
    public function getValue()
    {
        if ($value = $this->cachePipe(__FUNCTION__)) {
            return $value->value();
        }
        return $this->getValueModel()->getValue($this->entity);
    }

    /**
     * 设置字段值
     *
     * @param  mixed $value
     * @return void
     */
    public function setValue($value)
    {
        return $this->getValueModel()->setValue($value, $this->entity);
    }

    /**
     * 删除字段值
     *
     * @return void
     */
    public function deleteValue()
    {
        return $this->getValueModel()->deleteValue($this->entity);
    }

    /**
     * 搜索字段值
     *
     * @param  string $needle 搜索该字符串
     * @return array
     */
    public function searchValue(string $needle)
    {
        return $this->getValueModel()->searchValue($needle);
    }

    /**
     * 判断是否使用动态表存储
     *
     * @return string
     */
    public function useDynamicValueTable()
    {
        return $this->getValueModel()->isDynamic();
    }

    /**
     * 获取存储字段值的动态数据库表的表名
     *
     * @return string
     */
    public function getDynamicValueTable()
    {
        return $this->getBoundEntityName().'__'.$this->getKey();
    }

    /**
     * 获取存储字段值的数据库表的表名
     *
     * @return string
     */
    public function getValueTable()
    {
        return $this->getValueModel()->getTable();
    }

    /**
     * 获取数据表列参数
     *
     * @return array
     */
    public function getValueColumn()
    {
        return $this->getFieldType()->getColumn();
    }

    /**
     * 建立字段值存储表
     *
     * @return void
     */
    public function tableUp()
    {
        // 检查是否使用动态表保存字段值，如果不是则不创建
        if (! $this->useDynamicValueTable()) {
            return;
        }

        // 获取独立表表名，并判断是否已存在
        $tableName = $this->getDynamicValueTable();
        if (Schema::hasTable($tableName)) {
            return;
        }

        // 获取用于创建数据表列的参数
        $column = $this->getValueColumn();

        // 创建数据表
        Schema::create($tableName, function (Blueprint $table) use ($column) {
            $table->id();
            $table->unsignedBigInteger('entity_id');

            $table->addColumn($column['type'], $column['name'], $column['parameters'] ?? []);

            $table->string('langcode', 12);
            $table->timestamps();

            $table->unique(['entity_id', 'langcode']);
        });
    }

    /**
     * 删除字段值存储表
     *
     * @return void
     */
    public function tableDown()
    {
        // 检查是否使用动态表保存字段值，如果不是则不创建
        if (! $this->useDynamicValueTable()) {
            return;
        }
        Schema::dropIfExists($this->getDynamicValueTable());
    }

    /**
     * {@inheritdoc}
     */
    public static function boot()
    {
        parent::boot();

        static::created(function(FieldBase $field) {
            $field->tableUp();
        });

        static::deleting(function(FieldBase $field) {
            $field->tableDown();
        });
    }

    /**
     * 获取字段参数
     *
     * @return array
     */
    public function getParameters()
    {
        // 尝试从缓存获取数据
        if ($result = $this->cachePipe(__FUNCTION__)) {
            return $result->value();
        }

        $parameters = null;

        // 获取翻译过的模型字段参数
        if ($this->entity && $this->entity->getMold()->isTranslated()) {
            $parameters = FieldParameters::ofField($this)->where('mold_id', $this->entity->mold_id)->first();
        }

        // 获取翻译过的字段参数
        elseif (!$this->entity && $this->isTranslated()) {
            $parameters = FieldParameters::ofField($this)->where('mold_id', null)->first();
        }

        if ($parameters) {
            return [
                'default_value' => $parameters->default_value,
                'options' => $parameters->options,
            ];
        }

        return [];
    }
}