import { Button } from "@mobilestock/base";
import type { Meta, StoryObj } from "@storybook/react";

Button.defaultProps = {
    isLoading: false,
    texto: "Botão",
    style: {
        backgroundColor: "blue",
        color: "white",
        padding: "10px",
        minWidth: "100px",
        borderRadius: "5px",
        cursor: "pointer",
    },
};

const meta = {
    title: "Componentes/Botao",
    component: Button,
    parameters: {
        layout: "centered",
        docs: {
            subtitle: "Botão padrão do sistema.",
        },
    },
    args: {
        isLoading: false,
        texto: "Botão",
        onClick: () => { },
    },
    argTypes: {
        isLoading: {
            control: "boolean",
            description: "Indica se o botão está em estado de carregamento.",
            defaultValue: false,
        },
        texto: {
            control: "text",
            description: "Texto exibido dentro do botão.",
            defaultValue: "Botão",
        },
        onClick: {
            action: () => {alert("Clicou no botão")},
            description: "Função chamada ao clicar no botão.",
            defaultValue: () => { },
            control: () => { alert("Clicou no botão") },
            type: { name: "function" },
        },
    },
} satisfies Meta<typeof Button>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Primary: Story = {
    args: {
        isLoading: false,
        texto: "Botão",
    },
};

export const Loading: Story = {
    args: {
        isLoading: true,
        texto: "Carregando...",
        disabled: true
    },
};

export const Disabled: Story = {
    args: {
        isLoading: false,
        texto: "Desativado",
        disabled: true,
    },
};
