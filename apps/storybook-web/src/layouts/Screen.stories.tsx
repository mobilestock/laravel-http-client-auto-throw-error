import type { Meta } from '@storybook/react'

export default {
  title: 'Layouts/Screens',
  component: () => <div>My story</div>
} as Meta

export const Padrao: Meta = {
  parameters: {
    design: {
      type: 'figma',
      url: 'https://www.figma.com/design/dYA59eOxQz1w6N1UlI95Dp/Untitled?node-id=1-45&t=xZ9sgrzZNz2o25wo-4'
    },
    layout: 'padded'
  }
}
export const PadraoComCabecalho: Meta = {
  parameters: {
    design: {
      type: 'figma',
      url: 'https://www.figma.com/design/dYA59eOxQz1w6N1UlI95Dp/Untitled?node-id=1-51&t=xZ9sgrzZNz2o25wo-4'
    },
    layout: 'padded'
  }
}
export const ComCabecalhoCustomizado: Meta = {
  parameters: {
    design: {
      type: 'figma',
      url: 'https://www.figma.com/design/dYA59eOxQz1w6N1UlI95Dp/Untitled?node-id=1-69&t=xZ9sgrzZNz2o25wo-4'
    },
    layout: 'padded'
  }
}
