import DefaultTheme from 'vitepress/theme'
import { VPButton } from 'vitepress/theme'
import ChromeExtButton from './ChromeExtButton.vue'
import './custom.css'

export default {
	...DefaultTheme,
	enhanceApp({ app }) {
		app.component('VPButton', VPButton)
		app.component('ChromeExtButton', ChromeExtButton)
	},
}
