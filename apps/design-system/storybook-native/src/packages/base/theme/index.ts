import { Dimensions, PixelRatio, Platform, StatusBar } from 'react-native'

export const theme = {
  cores: {
    corSecundaria: '#4ba8ff',
    botaoPadrao: '#4B6680',
    backgroundGray: '#7D8D9C',
    tomate: '#FF6C63',

    azul100: '#4ba8ff',
    azul40: '#222465',
    azul30: '#014C76',
    azul20: '#96CCFF',
    azul: 'blue',

    branco90: '#f1f1f1',
    branco: '#fff',

    bege10: '#c4c4c4',

    preto: '#000',

    texto: '#292929',

    sucesso: '#0cb536',

    alerta80: '#F51D07',
    alerta80A_bb: '#F51D07bb',
    alerta70: '#FF3D0D',
    alerta50: '#E86513',
    alerta20: '#F2BF2C',

    whatsapp: '#128c7e',

    situacoes: {
      conferencia: {
        background: '#363636',
        color: '#eeeeee'
      },
      expedicao: {
        background: '#008DDC',
        color: '#f1f1f1'
      },
      pontoTransporte: {
        background: '#DC7700',
        color: '#f1f1f1'
      },
      entregarCliente: {
        background: '#00DC58',
        color: '#000'
      },
      entregue: {
        background: '#00DC58',
        color: '#f1f1f1'
      },
      devolucao: {
        background: '#8B71D9',
        color: '#000'
      },
      monitoramento: {
        color: '#FFF',
        background: '#4ba8ff'
      }
    }
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
    }
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
    }
  }
}
