import type { Meta, StoryObj } from "@storybook/react";
import { Avatar } from "../../../packages/base/index";

const meta = {
    title: "Componentes/Avatar",
    component: Avatar,
    parameters: {
        layout: "centered",
        docs: {
            subtitle: "Avatar com imagem genérica e borda arredondada.",
        },
    },
    args: {
        src: "https://via.placeholder.com/150",
        alt: "Avatar",
    },
    argTypes: {
        src: {
            control: "text",
            description: "URL da imagem exibida no Avatar.",
            defaultValue: "https://via.placeholder.com/150",
        },
        alt: {
            control: "text",
            description: "Texto alternativo para a imagem.",
            defaultValue: "Avatar",
        },
        width: {
            control: "number",
            description: "Largura da imagem do Avatar.",
            defaultValue: 50,
        },
        height: {
            control: "number",
            description: "Altura da imagem do Avatar.",
            defaultValue: 50,
        },
    },
} satisfies Meta<typeof Avatar>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    src: "https://images.unsplash.com/photo-1725818184221-8ba1698cd9bf?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
    alt: "Avatar Padrão",
    width: 150,
    height: 150,
  },
};
