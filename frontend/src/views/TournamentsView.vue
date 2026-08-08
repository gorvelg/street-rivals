<template>
  <section class="future-page">
    <div class="page-heading">
      <div>
        <p class="eyebrow">
          Compétition
        </p>

        <h1>
          Tournois
        </h1>
      </div>
    </div>

    <section class="panel future-panel">
      <span class="future-symbol">
        T
      </span>

      <div>
        <h2>
          Bientôt disponible
        </h2>

        <p>
          Les tournois arriveront dans une prochaine
          version de Street Rivals.
        </p>
      </div>
    </section>
  </section>
</template>

<style scoped>
.future-panel {
  display: flex;

  align-items: center;

  gap: 20px;
}

.future-symbol {
  display: flex;

  width: 64px;
  height: 64px;

  flex: 0 0 auto;

  align-items: center;
  justify-content: center;

  border-radius: 18px;

  background:
      rgba(
          255,
          255,
          255,
          0.07
      );

  font-size: 1.3rem;
  font-weight: 900;
}

.future-panel h2 {
  margin:
      0
      0
      5px;
}

.future-panel p {
  margin: 0;

  opacity: 0.65;
}
</style>