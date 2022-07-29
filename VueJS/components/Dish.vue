<template>
  <q-page class="bg-grey-1">
    <q-card flat>
      <ButtonBack></ButtonBack>

      <q-img :src="dish.relative_image_path_card" class="relative-position" />

      <q-card-section>
        <transition
          appear
          enter-active-class="animated fadeIn"
          leave-active-class="animated fadeOut"
        >
          <q-btn
            v-if="!addingStatus"
            fab
            color="primary"
            icon="shopping_cart"
            class="absolute"
            style="top: 0; right: 12px; transform: translateY(-50%)"
            @click="addToCart"
            :loading="addingToCart"
            :disable="addingToCart"
          />
          <q-btn
            v-if="!!addingStatus && addingStatus === 'success'"
            fab
            color="green-6"
            icon="check"
            class="absolute"
            style="top: 0; right: 12px; transform: translateY(-50%)"
          />
          <q-btn
            v-if="!!addingStatus && addingStatus === 'fail'"
            fab
            color="orange-6"
            icon="close"
            class="absolute"
            style="top: 0; right: 12px; transform: translateY(-50%)"
          />
        </transition>

        <div class="row no-wrap items-center">
          <div class="col text-h6 ellipsis">
            {{ dish.title }}
          </div>
        </div>
      </q-card-section>

      <q-card-section class="q-pt-none row items-center">
        <div class="text-body1">{{ dish.price }} руб.</div>
        <q-space />

        <q-btn
          flat
          round
          color="grey"
          icon="remove_circle"
          @click="decrement"
        />
        <div class="text-body1">{{ quantity }}</div>
        <q-btn
          flat
          round
          color="primary"
          icon="add_circle"
          @click="increment"
        />
      </q-card-section>

      <q-card-section class="bg-grey-1">
        <div class="text-subtitle2">
          {{ category.title === "Напитки" ? "Описание" : "Состав" }}
        </div>
        <div class="text-caption text-grey-7">
          {{ dish.description }}
        </div>
      </q-card-section>
      <q-card-section class="bg-grey-1 q-pt-none">
        <div class="text-subtitle2">Вес</div>
        <div class="text-caption text-grey-7">{{ dish.weight }}</div>
      </q-card-section>
      <q-card-section
        v-if="category.title !== 'Напитки'"
        class="bg-grey-1 q-pt-none"
      >
        <div class="text-subtitle2">Время приготовления</div>
        <div class="text-caption text-grey-7">{{ dish.cooking_time }} мин.</div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import { showErrorMessage } from "src/functions/show-error-message";
import ButtonBack from "components/ButtonBack";

export default {
  name: "PageDish",
  components: { ButtonBack },
  data() {
    return {
      addingToCart: false,
      quantity: 1,
      timer: null,
      addingStatus: null,
    };
  },
  watch: {
    addingStatus: "handleAddingStatusChange",
  },
  computed: {
    ...mapGetters("dishes", ["dish"]),
    ...mapGetters("categories", ["category"]),
    ...mapGetters("cart", ["cart_id"]),
  },
  methods: {
    ...mapActions("dishes", ["getDish"]),
    ...mapActions("cart", ["addCartItem"]),
    async addToCart() {
      const data = {
        quantity: this.quantity,
        dish_id: this.dish.id,
        category_id: this.category.id,
      };

      this.addingToCart = true;
      try {
        await this.addCartItem({ data, cartId: this.cart_id });
        this.addingStatus = "success";
      } catch (e) {
        this.addingStatus = "fail";
      } finally {
        this.addingToCart = false;
      }
    },
    increment() {
      if (this.quantity === 50) {
        showErrorMessage(
          "Нельзя выбрать более 50 позиций одного блюда или напитка."
        );
        return;
      }
      this.quantity++;
    },
    decrement() {
      if (this.quantity === 1) return;
      this.quantity--;
    },
    handleAddingStatusChange(val, oldVal) {
      if (!val) return;

      this.timer = setTimeout(() => (this.addingStatus = null), 2000);
    },
  },
  beforeDestroy() {
    clearTimeout(this.timer);
  },
};
</script>
