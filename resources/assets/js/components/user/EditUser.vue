<template>
  <form class="form-horizontal">
    <div class="text-right">
      <button class="btn btn-success" type="button" v-on:click="updateUser(user.id)" v-if="user">ACTUALIZAR</button>
    </div>
    <div class="form-group">
      <label class="col-lg-2 control-label">Funcionario</label>
      <div class="col-lg-10">
        <input type="text" disabled class="form-control" :value="`${userSelected.last_name} ${userSelected.first_name}`" v-if="user">
        <select class="form-control m-b" v-model="uid" v-else>
          <option v-for="u in users" :key="u.uid" :value="u.uid">{{ u.sn }} {{ u.givenName }}</option>
        </select>
      </div>
      <div class="form-group">
        <label class="col-lg-2 control-label">Usuario</label>
        <div class="col-lg-10">
          <input type="text" disabled class="form-control" :value="uid">
        </div>
      </div>
      <div class="form-group">
        <label class="col-lg-2 control-label">Cargo</label>
        <div class="col-lg-10">
          <input type="text" disabled class="form-control" :value="userSelected.position">
        </div>
      </div>
      <div class="form-group">
        <label class="col-lg-2 control-label">Celular</label>
        <div class="col-lg-10">
          <input type="text" disabled class="form-control" :value="userSelected.phone">
        </div>
      </div>
      <div class="form-group">
        <label class="col-lg-2 control-label">Departamento</label>
        <div class="col-lg-10">
          <select class="form-control m-b" v-model="userSelected.city_id">
            <option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
          </select>
        </div>
      </div>
      <div v-if="user">
        <div v-if="user.username == 'admin'">
          <div class="form-group">
            <label class="col-lg-2 control-label">Contraseña anterior</label>
            <div class="col-lg-10">
              <input type="password" class="form-control" v-model="userSelected.old_password">
            </div>
          </div>
          <div class="form-group">
            <label class="col-lg-2 control-label">Contraseña nueva</label>
            <div class="col-lg-10">
              <input type="password" class="form-control" v-model="userSelected.password">
            </div>
          </div>
          <div class="form-group">
            <label class="col-lg-2 control-label">Repita la contraseña</label>
            <div class="col-lg-10">
              <input type="password" class="form-control" v-model="userSelected.repeat_password">
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="panel panel-primary">
      <div class="panel-body">
        <div class="nav-tabs-custom">
          <ul class="nav nav-tabs">
            <li v-for="module in modules" :key="module.id"><a :href="`#tab_${module.id}`" data-toggle="tab" :title="module.name">&nbsp;<i :class="module.icon" aria-hidden="true"></i>&nbsp;</a></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane" v-for="module in modules" :key="module.id" :id="`tab_${module.id}`">
              <h3 class="box-title">{{ module.name }}</h3>
              <div v-for="rol in roles.filter(o => o.module_id == module.id)" :key="rol.id">
                <label>
                  <input type="checkbox" :checked="userSelected.rol.includes(rol.id)" v-on:click="changeRole(rol.id)"/>
                  <span>{{ rol.name }}</span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="text-right">
      <button class="btn btn-primary" type="button" v-on:click="cancel">CANCELAR</button>
      <button class="btn btn-danger" type="button" v-on:click="saveUser">GUARDAR</button>
    </div>
  </form>
</template>

<script>
export default {
  props:[
    'users',
    'user',
    'modules',
    'cities',
    'roles',
    'token'
  ],
  data() {
    return {
      userSelected: {
        username: null,
        first_name: null,
        last_name: null,
        phone: null,
        city_id: null,
        position: null,
        city: null,
        rol: []
      },
      uid: null
    }
  },
  mounted() {
    if (this.user) {
      this.uid = this.user.username
    }
  },
  watch: {
    uid: function(val) {
      if (this.user) {
        this.userSelected = this.user
        this.userSelected.user_id = this.user.id
      } else {
        let user = this.users.find(o => o.uid == val)
        this.userSelected = {
          username: val,
          first_name: user.givenName,
          last_name: user.sn,
          phone: null,
          city_id: null,
          position: user.title,
          rol: []
        }
        this.getUser(user.employeeNumber)
      }
    }
  },
  methods: {
    async updateUser(id) {
      try {
        let res = await axios.get(`/user/${id}`)
        this.userSelected.first_name = res.data.givenName
        this.userSelected.last_name = res.data.sn
        this.userSelected.position = res.data.title
        this.getUser(res.data.employeeNumber)
        flash('Datos actualizados')
      } catch (e) {
        console.log(e)
        flash('Usuario inexistente', 'error')
      }
    },
    cancel() {
      window.history.back()
    },
    changeRole(id) {
      if (this.userSelected.rol.includes(id)) {
        this.userSelected.rol = this.userSelected.rol.filter(o => o != id)
      } else {
        this.userSelected.rol.push(id)
      }
    },
    async saveUser() {
      try {
        this.userSelected._token = this.token
        if (this.userSelected.username == 'admin') {
          if (this.userSelected.password == this.userSelected.repeat_password && this.userSelected.old_password != null && this.userSelected.password != null) {
            let res = await axios.post(`/registrar`, this.userSelected)
            window.location = '/user'
            flash('Usuario registrado');
          } else {
            flash('Las contraseñas no coinciden', 'error');
          }
        } else {
          let res = await axios.post(`/registrar`, this.userSelected)
          window.location = '/user'
          flash('Usuario registrado');
        }
      } catch (e) {
        console.log(e)
        flash('Error al guardar usuario', 'error');
      }
    },
    async getUser(id) {
      try {
        let res = await axios.get(`${process.env.MIX_RRHH_URL}/employee/${id}`)
        this.userSelected.phone = res.data.phone_number
        let city = this.cities.find(o => o.name == res.data.location)
        if (city) {
          this.userSelected.city_id = city.id
          this.userSelected.city = city.name
        }
      } catch (e) {
        console.log(e)
      }
    }
  }
}
</script>