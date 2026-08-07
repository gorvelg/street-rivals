<script setup lang="ts">
import {
  onMounted,
  onUnmounted,
} from 'vue'
import { RouterLink } from 'vue-router'
import AdminNavigation
  from '../../components/admin/AdminNavigation.vue'
import { useAdminCardsStore }
  from '../../stores/adminCards'
import type {
  AdminCardKind,
  AdminEquipmentSlot,
} from '../../types/admin'

const cardsStore =
    useAdminCardsStore()

function formatAverageLevel(
    value: number | null,
): string {
  if (value === null) {
    return '—'
  }

  return new Intl.NumberFormat(
      'fr-FR',
      {
        maximumFractionDigits: 2,
      },
  ).format(value)
}

function formatEffectConfig(
    value: Record<string, unknown>,
): string {
  try {
    return JSON.stringify(
        value,
        null,
        2,
    )
  } catch {
    return 'Configuration indisponible'
  }
}

function kindLabel(
    kind: AdminCardKind,
): string {
  switch (kind) {
    case 'equipment':
      return 'Équipement'

    case 'stat_boost':
      return 'Bonus permanent'

    case 'ability':
    default:
      return 'Capacité'
  }
}

function equipmentSlotLabel(
    slot: AdminEquipmentSlot,
): string {
  switch (slot) {
    case 'engine':
      return 'Moteur'

    case 'wheels':
      return 'Roues'

    case 'brakes':
      return 'Freins'

    case 'gearbox':
      return 'Boîte de vitesses'

    case 'chassis':
      return 'Châssis'

    case 'aero':
      return 'Aérodynamique'
  }
}

onMounted(
    async (): Promise<void> => {
      await cardsStore.loadCards()
    },
)

onUnmounted((): void => {
  cardsStore.reset()
})
</script>

<template>
  <main class="admin-cards">
    <header class="admin-header">
      <div>
        <p class="admin-eyebrow">
          Street Rivals
        </p>

        <h1>Cartes</h1>

        <p class="admin-description">
          Catalogue, catégories, équipements,
          paliers et utilisation des cartes.
        </p>
      </div>

      <button
          type="button"
          class="refresh-button"
          :disabled="cardsStore.isLoading"
          @click="
            cardsStore.loadCards(
                cardsStore.pagination.page,
            )
          "
      >
        {{
          cardsStore.isLoading
              ? 'Actualisation…'
              : 'Actualiser'
        }}
      </button>
    </header>

    <AdminNavigation />

    <form
        class="filters"
        @submit.prevent="
          cardsStore.submitFilters
        "
    >
      <label
          class="filter-field search-field"
      >
        <span>Nom ou code</span>

        <input
            v-model="cardsStore.search"
            type="search"
            placeholder="Turbo, pneus, grip…"
            autocomplete="off"
        >
      </label>

      <label class="filter-field">
        <span>Nature</span>

        <select
            v-model="cardsStore.kind"
            @change="cardsStore.changeKind"
        >
          <option value="">
            Toutes
          </option>

          <option
              v-for="
                availableKind in
                  cardsStore.options.kinds
              "
              :key="availableKind"
              :value="availableKind"
          >
            {{
              kindLabel(
                  availableKind,
              )
            }}
          </option>
        </select>
      </label>

      <label class="filter-field">
        <span>Emplacement</span>

        <select
            v-model="
              cardsStore.equipmentSlot
            "
            :disabled="
              cardsStore.kind
              !== 'equipment'
            "
        >
          <option value="">
            Tous
          </option>

          <option
              v-for="
                availableSlot in
                  cardsStore.options
                      .equipmentSlots
              "
              :key="availableSlot"
              :value="availableSlot"
          >
            {{
              equipmentSlotLabel(
                  availableSlot,
              )
            }}
          </option>
        </select>
      </label>

      <label class="filter-field">
        <span>Type</span>

        <select
            v-model="cardsStore.type"
        >
          <option value="">
            Tous
          </option>

          <option
              v-for="
                availableType in
                  cardsStore.options.types
              "
              :key="availableType"
              :value="availableType"
          >
            {{ availableType }}
          </option>
        </select>
      </label>

      <label class="filter-field">
        <span>Rareté</span>

        <select
            v-model="cardsStore.rarity"
        >
          <option value="">
            Toutes
          </option>

          <option
              v-for="
                availableRarity in
                  cardsStore.options
                      .rarities
              "
              :key="availableRarity"
              :value="availableRarity"
          >
            {{ availableRarity }}
          </option>
        </select>
      </label>

      <label class="filter-field">
        <span>Palier possédé</span>

        <select
            v-model="cardsStore.tier"
        >
          <option value="">
            Tous
          </option>

          <option
              v-for="
                availableTier in
                  cardsStore.options.tiers
              "
              :key="availableTier"
              :value="
                String(
                    availableTier,
                )
              "
          >
            Palier {{ availableTier }}
          </option>
        </select>
      </label>

      <label class="checkbox-field">
        <input
            v-model="
              cardsStore.equippedOnly
            "
            type="checkbox"
        >

        <span>
          Au moins une carte équipée
        </span>
      </label>

      <button
          type="submit"
          class="primary-button"
          :disabled="cardsStore.isLoading"
      >
        Filtrer
      </button>

      <button
          type="button"
          class="secondary-button"
          :disabled="cardsStore.isLoading"
          @click="
            cardsStore.clearFilters
          "
      >
        Effacer
      </button>
    </form>

    <div
        v-if="
          cardsStore.errorMessage
          !== null
        "
        class="alert alert-error"
    >
      <strong>Erreur</strong>

      <p>
        {{ cardsStore.errorMessage }}
      </p>

      <button
          type="button"
          @click="
            cardsStore.loadCards(1)
          "
      >
        Réessayer
      </button>
    </div>

    <div
        v-else-if="
          cardsStore.isLoading
          && cardsStore.cards.length
              === 0
        "
        class="loading-panel"
    >
      Chargement des cartes…
    </div>

    <section v-else>
      <div class="section-summary">
        <p>
          <strong>
            {{
              cardsStore.pagination
                  .totalItems
            }}
          </strong>
          carte(s)
        </p>

        <p>
          Page
          {{ cardsStore.pagination.page }}
          sur
          {{
            cardsStore.pagination
                .totalPages
          }}
        </p>
      </div>

      <div
          v-if="
            cardsStore.cards.length
            === 0
          "
          class="empty-panel"
      >
        Aucune carte ne correspond aux filtres.
      </div>

      <div
          v-else
          class="cards-grid"
      >
        <article
            v-for="
              card in cardsStore.cards
            "
            :key="card.id"
            class="card-panel"
        >
          <header class="card-header">
            <div>
              <div class="card-labels">
                <span class="card-type">
                  {{ card.type }}
                </span>

                <span class="kind-badge">
                  {{
                    kindLabel(
                        card.kind,
                    )
                  }}
                </span>

                <span
                    v-if="
                      card.kind
                        === 'equipment'
                      && card.equipmentSlot
                        !== null
                    "
                    class="slot-badge"
                >
                  {{
                    equipmentSlotLabel(
                        card.equipmentSlot,
                    )
                  }}
                </span>
              </div>

              <h2>
                <RouterLink
                    :to="{
                      name:
                        'admin-card-detail',

                      params: {
                        id: card.id,
                      },
                    }"
                    class="card-detail-link"
                >
                  {{ card.name }}
                </RouterLink>
              </h2>

              <small>
                {{ card.code }}
              </small>
            </div>

            <span class="rarity-badge">
              {{ card.rarity }}
            </span>
          </header>

          <div class="usage-summary">
            <div>
              <span>Voitures</span>

              <strong>
                {{ card.carCount }}
              </strong>
            </div>

            <div>
              <span>Équipées</span>

              <strong>
                {{ card.equippedCount }}
              </strong>
            </div>

            <div>
              <span>Palier max.</span>

              <strong>
                {{ card.maxTier }}
              </strong>
            </div>

            <div>
              <span>Niveau moyen</span>

              <strong>
                {{
                  formatAverageLevel(
                      card.averageAcquiredLevel,
                  )
                }}
              </strong>
            </div>
          </div>

          <div class="tiers-grid">
            <div
                v-for="
        availableTier in card.maxTier
      "
                :key="availableTier"
            >
    <span>
      Palier {{ availableTier }}
    </span>

              <strong>
                {{
                  card.tierCounts[
                      String(
                          availableTier,
                      )
                      ] ?? 0
                }}
              </strong>
            </div>
          </div>



          <details class="effect-details">
            <summary>
              Configuration de l’effet
            </summary>

            <pre>{{
                formatEffectConfig(
                    card.effectConfig,
                )
              }}</pre>
          </details>
        </article>
      </div>

      <footer class="pagination">
        <button
            type="button"
            :disabled="
              cardsStore.isLoading
              || cardsStore.pagination
                  .page <= 1
            "
            @click="
              cardsStore.previousPage
            "
        >
          Page précédente
        </button>

        <span>
          {{
            cardsStore.pagination.page
          }}
          /
          {{
            cardsStore.pagination
                .totalPages
          }}
        </span>

        <button
            type="button"
            :disabled="
              cardsStore.isLoading
              || cardsStore.pagination
                  .page
                >= cardsStore.pagination
                    .totalPages
            "
            @click="
              cardsStore.nextPage
            "
        >
          Page suivante
        </button>
      </footer>
    </section>
  </main>
</template>

<style scoped>
.admin-cards {
  width: min(
      1280px,
      calc(100% - 32px)
  );
  margin: 0 auto;
  padding: 32px 0 64px;
}

.admin-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 24px;
}

.admin-eyebrow {
  margin: 0 0 4px;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  opacity: 0.65;
}

.admin-header h1 {
  margin: 0;
  font-size: clamp(
      2rem,
      5vw,
      3rem
  );
}

.admin-description {
  margin: 8px 0 0;
  opacity: 0.7;
}

.refresh-button,
.primary-button,
.secondary-button,
.pagination button,
.alert button {
  min-height: 42px;
  padding: 0 15px;
  border: 1px solid
  rgba(127, 127, 127, 0.35);
  border-radius: 9px;
  background:
      rgba(127, 127, 127, 0.1);
  color: inherit;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.48;
}

.filters {
  display: grid;
  grid-template-columns:
    repeat(
      auto-fit,
      minmax(150px, 1fr)
    );
  align-items: end;
  gap: 11px;
  margin-bottom: 24px;
  padding: 18px;
  border: 1px solid
  rgba(127, 127, 127, 0.22);
  border-radius: 14px;
  background:
      rgba(127, 127, 127, 0.06);
}

.search-field {
  min-width: 230px;
}

.filter-field {
  display: grid;
  gap: 7px;
}

.filter-field span {
  font-size: 0.82rem;
  font-weight: 700;
  opacity: 0.68;
}

.filter-field input,
.filter-field select {
  width: 100%;
  min-height: 42px;
  padding: 0 12px;
  border: 1px solid
  rgba(127, 127, 127, 0.35);
  border-radius: 9px;
  background: transparent;
  color: inherit;
  font: inherit;
}

.filter-field select:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

.checkbox-field {
  display: flex;
  align-items: center;
  min-height: 42px;
  gap: 9px;
  font-size: 0.83rem;
  font-weight: 700;
}

.checkbox-field input {
  width: 17px;
  height: 17px;
}

.section-summary {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 12px;
}

.section-summary p {
  margin: 0;
  opacity: 0.7;
}

.cards-grid {
  display: grid;
  grid-template-columns:
    repeat(
      auto-fit,
      minmax(310px, 1fr)
    );
  gap: 15px;
}

.card-panel {
  min-width: 0;
  padding: 19px;
  border: 1px solid
  rgba(127, 127, 127, 0.22);
  border-radius: 14px;
  background:
      rgba(127, 127, 127, 0.05);
}

.card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 15px;
}

.card-header h2 {
  margin: 4px 0;
  font-size: 1.18rem;
}

.card-header small {
  opacity: 0.6;
}

.card-labels {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
}

.card-type {
  font-size: 0.73rem;
  font-weight: 750;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  opacity: 0.62;
}

.kind-badge,
.slot-badge,
.rarity-badge {
  display: inline-flex;
  padding: 5px 9px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 750;
}

.kind-badge {
  background:
      rgba(40, 120, 210, 0.14);
}

.slot-badge {
  background:
      rgba(180, 120, 40, 0.14);
}

.rarity-badge {
  background:
      rgba(127, 127, 127, 0.14);
}

.usage-summary,
.tiers-grid {
  display: grid;
  gap: 8px;
  margin-top: 17px;
}

.usage-summary {
  grid-template-columns:
    repeat(4, 1fr);
}

.tiers-grid {
  grid-template-columns:
    repeat(
      auto-fit,
      minmax(80px, 1fr)
    );
}

.usage-summary div,
.tiers-grid div {
  padding: 10px;
  border-radius: 9px;
  background:
      rgba(127, 127, 127, 0.08);
}

.usage-summary span,
.tiers-grid span {
  display: block;
  font-size: 0.7rem;
  opacity: 0.62;
}

.usage-summary strong,
.tiers-grid strong {
  display: block;
  margin-top: 4px;
  font-size: 1.1rem;
}


.effect-details {
  margin-top: 17px;
}

.effect-details summary {
  cursor: pointer;
  font-size: 0.84rem;
  font-weight: 750;
}

.effect-details pre {
  max-height: 300px;
  overflow: auto;
  margin-bottom: 0;
  padding: 13px;
  border-radius: 8px;
  background:
      rgba(18, 18, 18, 0.92);
  color: white;
  font-size: 0.74rem;
  line-height: 1.5;
  white-space: pre-wrap;
  word-break: break-word;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 18px;
  margin-top: 22px;
}

.loading-panel,
.empty-panel,
.alert {
  padding: 36px;
  border-radius: 14px;
  background:
      rgba(127, 127, 127, 0.08);
  text-align: center;
}

.alert-error {
  border: 1px solid
  rgba(190, 50, 50, 0.5);
  background:
      rgba(190, 50, 50, 0.1);
}

.alert p {
  margin: 8px 0 14px;
}

.card-detail-link {
  color: inherit;
  text-decoration: none;
}

.card-detail-link:hover {
  text-decoration: underline;
}

@media (max-width: 850px) {
  .usage-summary {
    grid-template-columns:
      repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .admin-cards {
    width: min(
        100% - 20px,
        1280px
    );
    padding-top: 20px;
  }

  .admin-header,
  .section-summary,
  .card-header {
    flex-direction: column;
  }

  .refresh-button {
    width: 100%;
  }

  .filters {
    grid-template-columns: 1fr;
  }

  .search-field {
    min-width: 0;
  }

  .usage-summary,
  .tiers-grid {
    grid-template-columns: 1fr;
  }
}
</style>