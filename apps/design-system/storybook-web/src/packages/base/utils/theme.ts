import defaultColors from './defaultColors'

export const theme = {
  colors: {
    text: {
      primary: defaultColors.black100,
      secondary: defaultColors.white50
    },

    decorator: {
      shadow: defaultColors.shadow,
      purpleShadow: defaultColors.purpleShadow,
      outline: defaultColors.outline,
      soft: defaultColors.white75,
      pure: defaultColors.white100
    },

    alert: {
      success: defaultColors.green40,
      tip: defaultColors.yellow20,
      warning: defaultColors.orangeRed50,
      urgent: defaultColors.red50
    },

    button: {
      base: defaultColors.primary,
      confirm: defaultColors.green40,
      cancel: defaultColors.red50,
      check: defaultColors.white50,
      next: defaultColors.primary
    },

    background: {
      dark: defaultColors.black70,
      light: defaultColors.white100
    }
  }
}
