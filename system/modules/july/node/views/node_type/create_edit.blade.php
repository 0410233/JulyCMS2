@extends('layout')

@section('h1')
  {{ $model['id'] ? '编辑内容类型' : '新建内容类型' }} <span id="content_locale">[ {{ lang($langcode)->getName() }} ]</span>
@endsection

@section('main_content')
  <el-form id="main_form" ref="main_form"
    :model="mold.model"
    :rules="mold.rules"
    label-position="top">
    <div id="main_form_left">
      <x-handle model="mold.model" :read-only="!!$model['id']" :unique-action="{{ short_url('node_types.exists', '_ID_') }}">
      <x-label model="mold.model" label="名称">
      <x-description model="mold.model">
      <div class="el-form-item el-form-item--small has-helptext jc-embeded-field">
        <div class="el-form-item__content">
          <div class="jc-embeded-field__header">
            <label class="el-form-item__label">已选字段：</label>
            <div class="jc-embeded-field__buttons">
              <button type="button" title="选择或新建字段"
                class="md-button md-icon-button md-dense md-accent md-theme-default"
                @click.stop="fieldSelectorVisible = true">
                <div class="md-ripple"><div class="md-button-content"><i class="md-icon md-icon-font md-theme-default">add</i></div></div>
              </button>
            </div>
          </div>
          <div class="jc-table-wrapper">
            <table class="jc-table jc-dense is-draggable with-drag-handle with-operators">
              <colgroup>
                <col width="50px">
                <col width="120px">
                <col width="120px">
                <col width="auto">
                <col width="100px">
                <col width="120px">
              </colgroup>
              <thead>
                <tr>
                  <th></th>
                  <th>真名</th>
                  <th>标签</th>
                  <th>描述</th>
                  <th>类型</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="field in nodeType.preset_fields" :key="field.id">
                  <td></td>
                  <td><span>@{{ field.id }}</span></td>
                  <td><span :class="{'jc-label':true,'is-required':field.required}">@{{ field.label }}</span></td>
                  <td><span>@{{ field.description }}</span></td>
                  <td><span>@{{ field.field_type_id }}</span></td>
                  <td>
                    <div class="jc-operators">
                      <button type="button" title="编辑" class="md-button md-icon-button md-primary md-theme-default"
                        @click.stop="editField(field)">
                        <svg class="md-icon jc-svg-icon"><use xlink:href="#jcon_edit_circle"></use></svg>
                        {{-- <i class="md-icon md-icon-font md-theme-default">edit</i> --}}
                      </button>
                      <button type="button" title="删除" class="md-button md-icon-button md-accent md-theme-default" disabled>
                        <i class="md-icon md-icon-font md-theme-default">remove_circle</i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
              <tbody
                is="draggable"
                v-model="nodeType.fields"
                :animation="150"
                ghost-class="jc-drag-ghost"
                handle=".jc-drag-handle"
                tag="tbody">
                <tr v-for="field in nodeType.fields" :key="field.id">
                  <td><i class="md-icon md-icon-font md-theme-default jc-drag-handle">swap_vert</i></td>
                  <td><span>@{{ field.id }}</span></td>
                  <td><span :class="{'jc-label':true,'is-required':field.required}">@{{ field.label }}</span></td>
                  <td><span>@{{ field.description }}</span></td>
                  <td><span>@{{ field.field_type_id }}</span></td>
                  <td>
                    <div class="jc-operators">
                      <button
                        type="button"
                        class="md-button md-icon-button md-primary md-theme-default"
                        title="编辑"
                        @click.stop="editField(field)">
                        <svg class="md-icon jc-svg-icon"><use xlink:href="#jcon_edit_circle"></use></svg>
                        {{-- <i class="md-icon md-icon-font md-theme-default">edit</i> --}}
                      </button>
                      <button
                        type="button"
                        class="md-button md-icon-button md-accent md-theme-default"
                        title="删除"
                        @click.stop="removeField(field)">
                        <i class="md-icon md-icon-font md-theme-default">remove_circle</i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <span class="jc-form-item-help"><i class="el-icon-info"></i> 选择、排序字段</span>
        </div>
      </div>
      <div id="main_form_bottom" class="is-button-item">
        <button type="button" class="md-button md-raised md-dense md-primary md-theme-default" @click="submitMainForm">
          <div class="md-button-content">保存</div>
        </button>
      </div>
    </div>
    <div id="main_form_right">
      <h2 class="jc-form-info-item">通用非必填项</h2>
    </div>
  </el-form>
  <el-dialog
    id="field_selector"
    top="-5vh"
    :show-close="false"
    :visible.sync="fieldSelectorVisible"
    @open="syncTypeFieldsToSelected">
    <el-tabs v-model="currentTab" type="card" class="jc-tabs-mini">
      <el-tab-pane label="选择字段" name="select" class="md-scrollbar md-theme-default">
        <el-table
          ref="fields_table"
          :data="selectableFields"
          style="width: 100%;"
          class="jc-table jc-dense"
          @selection-change="handleSelectionChange"
          @hook:mounted="syncTypeFieldsToSelected">
          <el-table-column
            type="selection"
            width="50">
          </el-table-column>
          <el-table-column
            prop="id"
            label="真名"
            width="180"
            sortable>
          </el-table-column>
          <el-table-column
            prop="label"
            label="标签"
            width="160"
            sortable>
          </el-table-column>
          <el-table-column
            prop="description"
            label="描述">
          </el-table-column>
          <el-table-column
            prop="field_type_id"
            label="类型"
            width="160"
            sortable>
          </el-table-column>
        </el-table>
      </el-tab-pane>
      <el-tab-pane label="新建字段" name="create" class="md-scrollbar md-theme-default">
        @include('backend::field.create_edit', ['fieldId' => null, 'formData' => 'nodeField'])
      </el-tab-pane>
    </el-tabs>
    <span slot="footer" class="dialog-footer">
      <el-button size="small" @click="fieldSelectorVisible = false">取 消</el-button>
      <el-button size="small" type="primary" @click="handleFieldSelectorConfirm">确 定</el-button>
    </span>
  </el-dialog>
  <el-dialog
    id="field_editor"
    title="编辑字段"
    top="-5vh"
    :visible.sync="fieldEditorVisible">
    @include('backend::field.create_edit', ['fieldId' => 1, 'formData' => 'editingField'])
    <span slot="footer" class="dialog-footer">
      <el-button size="small" @click="fieldEditorVisible = false">取 消</el-button>
      <el-button size="small" type="primary" @click="handleFieldEditorConfirm">确 定</el-button>
    </span>
  </el-dialog>
@endsection

@section('script')
<script>
  function clone(obj) {
    return JSON.parse(JSON.stringify(obj))
  }

  let mode = "{{ $id ? 'edit' : 'create' }}";

  const presetFields = [];
  const selectedFields = [];
  const selectableFields = [];

  const allFields = @json($all_fields, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

  for (const id in allFields) {
    const field = allFields[id];
    field.options = field.options.map(item => {value:item});
    if (field.options.length <= 0) {
      field.options.push({value:''});
    }

    if (field.preset_type === 1) {
      presetFields.push(field);
    } else {
      selectableFields.push(field);
    }
  }

  const currentFields = @json($currentFields, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  currentFields.forEach(function(fieldName) {
    const field = allFields[fieldName];
    if (field.preset_type === 0) {
      selectedFields.push(field);
    }
  });

  let app = new Vue({
    el: '#main_content',
    data() {
      return {
        fields: @json($all_fields, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),

        mold: {
          model: @json($model, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
          fields: @json(array_values($mold_fields), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
          rules: {},
        },

        field: {
          model: {},
          rules: {},
          dialogVisible: false,
        },

        newField: {
          model: {},
          rules: {
            field_type_id: [
              { required:true, message:'『字段类型』不能为空', trigger:'submit' },
            ],
          },
          template: {
            id: null,
            field_type_id: null,
            search_weight: 0,
            label: null,
            description: null,
            langcode: '{{ $langcode }}',
            is_required: false,
            maxlength: 0,
            helpertext: null,
            default_value: null,
            options: [{value:''}],
          },
        },

        fieldSelectorVisible: false,
        currentTab: 'select',

        selectedFields: [],
        selectableFields: selectableFields,

        fieldTypes: @json(get_field_types(), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
      }
    },

    computed: {
      fieldTypeHelp() {
        let current_type = this.nodeField.field_type_id;
        if (current_type && this.fieldTypes[current_type]) {
          return this.fieldTypes[current_type].description;
        }
        return '';
      },

      fileTypeHelp() {
        let file_bundle = this.nodeField.file_bundle;
        if (file_bundle && this.fileTypes[file_bundle]) {
          return '允许的扩展名：' + this.fileTypes[file_bundle].join(', ');
        }
        return '';
      },
    },

    created: function() {
      this.initial_data = JSON.stringify(this.nodeType);
    },

    methods: {
      removeField(field) {
        let fields = this.nodeType.fields;
        for (let i = 0, len = fields.length; i < len; i++) {
          if (fields[i].id === field.id) {
            fields.splice(i, 1);
            return;
          }
        }
      },

      editField(field) {
        // this.currentField = field;
        // let data = Object.assign({}, field);
        Vue.set(this.$data, 'editingField', field);
        this.fieldEditorVisible = true;
      },

      syncTypeFieldsToSelected() {
        this.selectedFields = this.nodeType.fields.slice();
        // console.log(this.selectedFields.slice())

        let selected = this.nodeType.fields.map(field => field.id);
        // console.log(selected.slice())

        let table = this.$refs.fields_table;
        if (table) {
          // table.clearSelection()
          this.selectableFields.forEach(row => {
            table.toggleRowSelection(row, selected.indexOf(row.id) >= 0);
          });
        }
      },

      handleSelectionChange(selected) {
        this.selectedFields = selected;
      },

      // 当按下对话框确定按钮时
      handleFieldSelectorConfirm() {
        let form = this.$refs.field_create_form;

        if (this.currentTab == 'create') {
          form.validate().then(function() {

            const loading = app.$loading({
              lock: true,
              text: '正在新建字段 ...',
              background: 'rgba(255, 255, 255, 0.7)',
            });

            let field = clone(app.nodeField);
            field.parameters.options = field.parameters.options.map(item => item.value);

            axios.post("{{ short_url('node_fields.store') }}", field).then(function(response) {
              // console.log(response)
              availableFields[field.id] = field;
              app.nodeType.fields.push(field);
              app.selectableFields.push(field);
              form.resetFields();
              loading.close();
              app.fieldSelectorVisible = false;
            }).catch(function(error) {
              loading.close();
              console.error(error);
            })
          }).catch(function(error) {
            console.error(error);
          });
        } else {
          this.$set(this.nodeType, 'fields', this.selectedFields.slice());
          form.resetFields();
          this.fieldSelectorVisible = false;
        }
      },

      handleFieldEditorConfirm() {
        let form = this.$refs.field_edit_form;
        form.validate((valid) => {
          if (valid) {
            // this.currentField = this.editingField;
            this.fieldEditorVisible = false;
          }
        });
      },

      addOption(index, field) {
        this.$data[field].parameters.options.splice(index + 1, 0, {value:''});
      },

      removeOption(index, field) {
        if (this.$data[field].parameters.options.length > 1) {
          this.$data[field].parameters.options.splice(index, 1);
        }
      },

      submitMainForm() {
        let form = this.$refs.main_form;

        const loading = app.$loading({
          lock: true,
          text: '{{ $id ? "正在保存修改 ..." : "正在新建类型 ..." }}',
          background: 'rgba(255, 255, 255, 0.7)',
        });

        form.validate().then(function() {

          let nodeType = clone(app.nodeType);
          nodeType.fields = nodeType.preset_fields.concat(nodeType.fields);
          delete nodeType.preset_fields;
          nodeType.fields = nodeType.fields.map(function(field) {
            if (field.field_type_id == 'text' && field.parameters.options) {
              field.parameters.options = field.parameters.options
                .map(option => option.value)
                .filter(option => option != null && option.length > 0);
            }
            return field;
          });

          @if ($id)
          if (app.initial_data === JSON.stringify(nodeType)) {
            window.location.href = "{{ short_url('node_types.index') }}";
            return;
          }
          @endif

          @if ($id)
          const action = "{{ short_url('node_types.update', $id) }}";
          @else
          const action = "{{ short_url('node_types.store') }}";
          @endif

          axios.{{ $id ? 'put' : 'post' }}(action, nodeType).then(function(response) {
            window.location.href = "{{ short_url('node_types.index') }}";
          }).catch(function(error) {
            loading.close();
            console.error(error);
            app.$message.error('发生错误，可查看控制台');
          });
        }).catch(function(error) {
          loading.close();
          // console.error(error);
        })
      },
    }
  })
</script>
@endsection
