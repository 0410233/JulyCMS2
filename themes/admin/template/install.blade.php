<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>安装 JulyCMS</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,500,700,400italic|Material+Icons">
  <link rel="stylesheet" href="/themes/admin/vendor/normalize.css/normalize.css">
  <link rel="stylesheet" href="/themes/admin/vendor/vue-material/vue-material.css">
  <link rel="stylesheet" href="/themes/admin/vendor/vue-material/theme/default.css">
  <link rel="stylesheet" href="/themes/admin/vendor/element-ui/theme-chalk/index.css">
  <link rel="stylesheet" href="/themes/admin/css/july.css">
  <link rel="stylesheet" href="/themes/admin/css/install.css">
</head>
<body>
  <div id="install" class="md-elevation-7">
    <h1 class="jc-install-title">欢迎使用 JulyCMS</h1>
    <el-steps :active="currentStep" finish-status="success" align-center>
      <el-step title="检查安装环境" icon="el-icon-finished"></el-step>
      <el-step title="初始化配置" icon="el-icon-s-operation"></el-step>
      <el-step title="安装" icon="el-icon-s-flag" :status="lastStepStatus"></el-step>
    </el-steps>
    <div id="install_steps" v-show="isMounted" style="display: none">
      <div class="jc-install-step" v-if="currentStep===0">
        <div class="jc-install-step-content">
          <ul class="jc-env-list">
            <li v-for="(isok, requirement) in requirements" :key="requirement" :class="{'jc-env':true, 'is-ok':isok}">
              <span>@{{ requirement }}</span>
            </li>
          </ul>
        </div>
        <div class="jc-install-step-footer">
          <button type="button" class="md-button md-raised md-primary md-theme-default"
            :disabled="!environmentsOk"
            @click="stepToSettings">
            <div class="md-ripple">
              <div class="md-button-content">下一步</div>
            </div>
          </button>
        </div>
      </div>
      <div class="jc-install-step" v-if="currentStep===1">
        <div class="jc-install-step-content">
          <el-form ref="settings_form"
            :model="settings"
            :rules="rules"
            label-width="100px">
            <el-form-item label="网址" prop="app_url">
              <el-input
                size="medium"
                native-size="50"
                v-model="settings.app_url"
                placeholder="https://www.example.com">
              </el-input>
            </el-form-item>
            <el-form-item label="管理账号" prop="admin_name">
              <el-input
                size="medium"
                native-size="50"
                v-model="settings.admin_name"
                placeholder="admin"></el-input>
            </el-form-item>
            <el-form-item label="管理密码" prop="admin_password">
              <div class="jc-form-item-group">
                <el-input
                  size="medium"
                  native-size="50"
                  v-model="settings.admin_password">
                </el-input>
                <button type="button" class="md-button md-raised md-dense md-primary md-theme-default"
                  @click="randomPassword">
                  <div class="md-ripple"><div class="md-button-content">随机</div></div>
                </button>
              </div>
            </el-form-item>
            <el-form-item label="数据文件" prop="db_database" class="has-helptext">
              <div class="jc-form-item-group">
                <el-input
                  size="medium"
                  native-size="50"
                  v-model="settings.db_database"
                  placeholder="database.db3">
                </el-input>
                <button type="button" class="md-button md-raised md-dense md-primary md-theme-default"
                  @click="randomDatabase">
                  <div class="md-ripple"><div class="md-button-content">随机</div></div>
                </button>
              </div>
              <span class="jc-form-item-help"><i class="el-icon-info"></i> SQLite 数据文件</span>
            </el-form-item>
            <el-form-item label="所属企业" prop="app_owner">
              <el-input
                size="medium"
                native-size="50"
                v-model="settings.app_owner"
                placeholder="衡水网科计算机服务有限公司">
              </el-input>
            </el-form-item>
            <el-form-item label="邮箱" prop="mail_to_address" class="has-helptext">
              <el-input
                size="medium"
                native-size="50"
                v-model="settings.mail_to_address"
                placeholder="someone@example.com">
              </el-input>
              <span class="jc-form-item-help"><i class="el-icon-info"></i> 用于接收站内邮件</span>
            </el-form-item>
          </el-form>
        </div>
        <div class="jc-install-step-footer">
          <button type="button" class="md-button md-raised md-primary md-theme-default"
            @click="install">
            <div class="md-ripple">
              <div class="md-button-content">安装</div>
            </div>
          </button>
        </div>
      </div>
      <div class="jc-install-step" v-if="currentStep===2">
        <div class="jc-install-step-content">
          <div v-if="lastStepStatus==='finish'">
            <h3>账号：@{{ settings.admin_name }}</h3>
            <h3>密码：@{{ settings.admin_password }}</h3>
          </div>
        </div>
        <div class="jc-install-step-footer">
          <button type="button" class="md-button md-raised md-primary md-theme-default"
            :disabled="lastStepStatus!=='finish'" @click="login">
            <div class="md-ripple">
              <div class="md-button-content">转到登录</div>
            </div>
          </button>
        </div>
      </div>
    </div>
  </div>
  <script src="/themes/admin/js/app.js"></script>
  <script src="/themes/admin/vendor/element-ui/index.js"></script>
  <script src="/themes/admin/js/utils.js"></script>
  <script>
    const app = new Vue({
      el: '#install',
      data() {
        let validateUrl = (rule, value, callback) => {
          if (!value.trim().length || /^\s*https?:\/\/(127\.0\.0\.1\s*$|localhost\s*$|www\.)/i.test(value)) {
            callback();
          } else {
            callback(new Error('网址格式错误'));
          }
        };

        let validateDatabase = (rule, value, callback) => {
          value = value.trim();
          if (!(/\.db3$/.test(value))) {
            callback(new Error('数据文件必须以 .db3 结尾'));
          } else {
            value = value.replace(/\.db3$/, '');
            if (/[^a-z0-9_]/.test(value)) {
              callback(new Error('数据文件只能包含小写字母、数字和下划线'));
            } else {
              callback();
            }
          }
        };

        let validateOwner = (rule, value, callback) => {
          if (/[\\"']/.test(value)) {
            callback(new Error('企业名不能包含 \\ 和 "'));
          } else {
            callback();
          }
        };

        let validateAdminName = (rule, value, callback) => {
          if (/[^a-zA-Z0-9\-_ ]/.test(value)) {
            callback(new Error('管理账号不能含有特殊字符'));
          } else if (/^\s+$/.test(value)) {
            callback(new Error('管理账号不能全是空格'));
          } else {
            callback();
          }
        };

        return {
          currentStep: 0,
          requirements: @json($requirements, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
          environmentsOk: false,
          settings: {
            app_url: 'http://127.0.0.1',
            admin_name: 'admin',
            admin_password: 'admin666',
            db_database: null,
            app_owner: 'Someone',
            mail_to_address: 'someone@example.com',
          },
          rules: {
            app_url: [
              { required: true, message: '网址不能为空', trigger: 'blur' },
              { validator: validateUrl, trigger: 'change' },
              { type: 'url', message: '网址格式错误', trigger: 'change' },
            ],
            admin_name: [
              { required: true, message: '管理账号不能为空', trigger: 'blur' },
              { validator: validateAdminName, trigger: ['change', 'blur'] },
            ],
            admin_password: [
              { required: true, message: '管理密码不能为空', trigger: 'blur' },
              { min: 8, message: '管理密码至少 8 位字符', trigger: 'blur' },
            ],
            db_database: [
              { required: true, message: '数据文件不能为空', trigger: 'blur' },
              { validator: validateDatabase, trigger: 'blur' },
            ],
            app_owner: [
              { required: true, message: '企业名不能为空', trigger: 'blur' },
              { validator: validateOwner, trigger: 'blur' },
            ],
            mail_to_address: [
              { required: true, message: '邮箱不能为空', trigger: 'blur' },
              { type: 'email', message: '邮箱格式错误', trigger: ['blur', 'change'] },
            ],
          },

          isMounted: false,
          lastStepStatus: 'wait',
        };
      },

      created() {
        this.randomDatabase();

        this.environmentsOk = true;
        for (const key in this.requirements) {
          if (!this.requirements[key]) {
            this.environmentsOk = false;
            break;
          }
        }
      },

      mounted() {
        this.isMounted = true;
      },

      methods: {
        stepToSettings() {
          if (this.environmentsOk) {
            this.currentStep = 1;
          }
        },

        randomDatabase() {
          const chars = 'abcdefghijklmnopqrstuvwxyz_0123456789';
          const maxPos = chars.length;
          let db = '';
          for (let i=0; i<12; i++) {
            db += chars.charAt(Math.floor(Math.random() * maxPos));
          }
          this.settings.db_database = db + '.db3';
        },

        randomPassword() {
          const chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678~!#$%^&*_-+=?;,.';
          const maxPos = chars.length;
          let admin_password = '';
          for (let i=0; i<10; i++) {
            admin_password += chars.charAt(Math.floor(Math.random() * maxPos));
          }
          this.settings.admin_password = admin_password;
        },

        install() {
          const form = this.$refs.settings_form;
          const loading = this.$loading({
            lock: true,
            text: '正在安装 ...',
            background: 'rgba(255, 255, 255, 0.7)',
          });

          form.validate().then(() => {
            this.currentStep = 2;
            this.lastStepStatus = 'process';

            // 更新 .env 文件，创建数据库
            axios.post('/install', this.settings).then(response => {
              // console.log(response);

              // 迁移数据
              axios.post('/install/migrate', {
                admin_name: this.settings.admin_name,
                admin_password: this.settings.admin_password,
              }).then(response => {
                // console.log(response);
                loading.close();
                this.lastStepStatus = 'finish';
                this.$message.success('安装完成');
              }).catch(err => {
                loading.close();
                console.error(err);
                this.$message.error('发生错误，可查看控制台');
              });
            }).catch(err => {
              loading.close();
              console.error(err);
              this.$message.error('发生错误，可查看控制台');
            });
          }).catch(err => {
            loading.close();
          });
        },

        login() {
          location.href = '/admin/login';
        },
      },
    });
  </script>
</body>
</html>
