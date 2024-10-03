import type { Meta } from '@storybook/react'

export default {
  title: 'layouts/Screens',
  component: () => <div>My story</div>
} as Meta

export const Default: Meta = {
  parameters: {
    design: {
      type: 'figspec',
      url: 'https://www.figma.com/design/C4ZZ5mNbqzUpTQnsEX6mQV/mobile-design-system?node-id=10-245&t=lVOy4WIcsDuLw5mQ-4'
    }
  }
}
export const DefaultWithHeader: Meta = {
  parameters: {
    design: {
      type: 'figspec',
      url: 'https://www.figma.com/design/C4ZZ5mNbqzUpTQnsEX6mQV/mobile-design-system?node-id=10-248&t=lVOy4WIcsDuLw5mQ-4'
    }
  }
}
