import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import '@mdi/font/css/materialdesignicons.css'

const light = {
  dark: false,
  colors: {
    background: '#f4f4f6',
    surface: '#ffffff',
    primary: '#111827',
    secondary: '#6b7280',
    accent: '#e30000',
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
