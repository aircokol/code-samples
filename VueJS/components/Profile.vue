<template>
  <q-page padding>
    <form @submit.prevent="submitForm" class="row">
      <ButtonBack></ButtonBack>

      <h5 class="col-8 offset-4 q-my-lg">Профиль</h5>

      <div class="col-12">
        <q-input
          label="Имя"
          v-model="formData.name"
          outlined
          dense
          class="q-mb-lg full-width"
        />
      </div>

      <div class="col-12">
        <q-input
          label="Email"
          v-model="formData.email"
          outlined
          dense
          class="q-mb-sm full-width"
          :rules="rules.email"
          ref="email"
        />
      </div>

      <div class="col-12">
        <q-input
          label="Телефон"
          v-model="formData.phone"
          mask="+7(###)###-##-##"
          outlined
          dense
          class="q-mb-sm full-width"
          :rules="rules.phone"
          ref="phone"
        />
      </div>

      <div class="col-12">
        <q-input
          v-show="isEditPassword"
          label="Пароль"
          v-model="formData.password"
          outlined
          dense
          class="q-mb-sm full-width"
          :rules="rules.password"
          ref="password"
          type="password"
        />
      </div>

      <div class="col-12">
        <q-btn
          label="Сохранить"
          color="primary"
          text-color="white"
          type="submit"
          class="full-width"
          :loading="loading"
          :disable="loading"
        />
      </div>

      <div class="col-12 q-mt-md">
        <q-btn
          label="Изменить пароль"
          color="primary"
          class="full-width"
          outline
          @click.prevent="isEditPassword = !isEditPassword"
          :disable="loading"
        />
      </div>
    </form>
  </q-page>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import ButtonBack from "components/ButtonBack";

export default {
  name: "PageProfile",
  components: { ButtonBack },
  data() {
    return {
      loading: false,
      isEditPassword: false,
      formData: {
        name: "",
        email: "",
        phone: "",
        password: "",
      },
      rules: {
        email: [
          (val) => !!val || "Обязательное поле",
          (val) =>
            this.isValidEmailAddress(val) || "Введите правильную Эл. почту",
        ],
        phone: [
          (val) => !!val || "Обязательное поле",
          (val) => val.length === 16 || "Телефон указан неверно",
        ],
        password: [
          (val) => !!val || "Обязательное поле",
          (val) => val.length >= 6 || "Пароль должен быть не менее 6 символов",
        ],
      },
    };
  },
  computed: {
    ...mapGetters("auth", ["user", "isValidEmailAddress"]),
  },
  methods: {
    ...mapActions("auth", ["updateUser", "getUser"]),

    async submitForm() {
      this.$refs.email.validate();
      this.$refs.phone.validate();
      if (this.isEditPassword && !!this.$refs.password) {
        this.$refs.password.validate();
      }

      if (
        this.$refs.email.hasError ||
        this.$refs.phone.hasError ||
        this.$refs.password.hasError
      ) {
        return;
      }

      this.loading = true;
      const data = Object.assign({}, this.formData);
      if (!this.isEditPassword) {
        delete data.password;
      }
      try {
        await this.updateUser(data);
      } finally {
        this.loading = false;
        this.isEditPassword = false;
      }
    },
    fillDefaultFormData() {
      this.formData.name = this.user.name;
      this.formData.email = this.user.email;
      this.formData.phone = this.user.phone;
    },
  },
  async mounted() {
    if (!this.user || !this.user.phone) {
      await this.getUser();
    }

    this.fillDefaultFormData();
  },
};
</script>
