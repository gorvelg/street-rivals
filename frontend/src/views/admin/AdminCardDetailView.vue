<script setup lang="ts">
import {
  computed,
  onUnmounted,
  watch,
} from 'vue'
import {
  RouterLink,
  useRoute,
} from 'vue-router'
import AdminNavigation
  from '../../components/admin/AdminNavigation.vue'
import { useAdminCardDetailStore }
  from '../../stores/adminCardDetail'

const route = useRoute()

const cardStore =
    useAdminCardDetailStore()

const cardId = computed<number | null>(() => {
  const parsedId = Number.parseInt(
      String(route.params.id),
      10,
  )

  return Number.isInteger(parsedId)
  && parsedId > 0
      ? parsedId
      : null
})

function formatAverage(
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

function formatJson(
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

watch(
    cardId,
    async (
        newCardId,
    ): Promise<void> => {
      cardStore.reset()

      if (newCardId === null) {
        return
      }

      await cardStore.loadCard(
          newCardId,
      )
    },
    {
      immediate: true,
    },
)

onUnmounted((): void => {
  cardStore.reset()
})
</script>

<template>
  <main class="admin-card-detail">
    <header class="admin-header">
      <div>
        <p class="admin-eyebrow">
          Street Rivals
        </p>

        <h1>Fiche carte</h1>

        <p class="admin-description">
          Effets, paliers et voitures possédant
          cette carte.
        </p>
      </div>

      <RouterLink
          :to="{ name: 'admin-cards' }"
          class="back-link"
      >
        Retour aux cartes
      </RouterLink>
    </header>

    <AdminNavigation />

    <div
        v-if="cardId === null"
        class="alert alert-error"
    >
      L’identifiant de la carte est invalide.
    </div>

    <div
        v-else-if="
        cardStore.errorMessage !== null
      "
        class="alert alert-error"
    >
      <strong>Erreur</strong>

      <p>
        {{ cardStore.errorMessage }}
      </p>

      <button
          type="button"
          @click="cardStore.loadCard(cardId)"
      >
        Réessayer
      </button>
    </div>

    <div
        v-else-if="
        cardStore.isLoading
        && cardStore.detail === null
      "
        class="loading-panel"
    >
      Chargement de la carte…
    </div>

    <template
        v-else-if="cardStore.detail !== null"
    >
      <section class="card-summary">
        <div>
          <span class="card-type">
            {{ cardStore.detail.card.type }}
          </span>

          <h2>
            {{ cardStore.detail.card.name }}
          </h2>

          <p>
            {{ cardStore.detail.card.code }}
          </p>
        </div>

        <span class="rarity-badge">
          {{ cardStore.detail.card.rarity }}
        </span>
      </section>

      <section class="summary-grid">
        <article>
          <span>Voitures</span>

          <strong>
            {{ cardStore.detail.card.carCount }}
          </strong>
        </article>

        <article>
          <span>Équipées</span>

          <strong>
            {{
              cardStore.detail.card
                  .equippedCount
            }}
          </strong>
        </article>

        <article>
          <span>Palier 1</span>

          <strong>
            {{
              cardStore.detail.card
                  .tier1Count
            }}
          </strong>
        </article>

        <article>
          <span>Palier 2</span>

          <strong>
            {{
              cardStore.detail.card
                  .tier2Count
            }}
          </strong>
        </article>

        <article>
          <span>Palier 3</span>

          <strong>
            {{
              cardStore.detail.card
                  .tier3Count
            }}
          </strong>
        </article>

        <article>
          <span>Niveau moyen</span>

          <strong>
            {{
              formatAverage(
                  cardStore.detail.card
                      .averageAcquiredLevel,
              )
            }}
          </strong>
        </article>
      </section>

      <section class="admin-section">
        <h2>Configuration de l’effet</h2>

        <pre class="effect-config">{{
            formatJson(
                cardStore.detail.card.effectConfig,
            )
          }}</pre>
      </section>

      <section class="admin-section">
        <div class="section-heading">
          <div>
            <h2>Voitures possédant la carte</h2>

            <p>
              Recherche par pilote ou propriétaire.
            </p>
          </div>
        </div>

        <form
            class="filters"
            @submit.prevent="
            cardStore.submitFilters(
              cardStore.detail.card.id,
            )
          "
        >
          <label class="filter-field">
            <span>Pilote ou propriétaire</span>

            <input
                v-model="cardStore.search"
                type="search"
                placeholder="Nom ou e-mail"
                autocomplete="off"
            >
          </label>

          <label class="filter-field">
            <span>Palier</span>

            <select v-model="cardStore.tier">
              <option value="">
                Tous
              </option>

              <option value="1">
                Palier 1
              </option>

              <option value="2">
                Palier 2
              </option>

              <option value="3">
                Palier 3
              </option>
            </select>
          </label>

          <label class="checkbox-field">
            <input
                v-model="cardStore.equippedOnly"
                type="checkbox"
            >

            <span>Équipées uniquement</span>
          </label>

          <button
              type="submit"
              :disabled="cardStore.isLoading"
          >
            Filtrer
          </button>

          <button
              type="button"
              :disabled="cardStore.isLoading"
              @click="
              cardStore.clearFilters(
                cardStore.detail.card.id,
              )
            "
          >
            Effacer
          </button>
        </form>

        <div class="section-summary">
          <p>
            <strong>
              {{
                cardStore.detail.holders
                    .pagination.totalItems
              }}
            </strong>
            résultat(s)
          </p>

          <p>
            Page
            {{
              cardStore.detail.holders
                  .pagination.page
            }}
            sur
            {{
              cardStore.detail.holders
                  .pagination.totalPages
            }}
          </p>
        </div>

        <div
            v-if="
            cardStore.detail.holders
              .members.length === 0
          "
            class="empty-panel"
        >
          Aucune voiture ne correspond aux filtres.
        </div>

        <div
            v-else
            class="table-container"
        >
          <table>
            <thead>
            <tr>
              <th>Voiture</th>
              <th>Propriétaire</th>
              <th>Niveau</th>
              <th>Classement</th>
              <th>V / D</th>
              <th>Palier</th>
              <th>Acquisition</th>
              <th>Statut</th>
            </tr>
            </thead>

            <tbody>
            <tr
                v-for="
                  holder in
                    cardStore.detail.holders
                      .members
                "
                :key="holder.id"
            >
              <td>
                <div class="car-identity">
                    <span
                        class="car-color"
                        :style="{
                        backgroundColor:
                          holder.car.color,
                      }"
                    />

                  <RouterLink
                      :to="{
                        name: 'admin-car-detail',
                        params: {
                          id: holder.car.id,
                        },
                      }"
                  >
                    {{ holder.car.pilotName }}
                  </RouterLink>
                </div>
              </td>

              <td>
                <RouterLink
                    v-if="
                      holder.owner.id !== null
                    "
                    :to="{
                      name: 'admin-user-detail',
                      params: {
                        id: holder.owner.id,
                      },
                    }"
                >
                  {{
                    holder.owner.email
                    ?? 'Compte inconnu'
                  }}
                </RouterLink>

                <span v-else>
                    Compte inconnu
                  </span>
              </td>

              <td>
                {{ holder.car.level }}
              </td>

              <td>
                {{ holder.car.rating }}
              </td>

              <td>
                {{ holder.car.wins }}
                /
                {{ holder.car.losses }}
              </td>

              <td>
                Palier {{ holder.tier }}
              </td>

              <td>
                Niveau
                {{ holder.acquiredLevel }}
              </td>

              <td>
                  <span
                      class="status-badge"
                      :class="{
                      equipped:
                        holder.equipped,
                    }"
                  >
                    {{
                      holder.equipped
                          ? 'Équipée'
                          : 'Non équipée'
                    }}
                  </span>
              </td>
            </tr>
            </tbody>
          </table>
        </div>

        <footer class="pagination">
          <button
              type="button"
              :disabled="
              cardStore.isLoading
              || cardStore.detail.holders
                .pagination.page <= 1
            "
              @click="
              cardStore.previousPage(
                cardStore.detail.card.id,
              )
            "
          >
            Page précédente
          </button>

          <span>
            {{
              cardStore.detail.holders
                  .pagination.page
            }}
            /
            {{
              cardStore.detail.holders
                  .pagination.totalPages
            }}
          </span>

          <button
              type="button"
              :disabled="
              cardStore.isLoading
              || cardStore.detail.holders
                .pagination.page
                >= cardStore.detail.holders
                  .pagination.totalPages
            "
              @click="
              cardStore.nextPage(
                cardStore.detail.card.id,
              )
            "
          >
            Page suivante
          </button>
        </footer>
      </section>
    </template>
  </main>
</template>

<style scoped>
.admin-card-detail {
  width: min(1180px, calc(100% - 32px));
  margin: 0 auto;
  padding: 32px 0 64px;
}

.admin-header,
.card-summary,
.section-heading,
.section-summary {
  display: flex;
  justify-content: space-between;
  gap: 24px;
}

.admin-header {
  align-items: flex-start;
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

.admin-header h1,
.card-summary h2,
.admin-section h2 {
  margin: 0;
}

.admin-description,
.section-heading p {
  margin: 8px 0 0;
  opacity: 0.7;
}

.back-link,
.filters button,
.pagination button,
.alert button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42px;
  padding: 0 15px;
  border: 1px solid rgba(127, 127, 127, 0.35);
  border-radius: 9px;
  background: rgba(127, 127, 127, 0.1);
  color: inherit;
  font: inherit;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.48;
}

.card-summary {
  align-items: flex-start;
  padding: 24px;
  border: 1px solid rgba(127, 127, 127, 0.22);
  border-radius: 15px;
  background: rgba(127, 127, 127, 0.06);
}

.card-summary p {
  margin: 7px 0 0;
  opacity: 0.65;
}

.card-type {
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  opacity: 0.65;
}

.rarity-badge,
.status-badge {
  display: inline-flex;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(127, 127, 127, 0.14);
  font-size: 0.76rem;
  font-weight: 750;
}

.status-badge.equipped {
  background: rgba(40, 160, 90, 0.16);
}

.summary-grid {
  display: grid;
  grid-template-columns:
    repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
  margin-top: 18px;
}

.summary-grid article {
  padding: 17px;
  border: 1px solid rgba(127, 127, 127, 0.2);
  border-radius: 12px;
  background: rgba(127, 127, 127, 0.05);
}

.summary-grid span {
  display: block;
  font-size: 0.76rem;
  opacity: 0.65;
}

.summary-grid strong {
  display: block;
  margin-top: 7px;
  font-size: 1.6rem;
}

.admin-section {
  margin-top: 34px;
}

.effect-config {
  max-height: 550px;
  overflow: auto;
  margin-top: 14px;
  padding: 17px;
  border-radius: 10px;
  background: rgba(18, 18, 18, 0.92);
  color: white;
  font-size: 0.78rem;
  line-height: 1.5;
  white-space: pre-wrap;
  word-break: break-word;
}

.filters {
  display: grid;
  grid-template-columns:
    minmax(240px, 1fr)
    minmax(130px, 170px)
    auto
    auto
    auto;
  align-items: end;
  gap: 10px;
  margin: 17px 0;
  padding: 16px;
  border: 1px solid rgba(127, 127, 127, 0.2);
  border-radius: 13px;
  background: rgba(127, 127, 127, 0.05);
}

.filter-field {
  display: grid;
  gap: 7px;
}

.filter-field span {
  font-size: 0.78rem;
  font-weight: 700;
  opacity: 0.68;
}

.filter-field input,
.filter-field select {
  width: 100%;
  min-height: 42px;
  padding: 0 12px;
  border: 1px solid rgba(127, 127, 127, 0.35);
  border-radius: 9px;
  background: transparent;
  color: inherit;
  font: inherit;
}

.checkbox-field {
  display: flex;
  align-items: center;
  min-height: 42px;
  gap: 8px;
  font-size: 0.8rem;
  font-weight: 700;
}

.section-summary {
  margin-bottom: 12px;
}

.section-summary p {
  margin: 0;
  opacity: 0.68;
}

.table-container {
  overflow-x: auto;
  border: 1px solid rgba(127, 127, 127, 0.2);
  border-radius: 13px;
}

table {
  width: 100%;
  min-width: 950px;
  border-collapse: collapse;
}

th,
td {
  padding: 14px;
  border-bottom: 1px solid rgba(127, 127, 127, 0.15);
  text-align: left;
  white-space: nowrap;
}

tbody tr:last-child td {
  border-bottom: 0;
}

th {
  font-size: 0.73rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  opacity: 0.62;
}

td a {
  color: inherit;
  font-weight: 750;
  text-decoration: none;
}

td a:hover {
  text-decoration: underline;
}

.car-identity {
  display: flex;
  align-items: center;
  gap: 9px;
}

.car-color {
  width: 28px;
  height: 28px;
  border: 2px solid rgba(127, 127, 127, 0.3);
  border-radius: 50%;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 18px;
  margin-top: 20px;
}

.loading-panel,
.empty-panel,
.alert {
  padding: 34px;
  border-radius: 13px;
  background: rgba(127, 127, 127, 0.08);
  text-align: center;
}

.alert p {
  margin: 8px 0 14px;
}

.alert-error {
  border: 1px solid rgba(190, 50, 50, 0.45);
  background: rgba(190, 50, 50, 0.1);
}

@media (max-width: 800px) {
  .filters {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 600px) {
  .admin-card-detail {
    width: min(100% - 20px, 1180px);
    padding-top: 20px;
  }

  .admin-header,
  .card-summary,
  .section-heading,
  .section-summary {
    flex-direction: column;
  }

  .back-link {
    width: 100%;
  }

  .filters {
    grid-template-columns: 1fr;
  }
}
</style>