<template>
  <MainLayout>
    <section class="work-hero-card">
      <div class="work-kicker">Profile settings</div>
      <h1 class="work-title">Manage your account</h1>
      <p class="work-subtitle">
        Update personal details, preferences, language, and theme.
      </p>
    </section>

    <v-container>
      <v-form ref="formRef" @submit.prevent="submitProfile" class="max-w-lg">
        <v-card class="profile-card">
          <v-card-title>Account details</v-card-title>
          <v-card-text>
            <v-text-field v-model="form.login" label="Login" :rules="[rules.required]" />
            <v-text-field v-model="form.display_name" label="Display name" />
          </v-card-text>
        </v-card>

        <v-card class="profile-card mt-4">
          <v-card-title>Preferences</v-card-title>
          <v-card-text>
            <v-select v-model="form.locale" label="Language" :items="languages" @update:model-value="handleLocaleChange" />
            <v-select v-model="form.currency_unit" label="Currency" :items="currencies" />
            <v-select v-model="form.distance_unit" label="Distance unit" :items="distances" />
            <div class="mt-4 p-3 bg-blue-grey-lighten-5">
              <strong>Preview:</strong> Language: {{ form.locale }}
            </div>
          </v-card-text>
        </v-card>

        <v-card class="profile-card mt-4">
          <v-card-title>Security</v-card-title>
          <v-card-text>
            <v-form ref="passwordForm" @submit.prevent="updatePassword">
              <v-text-field v-model="passwordForm.current_password" label="Current password" type="password" :rules="[rules.required]" />
              <v-text-field v-model="passwordForm.password" label="New password" type="password" :rules="[rules.required, rules.minLength(8)]" />
              <v-text-field v-model="passwordForm.password_confirmation" label="Confirm new password" type="password" :rules="[rules.required, rules.confirmPassword]" />
              <v-btn type="submit" variant="outlined" block>Update password</v-btn>
            </v-form>
          </v-card-text>
        </v-card>

        <v-card class="profile-card mt-4 danger-zone">
          <v-card-title>Delete account</v-card-title>
          <v-card-text>
            <p>This permanently deletes your account and all garage data.</p>
            <v-form @submit.prevent="deleteAccount">
              <v-text-field v-model="deleteForm.password" label="Type password to confirm" type="password" />
              <v-btn type="submit" color="error" block>Delete account</v-btn>
            </v-form>
          </v-card-text>
        </v-card>

  <div class="save-actions">
          <v-btn to="/app" class="mr-2" variant="text">Back to dashboard</v-btn>
          <v-btn type="submit" color="primary">Save profile</v-btn>
        </div>

    <v-snackbar v-model="showSaved" color="success" timeout="3000">
      Profile updated
    </v-snackbar>

    <v-snackbar v-model="showPassword" color="success" timeout="3000">
      Password updated
    </v-snackbar>
      </v-form>
    </v-container>
  </MainLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import MainLayout from '../layouts/MainLayout.vue'

const router = useRouter()
const auth = useAuthStore()

onMounted(() => {
  form.login = auth.user?.login || ''
  form.display_name = auth.user?.display_name || ''
  form.locale = auth.user?.locale || 'en'
  auth.setUserLocale(form.locale)
})

const formRef = ref()
const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: ''
})
const deleteForm = reactive({
  password: ''
})

const form = reactive({
  login: auth.user?.login || '',
  display_name: auth.user?.display_name || '',
  locale: auth.user?.locale || 'en',
  theme: auth.user?.theme || 'light',
  currency_unit: auth.user?.currency_unit || 'EUR',
  distance_unit: auth.user?.distance_unit || 'km'
})

auth.setUserLocale(form.locale)

const languages = [
  { title: 'English', value: 'en' },
  { title: 'Latviešu', value: 'lv' }
]

// Removed theme toggle - light only

const currencies = [
  { title: 'EUR', value: 'EUR' },
  { title: 'USD', value: 'USD' },
  { title: 'GBP', value: 'GBP' }
]

const distances = [
  { title: 'km', value: 'km' },
  { title: 'miles', value: 'mi' }
]

const rules = {
  required: v => !!v || 'Required',
  minLength: v => (v && v.length >= 8) || 'Minimum 8 characters',
  confirmPassword: v => v === passwordForm.password || 'Passwords must match'
}

const handleLocaleChange = (locale) => {
  auth.setUserLocale(locale)
}

// handleThemeChange removed

const submitProfile = async () => {
  const valid = await formRef.value?.validate()
  if (!valid) return

  try {
    const updated = await auth.updateProfile(form)
    auth.user = updated
  } catch (e) {
    console.error(e)
  }
}

const updatePassword = async () => {
  try {
    await auth.updatePassword(passwordForm)
    Object.assign(passwordForm, { current_password: '', password: '', password_confirmation: '' })
  } catch (e) {
    console.error(e)
  }
}

const deleteAccount = async () => {
  if (!confirm('Really delete?')) return
  try {
    await auth.deleteAccount({ password: deleteForm.password })
  } catch (e) {
    console.error(e)
  }
}



</script>

<style scoped>
.max-w-lg {
  max-width: 600px;
}

.profile-card {
  margin-bottom: 1rem;
}

.danger-zone {
  border-left: 4px solid theme('colors.error');
}

.save-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 2rem;
  gap: 1rem;
}

.mr-2 {
  margin-right: 0.5rem;
}
</style>

