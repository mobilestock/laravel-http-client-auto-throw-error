import { Dimensions, PixelRatio, Platform, StatusBar } from 'react-native'

import defaultColors from './defaultColors'

export const theme = {
  colors: {
    text: {
      primary: defaultColors.black,
      secondary: defaultColors.white,
    },

    container: {
      shadow: defaultColors.shadow,
      pure: defaultColors.white,
    },

    button: {
      default: defaultColors.primary,
    },
  },

  layout: {
    size: (size: number): number => {
      if (PixelRatio.get() < 2) {
        return PixelRatio.getPixelSizeForLayoutSize(size) * 1.3
      }
      if (PixelRatio.get() >= 2 && PixelRatio.get() < 3) {
        return PixelRatio.getPixelSizeForLayoutSize(size) * 0.9
      }
      if (PixelRatio.get() >= 3) {
        return PixelRatio.getPixelSizeForLayoutSize(size) * 0.7
      }
      return 16
    },
    width: (widthPercent: number): number => {
      let screenWidth = Dimensions.get('window').width
      const elemWidth = typeof widthPercent === 'number' ? widthPercent : parseFloat(widthPercent)
      return PixelRatio.roundToNearestPixel((screenWidth * elemWidth) / 100)
    },
    height: (heightPercent: number): number => {
      let screenHeight = Dimensions.get('window').height
      const elemHeight = typeof heightPercent === 'number' ? heightPercent : parseFloat(heightPercent)
      return PixelRatio.roundToNearestPixel((screenHeight * elemHeight) / 100)
    },
  },

  fonts: {
    size(fontSize: number, standardScreenHeight = 680): number {
      const { height, width } = Dimensions.get('window')
      const standardLength = width > height ? width : height
      let offset = 0
      let heightPercent = 0

      if (width > height) {
        offset = 0
      } else if (Platform.OS === 'ios') {
        offset = 190
      } else {
        offset = StatusBar.currentHeight || 0
      }

      const deviceHeight = Platform.OS !== 'android' ? standardLength - offset : standardLength

      if (typeof fontSize === 'number') {
        heightPercent = (fontSize * deviceHeight) / standardScreenHeight
      } else {
        heightPercent = (parseFloat(fontSize) * deviceHeight) / 100
      }
      return Math.round(heightPercent)
    },
  },
}
