import DefaultTheme from 'vitepress/theme'
import { VPButton } from 'vitepress/theme'
import './custom.css'

export default {
	...DefaultTheme,
	enhanceApp({ app }) {
		app.component('VPButton', VPButton)
	},
}
