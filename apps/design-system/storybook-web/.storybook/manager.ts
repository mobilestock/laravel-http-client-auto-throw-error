import { addons } from '@storybook/manager-api'
import { create } from '@storybook/theming'

import favicon from '../public/open-book-icon.svg'
import { theme } from '../src/packages/base/utils/theme'

const link = document.createElement('link')
link.setAttribute('rel', 'shortcut icon')
link.setAttribute('href', favicon)
document.head.appendChild(link)

const myTheme = create({
  base: 'dark',
  brandTitle: 'Documentação Mobile',
  brandUrl: 'https://mobilestock.com.br',
  brandTarget: '_self',
  appBg: theme.colors.background.dark,
  appContentBg: theme.colors.background.dark,
  appPreviewBg: theme.colors.background.dark,
  appBorderColor: theme.colors.background.light,
  appBorderRadius: 5,
  textColor: theme.colors.text.secondary,
  textInverseColor: theme.colors.text.primary,
  barTextColor: theme.colors.text.secondary,
  barSelectedColor: theme.colors.text.secondary,
  barBg: theme.colors.background.dark,
  inputBg: theme.colors.background.dark,
  inputBorder: theme.colors.decorator.outline,
  inputTextColor: theme.colors.alert.tip,
  inputBorderRadius: 5
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
