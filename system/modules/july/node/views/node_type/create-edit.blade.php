@extends('layout')

@section('h1')
  {{ $model['id'] ? '编辑内容类型' : '新建内容类型' }}
@endsection

@section('main_content')
  <el-form id="main_form" ref="main_form"
    :model="mold.model"
    :rules="mold.rules"
    label-position="top">
    <div id="main_form_left">
      <x-handle model="mold.model" :read-only="!!$model['id']" :unique-action="short_url('node_types.exists', '_ID_')" />
      <x-label model="mold.model" label="名称" />
      <x-description model="mold.model" />
      <div class="el-form-item el-form-item--small has-helptext jc-embeded-field">
        <div class="el-form-item__content">
          <div class="jc-embeded-field__header">
            <label class="el-form-item__label">已选字段：</label>
            <div class="jc-embeded-field__buttons">
              <button type="button" title="选择或新建字段"
                class="md-button md-icon-button md-dense md-accent md-theme-default"
                @click.stop="tabs.visible = true">
                <div class="md-ripple">
                  <div class="md-button-content"><i class="md-icon md-icon-font md-theme-default">add</i></div>
                </div>
              </button>
            </div>
          </div>
          <div class="jc-table-wrapper">
            <table class="jc-table jc-dense is-draggable with-drag-handle with-operators">
              <colgroup>
                <col width="50px">
                <col width="150px">
                <col width="150px">
                <col width="auto">
                <col width="120px">
                <col width="120px">
              </colgroup>
              <thead>
                <tr>
                  <th></th>
                  <th>ID</th>
                  <th>标签</th>
                  <th>描述</th>
                  <th>类型</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="field in mold.reservedFields" :key="field.id">
                  <td></td>
                  <td><span>@{{ field.id }}</span></td>
                  <td><span :class="{'jc-label':true,'is-required':field.is_required}">@{{ field.label }}</span></td>
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
                v-model="mold.optionalFields"
                :animation="150"
                ghost-class="jc-drag-ghost"
                handle=".jc-drag-handle"
                tag="tbody">
                <tr v-for="field in mold.optionalFields" :key="field.id">
                  <td><i class="md-icon md-icon-font md-theme-default jc-drag-handle">swap_vert</i></td>
                  <td><span>@{{ field.id }}</span></td>
                  <td><span :class="{'jc-label':true,'is-required':field.is_required}">@{{ field.label }}</span></td>
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
              <tbody
                is="draggable"
                v-model="mold.globalFields"
                :animation="150"
                ghost-class="jc-drag-ghost"
                handle=".jc-drag-handle"
                tag="tbody">
                <tr>
                  <th colspan="6" style="text-align: center">全局字段</th>
                </tr>
                <tr v-for="field in mold.globalFields" :key="field.id">
                  <td><i class="md-icon md-icon-font md-theme-default jc-drag-handle">swap_vert</i></td>
                  <td><span>@{{ field.id }}</span></td>
                  <td><span :class="{'jc-label':true,'is-required':field.is_required}">@{{ field.label }}</span></td>
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
                      <button type="button" class="md-button md-icon-button md-accent md-theme-default" title="删除" disabled>
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
        <button type="button" class="md-button md-raised md-dense md-primary md-theme-default" @click.stop="submit">
          <div class="md-button-content">保存</div>
        </button>
      </div>
    </div>
    {{-- <div id="main_form_right">
      <h2 class="jc-form-info-item">通用非必填项</h2>
    </div> --}}
  </el-form>
  <el-dialog
    id="field_selector"
    top="-5vh"
    :show-close="false"
    :visible.sync="tabs.visible"
    @open="syncToSelection" class="jc-dialog-form">
    <el-tabs v-model="tabs.current" type="card" class="jc-tabs-mini">
      <el-tab-pane label="选择字段" name="candidates" class="md-scrollbar md-theme-default">
        <el-table
          ref="candidates_table"
          :data="candidates.fields"
          style="width: 100%;"
          class="jc-table jc-dense"
          @selection-change="handleSelectionChange"
          @hook:mounted="syncToSelection">
          <el-table-column type="selection" width="50"></el-table-column>
          <el-table-column prop="id" label="ID" width="160" sortable></el-table-column>
          <el-table-column prop="label" label="标签" width="160" sortable></el-table-column>
          <el-table-column prop="description" label="描述"></el-table-column>
          <el-table-column prop="field_type_id" label="类型" width="160" sortable></el-table-column>
        </el-table>
      </el-tab-pane>
      <el-tab-pane label="新建字段" name="new_field" class="md-scrollbar md-theme-default">
        <x-field.create-edit scope="newField" model="newField.model" mode="create" />
      </el-tab-pane>
    </el-tabs>
    <span slot="footer" class="dialog-footer">
      <el-button size="small" @click.stop="tabs.visible = false">取 消</el-button>
      <el-button size="small" type="primary" @click.stop="handleSelectionConfirm">确 定</el-button>
    </span>
  </el-dialog>
  <el-dialog
    id="field_editor"
    title="编辑字段"
    top="-3vh"
    :visible.sync="field.dialogVisible" class="jc-dialog-form">
    <div class="md-scrollbar md-theme-default js-scroll-wrapper"
      style="max-height:600px; overflow:hidden auto; padding:0 20px">
      <x-field.create-edit scope="field" model="field.model" mode="edit" />
    </div>
    <span slot="footer" class="dialog-footer">
      <el-button size="small" @click.stop="field.dialogVisible = false">取 消</el-button>
      <el-button size="small" type="primary" @click.stop="handleFieldEditingConfirm">确 定</el-button>
    </span>
  </el-dialog>
@endsection

@section('script')
<script>
  const _allMoldFields = @json(array_values($context['fields']), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  let app = new Vue({
    el: '#main_content',
    data() {
      return {
        mold: {
          model: @json($model, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),
          globalFields: [],
          reservedFields: [],
          optionalFields: [],
          rules: {},
        },

        field: {
          model: @json($context['field_template'], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),
          rules: {},
          dialogVisible: false,
        },

        tabs: {
          current: 'candidates',
          visible: false,
        },

        candidates: {
          fields: @json(array_values($context['optional_fields']), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),
          selection: [],
        },

        newField: {
          model: @json($context['field_template'], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),
          rules: {},
          fieldTypeHelper: '选择字段类型',
        },
      }
    },

    created: function() {
      _allMoldFields.forEach(field => {
        if (field.is_global) {
          this.mold.globalFields.push(field);
        } else if (field.is_reserved) {
          this.mold.reservedFields.push(field);
        } else {
          this.mold.optionalFields.push(field);
        }
      });
      this.original_mold = _.cloneDeep(this.mold);
    },

    methods: {
      // 从列表移除指定字段
      removeField(field) {
        const fields = this.mold.optionalFields;
        for (let i = 0, len = fields.length; i < len; i++) {
          if (fields[i].id === field.id) {
            fields.splice(i, 1);
            return;
          }
        }
      },

      // 编辑字段
      editField(field) {
        this.$set(this.$data.field, 'model', field);
        this.field.dialogVisible = true;
      },

      handleFieldEditingConfirm() {
        let form = this.$refs.field_edit_form;
        form.validate((valid) => {
          if (valid) {
            // this.currentField = this.editingField;
            this.field.dialogVisible = false;
          }
        });
      },

      // 切换字段选择列表的选择状态
      syncToSelection() {
        const selected = this.mold.optionalFields.map(field => field.id);
        let table = this.$refs.candidates_table;
        if (table) {
          // table.clearSelection();
          this.candidates.syncing = true;
          this.candidates.fields.forEach(row => {
            if (!row.is_global && !row.is_reserved) {
              table.toggleRowSelection(row, selected.indexOf(row.id) >= 0);
            }
          });
          this.candidates.syncing = false;
        }
      },

      // 保存已选字段
      handleSelectionChange(selection) {
        if (! this.candidates.syncing) {
          this.candidates.selection = selection.slice();
        }
      },

      // 确认选择
      handleSelectionConfirm() {
        const form = this.$refs.field_create_form;

        if (this.tabs.current == 'new_field') {
          form.validate().then(() => {

            const loading = app.$loading({
              lock: true,
              text: '正在新建字段 ...',
              background: 'rgba(255, 255, 255, 0.7)',
            });

            const field = _.cloneDeep(this.newField.model);

            axios.post("{{ short_url('node_fields.store') }}", field).then((response) => {
              // console.log(response)
              this.mold.optionalFields.push(field);
              this.candidates.fields.push(_.cloneDeep(field));
              form.resetFields();
              loading.close();
              this.tabs.visible = false;
            }).catch((error) => {
              loading.close();
              console.error(error);
            })
          }).catch((error) => {
            console.error(error);
          });
        } else {
          const fields = [];
          const current = this.mold.optionalFields.map(field => field.id);
          const selected = this.candidates.selection.map(field => field.id);

          // 移除未选中的
          this.mold.optionalFields.forEach(field => {
            if (field.is_global || field.is_reserved || selected.indexOf(field.id) >= 0) {
              fields.push(field);
            }
          });

          // 添加已选中的
          this.candidates.selection.forEach(field => {
            if (current.indexOf(field.id) < 0) {
              fields.push(_.cloneDeep(field));
            }
          });

          // 更新视图
          this.$set(this.$data.mold, 'optionalFields', fields);

          form.resetFields();
          this.candidates.selection = [];
          this.tabs.visible = false;
        }
      },

      submit() {
        @if ($model['id'])
        if (_.isEqual(this.original_mold, this.mold)) {
          window.location.href = "{{ short_url('node_types.index') }}";
          return;
        }
        @endif

        this.$refs.main_form.validate().then(() => {
          const loading = app.$loading({
            lock: true,
            text: '{{ $model['id'] ? "正在保存修改 ..." : "正在新建类型 ..." }}',
            background: 'rgba(255, 255, 255, 0.7)',
          });

          @if ($model['id'])
          const action = "{{ short_url('node_types.update', $model['id']) }}";
          @else
          const action = "{{ short_url('node_types.store') }}";
          @endif

          const data = _.cloneDeep(this.mold.model);
          data.langcode = "{{ $context['content_langcode'] }}";
          data.fields = this.mold.reservedFields.concat(this.mold.optionalFields, this.mold.globalFields);
          data.fields.forEach((field, index) => { field.delta = index; })

          axios.{{ $model['id'] ? 'put' : 'post' }}(action, data)
          .then((response) => {
            window.location.href = "{{ short_url('node_types.index') }}";
          }).catch((error) => {
            loading.close();
            console.error(error);
            this.$message.error('发生错误，请查看日志');
          });
        }).catch((error) => {
          loading.close();
          // console.error(error);
        });
      },

      @stack('methods')
    }
  })
</script>
@endsection
