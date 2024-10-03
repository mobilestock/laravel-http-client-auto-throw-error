import { addons } from '@storybook/manager-api'
import { create } from '@storybook/theming'

const myTheme = create({
  base: 'dark',
  brandTitle: 'Mobilestock',
  brandUrl: 'https://mobilestock.com.br',
  brandTarget: '_blank',
  appBg: '#000',
  appContentBg: '#000'
})

addons.setConfig({
  theme: myTheme,
  enableShortcuts: false,
  showToolbar: false,
  sidebar: {
    showRoots: true,
    collapsedRoots: ['misc']
  }
})
