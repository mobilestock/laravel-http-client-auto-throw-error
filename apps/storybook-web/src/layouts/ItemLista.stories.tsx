import type { Meta } from '@storybook/react'

export default {
  title: 'Layouts/ItemLista',
  component: () => <div>My story</div>
} as Meta

export const PadraoComProduto: Meta = {
  parameters: {
    design: {
      type: 'figma',
      url: 'https://www.figma.com/design/dYA59eOxQz1w6N1UlI95Dp/Untitled?node-id=1-77&t=xZ9sgrzZNz2o25wo-4'
    },
    layout: 'padded'
  }
}
export const Padrao: Meta = {
  parameters: {
    design: {
      type: 'figma',
      url: 'https://www.figma.com/design/dYA59eOxQz1w6N1UlI95Dp/Untitled?node-id=1-95&t=xZ9sgrzZNz2o25wo-4'
    },
    layout: 'padded'
  }
}
