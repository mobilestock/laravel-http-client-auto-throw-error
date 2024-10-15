import defaultColors from './defaultColors'

export const theme = {
  colors: {
    text: {
      default: defaultColors.secondary,
      regular: defaultColors.primary,
      notice: defaultColors.orange50,
      danger: defaultColors.tertiary,
    },
    container: {
      default: defaultColors.grey7,
      outline: {
        default: defaultColors.grey75,
        soft: defaultColors.secondary,
      },
      pure: defaultColors.secondary,
      shadow: defaultColors.shadow,
      purpleShadow: defaultColors.purpleShadow,
    },
    button: {
      default: defaultColors.blue41,
      anchor: defaultColors.blue50,
      shadow: defaultColors.shadow,
    },
    alert: {
      urgent: defaultColors.tertiary,
      tip: defaultColors.yellow50,
    },
  },
}
