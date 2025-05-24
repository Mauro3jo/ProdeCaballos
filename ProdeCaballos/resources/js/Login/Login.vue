<template>
  <div class="login-container">
    <h2>Iniciar sesión</h2>
    <form @submit.prevent="handleLogin">
      <label>Email:</label>
      <input v-model="email" type="email" required />

      <label>Contraseña:</label>
      <input v-model="password" type="password" required />

      <button type="submit">Ingresar</button>

      <div v-if="error" class="error">{{ error }}</div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const email = ref('')
const password = ref('')
const error = ref(null)

const handleLogin = async () => {
  error.value = null

  try {
    await axios.post('/login', {
      email: email.value,
      password: password.value
    })

    window.location.href = '/admin'
  } catch (err) {
    error.value = 'Credenciales incorrectas.'
  }
}
</script>

<style scoped>
.login-container {
  max-width: 400px;
  margin: 80px auto;
  padding: 20px;
  border: 1px solid #ccc;
  border-radius: 8px;
}
input {
  display: block;
  width: 100%;
  padding: 8px;
  margin-bottom: 12px;
}
button {
  padding: 10px;
  width: 100%;
  background: black;
  color: white;
  border: none;
}
.error {
  color: red;
  margin-top: 10px;
}
</style>
