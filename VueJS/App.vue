<template>
  <div id="q-app">
    <router-view />
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import { showValidationErrors } from "src/functions/show-validation-errors";
import { showWarningMessage } from "src/functions/show-warning-message";
import { date } from "quasar";

export default {
  name: "App",
  computed: {
    ...mapGetters("auth", ["isLoggedIn"]),
    ...mapGetters("cart", ["cartNotEmpty", "cart", "cart_id"]),
    ...mapGetters(["hasErrors"]),
  },
  methods: {
    ...mapActions("auth", ["getUser"]),
    ...mapActions("cart", [
      "getCart",
      "createCart",
      "clearCartWithBackend",
      "clearCart",
      "setCartIdFromLocalStorage",
    ]),

    cartShouldBeCleared() {
      const compareBy = "day";
      const updated = this.cart.updated_at;
      const today = new Date();

      return this.cartNotEmpty && !date.isSameDate(updated, today, compareBy);
    },
    async makeCart() {
      const cartIdFromLocalStorage = this.$q.localStorage.getItem("cart_id");

      // console.log("cartIdFromLocalStorage:", cartIdFromLocalStorage);
      // console.log("cartId:", this.cart_id);

      if (!this.cart_id && cartIdFromLocalStorage) {
        this.setCartIdFromLocalStorage(cartIdFromLocalStorage);
      }

      this.cart_id ? await this.getCart(this.cart_id) : await this.createCart();

      if (this.cartShouldBeCleared()) {
        await this.clearCartWithBackend(this.cart_id);
        showWarningMessage("Ваша корзина была очищена.");
      }
    },
  },
  watch: {
    hasErrors(val) {
      if (!!val) {
        showValidationErrors();
      }
    },
    "$q.appVisible": {
      immediate: true,
      handler(isVisible) {
        console.log("app is visible", isVisible);

        if (isVisible) {
          this.makeCart();
        }
      },
    },
  },
  mounted() {
    if (this.isLoggedIn) {
      this.getUser();
    }
  },
};
</script>
