import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import '@mdi/font/css/materialdesignicons.css'

const palette = {
  dark: false,
  colors: {
    background: '#f4f6f8',
    surface: '#ffffff',
    primary: '#111827',
    secondary: '#4b5563',
    accent: '#0f766e',
    error: '#b91c1c',
    info: '#0369a1',
    success: '#15803d',
    warning: '#a16207'
  }
}

export default createVuetify({
  theme: {
    defaultTheme: 'docbox',
    themes: {
      docbox: palette
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
