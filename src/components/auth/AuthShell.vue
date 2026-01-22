<template>
  <div class="auth-shell">
    <div class="auth-shell__glow"></div>
    <v-container fluid class="fill-height auth-shell__container">
      <v-row no-gutters class="fill-height">
        <v-col cols="12" md="6" class="auth-shell__brand">
          <BrandPanel />
        </v-col>
        <v-col cols="12" md="6" class="auth-shell__form">
          <div class="auth-shell__form-inner">
            <slot />
          </div>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script>
import BrandPanel from './BrandPanel.vue'

export default {
  name: 'AuthShell',
  components: {
    BrandPanel
  }
}
</script>

<style scoped>
.auth-shell {
  min-height: 100vh;
  background: radial-gradient(circle at top left, #fff1f1 0%, #f8fafc 35%, #e2f3f7 100%);
  position: relative;
  overflow: hidden;
  font-family: 'Manrope', sans-serif;
}

.auth-shell__glow {
  position: absolute;
  width: 520px;
  height: 520px;
  right: -120px;
  top: -160px;
  background: radial-gradient(circle, rgba(249, 2, 14, 0.25), rgba(249, 2, 14, 0));
  filter: blur(2px);
  pointer-events: none;
}

.auth-shell__container {
  padding: 0;
}

.auth-shell__brand {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 40px;
}

.auth-shell__form {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 28px;
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(30, 41, 59, 0.96));
}

.auth-shell__form-inner {
  width: 100%;
  max-width: 420px;
}

@media (max-width: 959px) {
  .auth-shell__brand {
    padding: 32px 24px;
  }

  .auth-shell__form {
    padding: 32px 24px;
  }
}
</style>
