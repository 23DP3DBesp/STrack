<template>
  <MainLayout>
    <section class="work-hero-card">
      <div class="work-kicker">Profile settings</div>
      <h1 class="work-title">Manage your account</h1>
      <p class="work-subtitle">Update personal details, preferences, and security settings.</p>
    </section>

    <section class="profile-shell">
      <v-form ref="formRef" @submit.prevent="submitProfile" class="profile-form">
        <v-card class="profile-card">
          <v-card-title>Account details</v-card-title>
          <v-card-text>
            <div v-if="profileError" class="form-error">{{ profileError }}</div>

            <v-text-field v-model="form.login" label="Login" :rules="[rules.required]" />
            <v-text-field v-model="form.display_name" label="Display name" />
          </v-card-text>
        </v-card>

        <v-card class="profile-card mt-4">
          <v-card-title>Preferences</v-card-title>
          <v-card-text>
            <v-select
              v-model="form.locale"
              label="Language"
              :items="languages"
              @update:model-value="handleLocaleChange"
            />
            <v-select v-model="form.currency" label="Currency" :items="currencies" />
            <v-select v-model="form.distance_unit" label="Distance unit" :items="distances" />

            <div class="profile-preview">
              <strong>Preview</strong>
              <span>Language: {{ form.locale }}</span>
              <span>Currency: {{ form.currency }}</span>
              <span>Distance: {{ form.distance_unit }}</span>
            </div>
          </v-card-text>
        </v-card>

        <v-card class="profile-card mt-4">
          <v-card-title>Security</v-card-title>
          <v-card-text>
            <div v-if="passwordError" class="form-error">{{ passwordError }}</div>

            <v-form ref="passwordFormRef" @submit.prevent="submitPasswordChange">
              <v-text-field
                v-model="passwordForm.current_password"
                label="Current password"
                type="password"
                :rules="[rules.required]"
              />
              <v-text-field
                v-model="passwordForm.password"
                label="New password"
                type="password"
                :rules="[rules.required, rules.minLength(8)]"
              />
              <v-text-field
                v-model="passwordForm.password_confirmation"
                label="Confirm new password"
                type="password"
                :rules="[rules.required, rules.confirmPassword]"
              />

              <v-btn
                class="ui-btn-secondary profile-block-btn"
                type="submit"
                variant="outlined"
                block
              >
                Update password
              </v-btn>
            </v-form>
          </v-card-text>
        </v-card>

        <v-card class="profile-card mt-4 danger-zone">
          <v-card-title>Delete account</v-card-title>
          <v-card-text>
            <p class="work-item-sub">This permanently deletes your account and all garage data.</p>

            <div v-if="deleteError" class="form-error">{{ deleteError }}</div>

            <v-form @submit.prevent="deleteAccount">
              <v-text-field
                v-model="deleteForm.password"
                label="Type password to confirm"
                type="password"
              />
              <v-btn class="profile-block-btn" type="submit" color="error" block>
                Delete account
              </v-btn>
            </v-form>
          </v-card-text>
        </v-card>

        <div class="save-actions">
          <v-btn to="/app" class="mr-2" variant="text">Back to dashboard</v-btn>
          <v-btn class="ui-btn-primary" type="submit" :loading="savingProfile">Save profile</v-btn>
        </div>
      </v-form>
    </section>

    <v-snackbar v-model="showSaved" color="success" timeout="3000"> Profile updated </v-snackbar>

    <v-snackbar v-model="showPassword" color="success" timeout="3000">
      Password updated
    </v-snackbar>
  </MainLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import MainLayout from '../layouts/MainLayout.vue'

const router = useRouter()
const auth = useAuthStore()

const formRef = ref(null)
const passwordFormRef = ref(null)

const savingProfile = ref(false)
const showSaved = ref(false)
const showPassword = ref(false)

const profileError = ref('')
const passwordError = ref('')
const deleteError = ref('')

const form = reactive({
  login: '',
  display_name: '',
  locale: 'lv',
  theme: 'light',
  currency: 'EUR',
  distance_unit: 'km'
})

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: ''
})

const deleteForm = reactive({
  password: ''
})

const languages = [
  { title: 'Latviešu', value: 'lv' },
  { title: 'English', value: 'en' }
]

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
  required: (v) => !!v || 'Required',
  minLength: (v) => (v && v.length >= 8) || 'Minimum 8 characters',
  confirmPassword: (v) => v === passwordForm.password || 'Passwords must match'
}

onMounted(() => {
  form.login = auth.user?.login || ''
  form.display_name = auth.user?.display_name || ''
  form.locale = auth.user?.locale || 'lv'
  form.theme = auth.user?.theme || 'light'
  form.currency = auth.user?.currency || 'EUR'
  form.distance_unit = auth.user?.distance_unit || 'km'

  auth.setUserLocale(form.locale)
})

const handleLocaleChange = (locale) => {
  auth.setUserLocale(locale)
}

const submitProfile = async () => {
  profileError.value = ''

  const validation = await formRef.value?.validate()
  if (!validation?.valid) return

  savingProfile.value = true

  try {
    await auth.updateProfile(form)
    showSaved.value = true
  } catch (error) {
    profileError.value =
      error?.response?.data?.message || auth.extractError(error) || 'Failed to update profile'
  } finally {
    savingProfile.value = false
  }
}

const submitPasswordChange = async () => {
  passwordError.value = ''

  const validation = await passwordFormRef.value?.validate()
  if (!validation?.valid) return

  try {
    await auth.updatePassword({
      current_password: passwordForm.current_password,
      password: passwordForm.password,
      password_confirmation: passwordForm.password_confirmation
    })

    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''

    showPassword.value = true
  } catch (error) {
    passwordError.value =
      error?.response?.data?.message || auth.extractError(error) || 'Failed to update password'
  }
}

const deleteAccount = async () => {
  deleteError.value = ''

  if (!deleteForm.password) {
    deleteError.value = 'Password is required'
    return
  }

  if (!window.confirm('Really delete your account? This action cannot be undone.')) {
    return
  }

  try {
    await auth.deleteAccount({ password: deleteForm.password })
    router.push({ name: 'home' })
  } catch (error) {
    deleteError.value =
      error?.response?.data?.message || auth.extractError(error) || 'Failed to delete account'
  }
}
</script>
