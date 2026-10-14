import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import { createEstateRouter } from './router'
import { createEstateI18n } from './i18n'
import { useLocaleStore } from './stores/locale'
import { wireApi } from './api/wire'
import './assets/main.css'

const app = createApp(App)
const pinia = createPinia()
const router = createEstateRouter()
app.use(pinia)
app.use(router)
app.use(createEstateI18n(useLocaleStore().locale === 'ar' ? 'ar' : 'en'))
wireApi(router)
useLocaleStore().applyToDocument()
app.mount('#app')
