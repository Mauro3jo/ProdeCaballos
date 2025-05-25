<template>
  <div class="admin-home">
    <h1>Bienvenido, {{ userName }}</h1>
    <p>Este es tu panel de administración.</p>

    <div class="stats">
      <div class="card">
        <h2>Carreras</h2>
        <p>Próximamente disponibles</p>
      </div>
      <div class="card">
        <h2>Usuarios</h2>
        <p>Gestión de participantes</p>
      </div>
      <div class="card">
        <h2>Resultados</h2>
        <p>Revisión de resultados</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

// Esto es solo una demo. En el futuro podés pasar el nombre real desde Blade.
const userName = ref('Administrador')
</script>

<style scoped>
.admin-home {
  padding: 40px;
  text-align: center;
  font-size: 20px;
  background-color: #f8f8f8;
  min-height: 100vh;
  color: #222;
}

.stats {
  margin-top: 40px;
  display: flex;
  justify-content: center;
  gap: 24px;
  flex-wrap: wrap;
}

.card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  width: 240px;
  transition: transform 0.3s;
}

.card:hover {
  transform: scale(1.05);
}

.card h2 {
  margin-bottom: 8px;
  font-size: 20px;
}
</style>
