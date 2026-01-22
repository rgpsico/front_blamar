<template>
  <div class="brand-panel">
    <div class="brand-panel__badge">
      <span class="brand-panel__dot"></span>
      ERP Turismo Premium
    </div>
    <h1 class="brand-panel__title">
      Blumar ERP
      <span>para experiências que encantam viajantes.</span>
    </h1>
    <p class="brand-panel__lead">
      Centralize reservas, contratos e margens com visão completa do funil turístico.
      Tudo em um painel focado em performance de destinos e operadoras.
    </p>

    <div class="brand-panel__highlights">
      <div class="brand-panel__highlight">
        <v-icon color="primary" size="28">mdi-map-marker-radius</v-icon>
        <div>
          <strong>+120 destinos</strong>
          <span>mapa vivo de parceiros</span>
        </div>
      </div>
      <div class="brand-panel__highlight">
        <v-icon color="primary" size="28">mdi-account-tie</v-icon>
        <div>
          <strong>+980 agentes</strong>
          <span>network com inteligência comercial</span>
        </div>
      </div>
    </div>

    <v-divider class="my-6"></v-divider>

    <div class="brand-panel__features">
      <div class="brand-panel__feature">
        <v-icon color="secondary" size="20">mdi-cash-multiple</v-icon>
        <span>Precificação dinâmica por temporada</span>
      </div>
      <div class="brand-panel__feature">
        <v-icon color="secondary" size="20">mdi-shield-check</v-icon>
        <span>Controle de riscos e compliance</span>
      </div>
      <div class="brand-panel__feature">
        <v-icon color="secondary" size="20">mdi-airplane</v-icon>
        <span>Integração com allotment e cotação</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'BrandPanel'
}
</script>

<style scoped>
.brand-panel {
  max-width: 520px;
  color: #0f172a;
}

.brand-panel__badge {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 8px 16px;
  border-radius: 999px;
  background: rgba(249, 2, 14, 0.12);
  color: #7f1d1d;
  font-weight: 600;
  letter-spacing: 0.4px;
  text-transform: uppercase;
  font-size: 12px;
}

.brand-panel__dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #f9020e;
  box-shadow: 0 0 10px rgba(249, 2, 14, 0.4);
}

.brand-panel__title {
  font-family: 'Playfair Display', serif;
  font-size: 42px;
  margin: 20px 0 12px;
  line-height: 1.1;
}

.brand-panel__title span {
  display: block;
  font-size: 26px;
  font-weight: 500;
  color: #475569;
}

.brand-panel__lead {
  font-size: 16px;
  line-height: 1.6;
  color: #334155;
  margin-bottom: 24px;
}

.brand-panel__highlights {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}

.brand-panel__highlight {
  display: flex;
  gap: 12px;
  align-items: center;
  background: #ffffff;
  border-radius: 16px;
  padding: 14px 16px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
}

.brand-panel__highlight strong {
  display: block;
  font-size: 16px;
}

.brand-panel__highlight span {
  display: block;
  font-size: 13px;
  color: #64748b;
}

.brand-panel__features {
  display: grid;
  gap: 12px;
}

.brand-panel__feature {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  color: #1e293b;
}

@media (max-width: 959px) {
  .brand-panel {
    text-align: left;
  }

  .brand-panel__title {
    font-size: 34px;
  }
}
</style>
