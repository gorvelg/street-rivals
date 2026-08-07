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

function kindLabel(
    kind: string,
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
    slot: string | null,
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

    default:
      return '—'
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
          <div class="card-labels">
  <span class="card-type">
    {{ cardStore.detail.card.type }}
  </span>

            <span class="kind-badge">
    {{
                kindLabel(
                    cardStore.detail.card.kind,
                )
              }}
  </span>

            <span
                v-if="
        cardStore.detail.card.kind
        === 'equipment'
      "
                class="slot-badge"
            >
    {{
                equipmentSlotLabel(
                    cardStore.detail.card
                        .equipmentSlot,
                )
              }}
  </span>
          </div>

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

        <article
            v-for="
      availableTier in
        cardStore.detail.card.maxTier
    "
            :key="
      `tier-${availableTier}`
    "
        >
  <span>
    Palier {{ availableTier }}
  </span>

          <strong>
            {{
              cardStore.detail.card
                  .tierCounts[
                  String(
                      availableTier,
                  )
                  ]
              ?? 0
            }}
          </strong>
        </article>

        <article>
          <span>Palier maximal</span>

          <strong>
            {{ cardStore.detail.card.maxTier }}
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

      <section class="admin-section edit-section">
        <div class="section-heading">
          <div>
            <h2>Modifier la carte</h2>

            <p>
              Le code interne reste volontairement
              non modifiable.
            </p>
          </div>
        </div>

        <div
            v-if="
      cardStore.validationMessage !== null
    "
            class="edit-message edit-message-error"
        >
          {{ cardStore.validationMessage }}
        </div>

        <div
            v-if="
      cardStore.saveErrorMessage !== null
    "
            class="edit-message edit-message-error"
        >
          {{ cardStore.saveErrorMessage }}
        </div>

        <div
            v-if="
      cardStore.successMessage !== null
    "
            class="edit-message edit-message-success"
        >
          <strong>
            {{ cardStore.successMessage }}
          </strong>

          <span
              v-if="
        cardStore.lastChangedFields.length > 0
      "
          >
      Champs modifiés :
      {{
              cardStore.lastChangedFields.join(', ')
            }}
    </span>
        </div>

        <form
            class="edit-form"
            @submit.prevent="
      cardStore.saveCard(
        cardStore.detail.card.id,
      )
    "
        >
          <div class="edit-fields-grid">
            <label class="edit-field">
              <span>Code interne</span>

              <input
                  :value="cardStore.detail.card.code"
                  type="text"
                  readonly
                  disabled
              >

              <small>
                Le code est utilisé par le moteur et
                les fixtures.
              </small>
            </label>

            <label class="edit-field">
              <span>Nom</span>

              <input
                  v-model="cardStore.editName"
                  type="text"
                  maxlength="120"
                  autocomplete="off"
                  :disabled="cardStore.isSaving"
                  @input="cardStore.clearEditMessages"
              >
            </label>

            <label class="edit-field">
              <span>Type</span>

              <input
                  v-model="cardStore.editType"
                  type="text"
                  maxlength="60"
                  autocomplete="off"
                  :disabled="cardStore.isSaving"
                  @input="cardStore.clearEditMessages"
              >
            </label>

            <label class="edit-field">
              <span>Rareté</span>

              <input
                  v-model="cardStore.editRarity"
                  type="text"
                  maxlength="60"
                  autocomplete="off"
                  :disabled="cardStore.isSaving"
                  @input="cardStore.clearEditMessages"
              >
            </label>
          </div>

          <label class="edit-field">
            <span>Nature</span>

            <select
                v-model="cardStore.editKind"
                :disabled="cardStore.isSaving"
                @change="cardStore.changeKind"
            >
              <option value="ability">
                Capacité
              </option>

              <option value="equipment">
                Équipement
              </option>

              <option value="stat_boost">
                Bonus permanent
              </option>
            </select>

            <small>
              Définit le comportement général
              de la carte.
            </small>
          </label>

          <label
              v-if="
      cardStore.editKind
      === 'equipment'
    "
              class="edit-field"
          >
            <span>Emplacement</span>

            <select
                v-model="
        cardStore.editEquipmentSlot
      "
                required
                :disabled="cardStore.isSaving"
                @change="
        cardStore.clearEditMessages
      "
            >
              <option value="">
                Sélectionner…
              </option>

              <option value="engine">
                Moteur
              </option>

              <option value="wheels">
                Roues
              </option>

              <option value="brakes">
                Freins
              </option>

              <option value="gearbox">
                Boîte de vitesses
              </option>

              <option value="chassis">
                Châssis
              </option>

              <option value="aero">
                Aérodynamique
              </option>
            </select>

            <small>
              Une voiture ne peut équiper
              qu'un objet par emplacement.
            </small>
          </label>

          <label class="edit-field">
            <span>Palier maximal</span>

            <input
                v-model.number="
        cardStore.editMaxTier
      "
                type="number"
                min="1"
                max="10"
                step="1"
                :disabled="cardStore.isSaving"
                @input="
        cardStore.clearEditMessages
      "
            >

            <small>
              Valeur comprise entre 1 et 10.
            </small>
          </label>

          <label class="edit-field effect-config-field">
            <span>Configuration des effets</span>

            <textarea
                v-model="
          cardStore.editEffectConfigText
        "
                rows="18"
                spellcheck="false"
                :disabled="cardStore.isSaving"
                @input="cardStore.clearEditMessages"
            />

            <small
                v-if="
      cardStore.editKind === 'ability'
    "
            >
              Configuration historique de la capacité.
            </small>

            <small v-else>
              Les équipements et bonus permanents
              utilisent une propriété "tiers" contenant
              les bonus ou malus de statistiques.
            </small>
          </label>

          <details class="json-preview">
            <summary>
              Aperçu du JSON actuellement enregistré
            </summary>

            <pre>{{
                formatJson(
                    cardStore.detail.card.effectConfig,
                )
              }}</pre>
          </details>

          <footer class="edit-actions">
            <button
                type="button"
                class="secondary-action"
                :disabled="
          cardStore.isSaving
          || !cardStore.isDirty
        "
                @click="cardStore.resetEditForm"
            >
              Annuler les modifications
            </button>

            <button
                type="submit"
                class="save-action"
                :disabled="
          cardStore.isSaving
          || !cardStore.isDirty
        "
            >
              {{
                cardStore.isSaving
                    ? 'Enregistrement…'
                    : 'Enregistrer la carte'
              }}
            </button>
          </footer>
        </form>
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

              <option
                  v-for="
        availableTier in
          cardStore.detail.card.maxTier
      "
                  :key="availableTier"
                  :value="String(availableTier)"
              >
                Palier {{ availableTier }}
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
  .edit-fields-grid {
    grid-template-columns: 1fr;
  }

  .edit-actions {
    flex-direction: column-reverse;
  }

  .edit-actions button {
    width: 100%;
  }
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
.edit-section {
  padding: 22px;
  border: 1px solid rgba(127, 127, 127, 0.22);
  border-radius: 15px;
  background: rgba(127, 127, 127, 0.05);
}

.edit-form {
  display: grid;
  gap: 18px;
  margin-top: 18px;
}

.edit-fields-grid {
  display: grid;
  grid-template-columns:
    repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.edit-field {
  display: grid;
  gap: 7px;
}

.edit-field > span {
  font-size: 0.8rem;
  font-weight: 750;
  opacity: 0.72;
}

.edit-field input,
.edit-field select,
.edit-field textarea {
  width: 100%;
  padding: 11px 12px;
  border: 1px solid rgba(127, 127, 127, 0.35);
  border-radius: 9px;
  background: transparent;
  color: inherit;
  font: inherit;
  box-sizing: border-box;
}

.edit-field input,
.edit-field select {
  min-height: 42px;
}

.edit-field input:disabled {
  opacity: 0.62;
  cursor: not-allowed;
}

.edit-field textarea {
  min-height: 330px;
  resize: vertical;
  font-family:
      "SFMono-Regular",
      Consolas,
      "Liberation Mono",
      monospace;
  font-size: 0.8rem;
  line-height: 1.5;
  tab-size: 2;
}

.edit-field small {
  opacity: 0.62;
}

.effect-config-field {
  margin-top: 2px;
}

.edit-message {
  display: grid;
  gap: 4px;
  margin-top: 15px;
  padding: 13px 14px;
  border-radius: 9px;
}

.edit-message-error {
  border: 1px solid rgba(190, 50, 50, 0.45);
  background: rgba(190, 50, 50, 0.1);
}

.edit-message-success {
  border: 1px solid rgba(40, 160, 90, 0.4);
  background: rgba(40, 160, 90, 0.1);
}

.json-preview {
  padding: 14px;
  border: 1px solid rgba(127, 127, 127, 0.18);
  border-radius: 10px;
}

.json-preview summary {
  cursor: pointer;
  font-weight: 750;
}

.json-preview pre {
  max-height: 400px;
  overflow: auto;
  margin: 13px 0 0;
  padding: 14px;
  border-radius: 9px;
  background: rgba(18, 18, 18, 0.92);
  color: white;
  font-size: 0.76rem;
  line-height: 1.5;
  white-space: pre-wrap;
  word-break: break-word;
}

.edit-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.edit-actions button {
  min-height: 42px;
  padding: 0 16px;
  border: 1px solid rgba(127, 127, 127, 0.35);
  border-radius: 9px;
  color: inherit;
  font: inherit;
  font-weight: 750;
  cursor: pointer;
}

.edit-actions button:disabled {
  cursor: not-allowed;
  opacity: 0.48;
}

.secondary-action {
  background: rgba(127, 127, 127, 0.08);
}

.save-action {
  border-color: rgba(40, 120, 210, 0.5)
  !important;
  background: rgba(40, 120, 210, 0.16);
}
.card-labels {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 7px;
  margin-bottom: 6px;
}

.kind-badge,
.slot-badge {
  display: inline-flex;
  padding: 5px 8px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 800;
}

.kind-badge {
  background: rgba(40, 120, 210, 0.14);
}

.slot-badge {
  background: rgba(180, 120, 40, 0.14);
}
</style>