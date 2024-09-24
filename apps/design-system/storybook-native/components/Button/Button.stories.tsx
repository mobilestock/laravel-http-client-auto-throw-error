import { Button } from "@mobilestock/base/index.android";
import type { Meta, StoryObj } from "@storybook/react";
import React from "react";
import { View } from "react-native";
import { ThemeProvider } from "styled-components";
import { theme } from "../../theme";

Button.defaultProps = {
    isLoading: false,
    text: 'Clique aqui',
    style: {
      width: 200,
      height: 50,
      backgroundColor: theme.cores.azul40
    }
  }

const meta = {
  title: "Botão",
    component: Button,
    parameters: {
      notes: `
        Exemplo de código:

        <Button
            isLoading={false}
            text="Clique aqui"
        />
      `,
  },
  args: {
    text: "Clique aqui",
  },
  decorators: [
    (Story) => (
      <View style={{ padding: 16 }}>
        <ThemeProvider theme={theme}>
          <Story />
        </ThemeProvider>
      </View>
    ),
    ],
    argTypes: {
        isLoading: {
          control: 'boolean',
          description: 'Indica se o botão está em estado de carregamento',
          defaultValue: false
        },
        text: {
          control: 'text',
          description: 'Texto exibido no botão',
          defaultValue: 'Clique aqui'
        },
        disabled: {
          control: 'boolean',
          description: 'Desativa o botão',
          defaultValue: false
        },
      }
} satisfies Meta<typeof Button>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Basic: Story = {
    args: {
      isLoading: false,
      text: 'Clique aqui'
    }
  }

export const Loading: Story = {
    args: {
        isLoading: true,
        text: 'Carregando...'
    }
}

export const Disabled: Story = {
    args: {
        isLoading: false,
        text: 'Desativado',
        disabled: true
    }
}
