import {axiosInstance as axios} from "boot/axios";
import {LocalStorage} from 'quasar'
import {showErrorMessage} from "src/functions/show-error-message";

const state = {
    cart_id: null,
    cart: {
        cart_items: [],
        sets: [],
        id: null,
        preorder_date: null,
        updated_at: null,
    },
    cart_items_count: 0,
    loadingCart: false,
}

const mutations = {
    setCart(state, cart) {
        state.cart = Object.assign({}, cart)
    },
    setCartId(state, cartId) {
        state.cart_id = cartId
        LocalStorage.set('cart_id', cartId)
    },
    clearCart(state) {
        state.cart = Object.assign({}, {
            cart_items: [],
            sets: [],
            preorder_date: null,
        })
        state.cart_items_count = 0
    },
    updateCartItems(state, cartItem) {
        let index = state.cart.cart_items.findIndex(item => item.id === cartItem.id);
        if (index < 0) return;
        state.cart.cart_items.splice(index, 1, cartItem);
    },
    updateCart(state, props) {
        Object.assign(state.cart, props)
    },
    setCartItemsCount(state, count) {
        state.cart_items_count = count
    },
    updateCartStandaloneItemByIndex (state, {item, index}) {
        state.cart.cart_items.splice(index, 1, item)
    },
    updateSetByIndex (state, {set, index}) {
        state.cart.sets.splice(index, 1, set)
    },
    setLoadingCart(state, payload) {
        state.loadingCart = payload
    },
    setPreorderDate(state, payload) {
        state.cart.preorder_date = payload
    },
}

const getters = {
    cart: state => state.cart,
    cart_id: state => state.cart_id,
    cartNotEmpty: state => state.cart_items_count > 0,
    cartStandaloneItems: state => state.cart.cart_items,
    sets: state => state.cart.sets,
    loadingCart: state => state.loadingCart,
    preorderDate: state => state.cart.preorder_date,
}

const actions = {
    async createCart({commit, getters}) {
        if (getters.loadingCart) return;

        commit('setLoadingCart', true)

        try {
            let res = await axios.post(`mobile/cart`)
            commit('setCart', res.data)
            commit('setCartId', res.data.id)
            commit('setCartItemsCount', res.data.cart_items_count)
        } catch (e) {
            throw e
        } finally {
            commit('setLoadingCart', false)
        }
    },
    async getCart({commit, getters}, cartId) {
        if (! cartId) {
            throw new Error('CartId is undefined.')
        }

        if (getters.loadingCart) return;

        commit('setLoadingCart', true)

        try {
            let res = await axios.get(`mobile/cart/${cartId}`)
            commit('setCart', res.data)
            commit('setCartId', res.data.id)
            commit('setCartItemsCount', res.data.cart_items_count)
        } catch (e) {
            throw e
        } finally {
            commit('setLoadingCart', false)
        }
    },
    async getCartItemsCount({commit}, cartId) {
        try {
            let res = await axios.get(`mobile/cart/${cartId}/items/count`)
            commit('setCartItemsCount', res.data.cart_items_count)
        } catch (e) {
            throw e
        }
    },
    async addCartItem({commit}, {data, cartId}) {
        try {
            let res = await axios.post(`mobile/cart/${cartId}/items`, data)
            // commit('setCart', res.data)
            commit('setCartItemsCount', res.data.cart_items_count)
        } catch (e) {
            // showErrorMessage('Не удалось добавить блюдо в корзину')
            throw e
        }
    },
    async addSetToCart({commit}, {data, cartId}) {
        try {
            let res = await axios.post(`mobile/cart/${cartId}/items/sets`, data)
            commit('setCartItemsCount', res.data.cart_items_count)
        } catch (e) {
            // console.error(e);
            showErrorMessage('Ошибка добавления комплекта в корзину')
        }
    },
    async clearCartWithBackend({commit}, cartId) {
        try {
            let res = await axios.delete(`mobile/cart/${cartId}`)
            commit('updateCart', res.data)
            commit('setCartItemsCount', res.data.cart_items_count)
        } catch (e) {
            // console.log('Не удалось изменить количество блюда в корзине')
            throw e
        }
    },
    clearCart({commit}) {
        commit('clearCart')
        commit('setCartItemsCount', 0)
    },
    setCartIdFromLocalStorage({commit}, cartId) {
        commit('setCartId', cartId)
    },
    async updateQuantity({commit}, {data, cartId}) {
        try {
            let res = await axios.put(`mobile/cart/${cartId}/items/${data.id}`, data)
            commit('updateCart', res.data)
            commit('setCartItemsCount', res.data.cart_items_count)
        } catch (e) {
            // console.log('Не удалось изменить количество блюда в корзине')
            throw e
        }
    },
    async deleteItem({commit}, {itemId, cartId}) {
        try {
            let res = await axios.delete(`mobile/cart/${cartId}/items/${itemId}`)
            commit('updateCart', res.data)
            commit('setCartItemsCount', res.data.cart_items_count)
        } catch (e) {
            // console.log('Не удалось удалить блюдо из корзины')
            throw e
        }
    },
    async checkout({commit}, orderData) {
        try {
            let res = await axios.post('mobile/orders', orderData)
            return res.data
        } catch (e) {
            if (e.response && ![400, 401, 422, 500, 503].includes(e.response.status)) {
                showErrorMessage('Ошибка создания заказа', e.response.data.message)
                // console.error(e)
            }
            throw e
        }
    },
    async getStreetSuggestions({commit}, params) {
        try {
            let res = await axios.get('api/suggestions', {params})
            return res.data
        } catch (e) {
            // console.error('Не удалось найти улицу соответствующую введенным символам.')
            throw e
        }
    },
    async getDeliveryCost({commit}, params) {
        try {
            let res = await axios.get('api/get_delivery_cost', {params})
            return res.data
        } catch (e) {
            throw e
        }
    },
    async createSet({commit}, {dishes, cartId}) {
        const data = {items: dishes}
        try {
            let res = await axios.post(`mobile/cart/${cartId}/sets`, data)
            commit('updateCart', res.data)
            commit('setCartItemsCount', res.data.cart_items_count)
        } catch (error) {
            showErrorMessage('Ошибка создания Комплекта в заказе')
            // console.error(error);
        }
    },
    updateCartStandaloneItemByIndex({commit}, data) {
        commit('updateCartStandaloneItemByIndex', data)
    },
    updateSetByIndex({commit}, data) {
        commit('updateSetByIndex', data)
    },
    setPreorderDate({commit}, date) {
        commit('setPreorderDate', date)
    },
}

export default {
    namespaced: true,
    getters,
    mutations,
    actions,
    state
}
