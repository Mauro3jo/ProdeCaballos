<template>
  <nav class="navbar">
    <div class="navbar-container">
      <div class="navbar-logo">
        <img :src="logoUrl" alt="Logo Prode" />
      </div>

      <button class="navbar-toggle" @click="menuAbierto = !menuAbierto">
        ☰
      </button>

      <div :class="['navbar-links', { 'activo': menuAbierto }]">
<!-- <router-link to="/reglas" class="navbar-link">Reglas</router-link> -->
        <router-link to="/" class="navbar-link">Prodes</router-link>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref } from 'vue'

const menuAbierto = ref(false)

const logoUrl = import.meta.env.VITE_IMAGES_PUBLIC_PATH
  ? `${import.meta.env.VITE_IMAGES_PUBLIC_PATH.replace(/\/$/, '')}/Nav.jpg`
  : '/Nav.jpg'
</script>

<style scoped src="./NavbarUsuarios.css"></style>
