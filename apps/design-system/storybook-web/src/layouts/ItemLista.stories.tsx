import type { Meta } from '@storybook/react'

export default {
  title: 'layouts/ItemLista',
  component: () => <div>My story</div>
} as Meta

export const PadraoComProduto: Meta = {
  parameters: {
    design: {
      type: 'figspec',
      url: 'https://www.figma.com/design/C4ZZ5mNbqzUpTQnsEX6mQV/mobile-design-system?node-id=12-183&t=HBexEFfP8sHV1ZII-4'
    }
  }
}
export const Padrao: Meta = {
  parameters: {
    design: {
      type: 'figspec',
      url: 'https://www.figma.com/design/C4ZZ5mNbqzUpTQnsEX6mQV/mobile-design-system?node-id=12-186&t=4W1bOmFMElNmjw0Y-4'
    }
  }
}
