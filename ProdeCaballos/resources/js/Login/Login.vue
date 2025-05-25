<template>
  <div class="login-container">
    <h2>Iniciar Sesión</h2>

    <form @submit.prevent="handleLogin">
      <div class="form-group">
        <label for="email">Email</label>
        <input v-model="email" type="email" id="email" required />
      </div>

      <div class="form-group">
        <label for="password">Contraseña</label>
        <input v-model="password" type="password" id="password" required />
      </div>

      <button type="submit" :disabled="loading">
        {{ loading ? 'Ingresando...' : 'Ingresar' }}
      </button>

      <p v-if="error" class="error-message">{{ error }}</p>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref(null)

const handleLogin = async () => {
  error.value = null
  loading.value = true

  try {
    const response = await axios.post('/auth/login', {
      email: email.value,
      password: password.value,
    })

    window.location.href = response.data.redirect || '/admin'

  } catch (err) {
    if (err.response?.status === 422) {
      error.value = err.response.data.errors.email?.[0] ?? 'Credenciales inválidas.'
    } else {
      error.value = 'Error del servidor. Intenta de nuevo.'
    }
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  max-width: 400px;
  margin: 80px auto;
  padding: 30px;
  border: 1px solid #ccc;
  border-radius: 10px;
  background-color: #f9f9f9;
}

h2 {
  text-align: center;
  margin-bottom: 20px;
  color: #222;
}

.form-group {
  margin-bottom: 16px;
}

label {
  font-weight: bold;
  display: block;
  margin-bottom: 6px;
}

input {
  width: 100%;
  padding: 8px;
  box-sizing: border-box;
}

button {
  width: 100%;
  padding: 10px;
  background-color: #111;
  color: white;
  border: none;
  cursor: pointer;
  font-weight: bold;
  border-radius: 4px;
}

button:disabled {
  background-color: #444;
  cursor: not-allowed;
}

.error-message {
  color: red;
  margin-top: 12px;
  text-align: center;
}
</style>
