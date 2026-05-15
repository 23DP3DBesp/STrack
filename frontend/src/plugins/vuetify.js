import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import '@mdi/font/css/materialdesignicons.css'

const light = {
  dark: false,
  colors: {
    background: '#f5f6f8',
    surface: '#ffffff',
    primary: '#9f1d2d',
    secondary: '#6b7280',
    accent: '#9f1d2d',
    error: '#c62828',
    info: '#2563eb',
    success: '#15803d',
    warning: '#a16207'
  }
}

export default createVuetify({
  theme: {
    defaultTheme: 'light',
    themes: {
      light
    }
  },
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: { mdi }
  },
  defaults: {
    VCard: {
      rounded: 'xl',
      elevation: 0
    },
    VTextField: {
      variant: 'outlined',
      density: 'comfortable'
    },
    VTextarea: {
      variant: 'outlined',
      density: 'comfortable'
    },
    VSelect: {
      variant: 'outlined',
      density: 'comfortable'
    },
    VFileInput: {
      variant: 'outlined',
      density: 'comfortable'
    }
  }
})
