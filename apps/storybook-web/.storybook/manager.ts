import { addons } from '@storybook/manager-api'
import { create } from '@storybook/theming'

// @ts-ignore
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
  appBg: theme.colors.container.default,
  appContentBg: theme.colors.container.default,
  appPreviewBg: theme.colors.container.pure,
  appBorderColor: theme.colors.container.pure,
  appBorderRadius: 5,
  textColor: theme.colors.text.default,
  textInverseColor: theme.colors.text.regular,
  barTextColor: theme.colors.text.default,
  barSelectedColor: theme.colors.text.default,
  barBg: theme.colors.container.default,
  inputBg: theme.colors.container.default,
  inputBorder: theme.colors.container.outline.default,
  inputTextColor: theme.colors.alert.tip,
  inputBorderRadius: 5
})

addons.setConfig({
  theme: myTheme,
  enableShortcuts: false,
  showToolbar: false,
  panelPosition: 'bottom',
  rightPanelWidth: 1080,
  bottomPanelHeight: 1080,
  selectedPanel: 'Design*',
  sidebar: {
    showRoots: true,
    collapsedRoots: ['misc']
  }
})
