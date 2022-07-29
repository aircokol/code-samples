import { Notify } from 'quasar'
import store from '../store'

export function showValidationErrors() {
  let errorsString = store.getters.errors.reduce(error => `- ${error}<br>`)

  Notify.create({
    icon: null,
    color: 'red-2',
    textColor: 'red-8',
    message: `<div>${ errorsString }</div>`,
    html: true,
    position: 'center',
    caption: 'Устраните вышеуказанные ошибки',
    timeout: 60000,
    actions: [
      { icon: 'close', color: 'red-8', handler() { store.commit('clearErrors') } }
    ]
  })
}
