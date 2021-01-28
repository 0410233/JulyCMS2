<?php

namespace July\Node;

use App\EntityField\FieldBase;
use Illuminate\Support\Facades\Log;

class NodeField extends FieldBase
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'node_fields';

    /**
     * 获取实体类
     *
     * @return string
     */
    public static function getEntityClass()
    {
        return Node::class;
    }

    // /**
    //  * 获取使用过当前字段的所有类型
    //  *
    //  * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
    //  */
    // public function nodeTypes()
    // {
    //     return $this->belongsToMany(NodeType::class, 'node_field_node_type', 'node_field_id', 'node_type_id')
    //                 ->orderBy('node_field_node_type.delta')
    //                 ->withPivot([
    //                     'delta',
    //                     // 'weight',
    //                     'label',
    //                     'description',
    //                 ]);
    // }

    // /**
    //  * 将预设类型转换为文字
    //  *
    //  * @param  string|int
    //  * @return string
    //  */
    // public function getPresetTypeAttribute($presetType)
    // {
    //     return array_flip(static::PRESET_TYPE)[$presetType] ?? 'normal';
    // }

    // /**
    //  * 限定仅查询常规字段
    //  *
    //  * @param  \Illuminate\Database\Eloquent\Builder  $query
    //  * @return \Illuminate\Database\Eloquent\Builder
    //  */
    // public function scopeNormalFields($query)
    // {
    //     return $query->where('preset_type', static::PRESET_TYPE['normal']);
    // }

    // /**
    //  * 限定仅查询预设字段
    //  *
    //  * @param  \Illuminate\Database\Eloquent\Builder  $query
    //  * @return \Illuminate\Database\Eloquent\Builder
    //  */
    // public function scopePresetFields($query)
    // {
    //     return $query->where('preset_type', static::PRESET_TYPE['preset']);
    // }

    // /**
    //  * 限定仅查询全局预设字段
    //  *
    //  * @param  \Illuminate\Database\Eloquent\Builder  $query
    //  * @return \Illuminate\Database\Eloquent\Builder
    //  */
    // public function scopeGlobalFields($query)
    // {
    //     return $query->where('preset_type', static::PRESET_TYPE['global']);
    // }

    // /**
    //  * 获取所有字段的信息（包含参数）
    //  *
    //  * @return \Illuminate\Support\Collection
    //  */
    // public static function retrieveFieldsInfo()
    // {
    //     return static::query()->with('fieldParameters')->get()
    //         ->map(function(NodeField $field) {
    //             return $field->gather();
    //         });
    // }

    // /**
    //  * 获取全局字段的信息
    //  *
    //  * @return \Illuminate\Support\Collection
    //  */
    // public static function takeGlobalFieldsInfo()
    // {
    //     return static::globalFields()->with('fieldParameters')->get()
    //         ->map(function(NodeField $field) {
    //             return $field->gather();
    //         });

    //     // return static::retrieveFieldsInfo()->groupBy('preset_type')->get(static::PRESET_TYPE['global']);
    // }

    // /**
    //  * 获取预设字段的信息
    //  *
    //  * @return \Illuminate\Support\Collection
    //  */
    // public static function takePresetFieldsInfo()
    // {
    //     return static::presetFields()->with('fieldParameters')->get()
    //         ->map(function(NodeField $field) {
    //             return $field->gather();
    //         });

    //     // return static::retrieveFieldsInfo()->groupBy('preset_type')->get(static::PRESET_TYPE['preset']);
    // }

    // /**
    //  * 获取常规字段的信息
    //  *
    //  * @return \Illuminate\Support\Collection
    //  */
    // public static function takeSelectableFieldsInfo()
    // {
    //     return static::normalFields()->with('fieldParameters')->get()
    //         ->map(function(NodeField $field) {
    //             return $field->gather();
    //         });

    //     // return static::retrieveFieldsInfo()->groupBy('preset_type')->get(static::PRESET_TYPE['normal']);
    // }

    // /**
    //  * 获取全局字段的构建材料
    //  *
    //  * @param  string|null $langcode
    //  * @return array
    //  */
    // public static function takeGlobalFieldMaterials(?string $langcode = null)
    // {
    //     $langcode = $langcode ?? langcode('content');
    //     $pocket = Pocket::make(static::class)->setKey('global_field_materials/'.$langcode);

    //     if ($materials = $pocket->get()) {
    //         $materials = $materials->value();
    //     }

    //     $lastModified = last_modified(backend_path('template/components/'));
    //     if (!$materials || $materials['created_at'] < $lastModified) {
    //         $materials = [];
    //         foreach (static::takeGlobalFieldsInfo() as $field) {
    //             $field = NodeField::make($field);
    //             $materials[$field['id']] = FieldTypeManager::findOrFail($field['field_type_id'])->bindField($field)->getMaterials();
    //         }
    //         $materials = [
    //             'created_at' => time(),
    //             'materials' => $materials,
    //         ];
    //         $pocket->put($materials);
    //     }

    //     return $materials['materials'];
    // }
}
