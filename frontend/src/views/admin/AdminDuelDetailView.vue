<script setup lang="ts">
import {
  computed,
  onUnmounted,
  ref,
  watch,
} from 'vue'
import {
  RouterLink,
  useRoute,
} from 'vue-router'
import AdminNavigation
  from '../../components/admin/AdminNavigation.vue'
import { useAdminDuelDetailStore }
  from '../../stores/adminDuelDetail'
import type {
  AdminJsonValue,
} from '../../types/admin'

const route = useRoute()
const detailStore = useAdminDuelDetailStore()

const copiedSection = ref<string | null>(null)

const duelId = computed<number | null>(() => {
  const parsedId = Number.parseInt(
      String(route.params.id),
      10,
  )

  return Number.isInteger(parsedId)
  && parsedId > 0
      ? parsedId
      : null
})

function formatDate(value: string): string {
  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'short',
    timeStyle: 'medium',
  }).format(date)
}

function formatDelta(value: number): string {
  return value > 0
      ? `+${value}`
      : String(value)
}

function formatJson(
    value: AdminJsonValue,
): string {
  try {
    return JSON.stringify(value, null, 2)
  } catch {
    return 'Données JSON indisponibles'
  }
}

async function copyJson(
    section: string,
    value: AdminJsonValue,
): Promise<void> {
  try {
    await navigator.clipboard.writeText(
        formatJson(value),
    )

    copiedSection.value = section

    window.setTimeout(() => {
      if (copiedSection.value === section) {
        copiedSection.value = null
      }
    }, 2000)
  } catch {
    copiedSection.value = null
  }
}

watch(
    duelId,
    async (newDuelId): Promise<void> => {
      detailStore.reset()

      if (newDuelId === null) {
        return
      }

      await detailStore.loadDuel(newDuelId)
    },
    {
      immediate: true,
    },
)

onUnmounted((): void => {
  detailStore.reset()
})
</script>

<template>
  <main class="admin-duel-detail">
    <header class="admin-header">
      <div>
        <p class="admin-eyebrow">
          Street Rivals
        </p>

        <h1>Détail du duel</h1>

        <p class="admin-description">
          Simulation, récompenses et replay complet.
        </p>
      </div>

      <RouterLink
          :to="{ name: 'admin-duels' }"
          class="back-link"
      >
        Retour aux duels
      </RouterLink>
    </header>

    <AdminNavigation />

    <div
        v-if="duelId === null"
        class="alert alert-error"
    >
      L’identifiant du duel est invalide.
    </div>

    <div
        v-else-if="detailStore.errorMessage !== null"
        class="alert alert-error"
    >
      <strong>Erreur</strong>

      <p>{{ detailStore.errorMessage }}</p>

      <button
          type="button"
          @click="detailStore.loadDuel(duelId)"
      >
        Réessayer
      </button>
    </div>

    <div
        v-else-if="
        detailStore.isLoading
        && detailStore.detail === null
      "
        class="loading-panel"
    >
      Chargement du duel…
    </div>

    <template v-else-if="detailStore.detail !== null">
      <section class="duel-summary">
        <div>
          <p class="duel-id">
            Duel #{{ detailStore.detail.duel.id }}
          </p>

          <h2>
            {{
              detailStore.detail.attacker.pilotName
            }}
            contre
            {{
              detailStore.detail.defender.pilotName
            }}
          </h2>

          <p>
            {{
              formatDate(
                  detailStore.detail.duel.createdAt,
              )
            }}
          </p>
        </div>

        <div class="duel-metadata">
          <span>
            Moteur
            {{ detailStore.detail.duel.engineVersion }}
          </span>

          <span>
            Seed
            {{ detailStore.detail.duel.randomSeed }}
          </span>

          <span>
            Écart
            {{ detailStore.detail.duel.finalGap }}
          </span>
        </div>
      </section>

      <section class="fighters-grid">
        <article
            class="fighter-card"
            :class="{
            winner:
              detailStore.detail.duel.winnerSide
                === 'attacker',
          }"
        >
          <header>
            <span
                class="car-color"
                :style="{
                backgroundColor:
                  detailStore.detail.attacker.color,
              }"
            />

            <div>
              <span class="side-label">
                Attaquant
              </span>

              <RouterLink
                  :to="{
                  name: 'admin-car-detail',
                  params: {
                    id:
                      detailStore.detail.attacker.id,
                  },
                }"
                  class="fighter-name"
              >
                {{
                  detailStore.detail.attacker
                      .pilotName
                }}
              </RouterLink>
            </div>
          </header>

          <dl>
            <div>
              <dt>Niveau</dt>
              <dd>
                {{ detailStore.detail.attacker.level }}
              </dd>
            </div>

            <div>
              <dt>Classement</dt>
              <dd>
                {{ detailStore.detail.attacker.rating }}
              </dd>
            </div>

            <div>
              <dt>V / D</dt>
              <dd>
                {{ detailStore.detail.attacker.wins }}
                /
                {{ detailStore.detail.attacker.losses }}
              </dd>
            </div>
          </dl>

          <RouterLink
              v-if="
              detailStore.detail.attacker.owner.id
                !== null
            "
              :to="{
              name: 'admin-user-detail',
              params: {
                id:
                  detailStore.detail.attacker.owner.id,
              },
            }"
              class="owner-link"
          >
            {{
              detailStore.detail.attacker.owner.email
              ?? 'Compte inconnu'
            }}
          </RouterLink>
        </article>

        <div class="versus">
          VS
        </div>

        <article
            class="fighter-card"
            :class="{
            winner:
              detailStore.detail.duel.winnerSide
                === 'defender',
          }"
        >
          <header>
            <span
                class="car-color"
                :style="{
                backgroundColor:
                  detailStore.detail.defender.color,
              }"
            />

            <div>
              <span class="side-label">
                Défenseur
              </span>

              <RouterLink
                  :to="{
                  name: 'admin-car-detail',
                  params: {
                    id:
                      detailStore.detail.defender.id,
                  },
                }"
                  class="fighter-name"
              >
                {{
                  detailStore.detail.defender
                      .pilotName
                }}
              </RouterLink>
            </div>
          </header>

          <dl>
            <div>
              <dt>Niveau</dt>
              <dd>
                {{ detailStore.detail.defender.level }}
              </dd>
            </div>

            <div>
              <dt>Classement</dt>
              <dd>
                {{ detailStore.detail.defender.rating }}
              </dd>
            </div>

            <div>
              <dt>V / D</dt>
              <dd>
                {{ detailStore.detail.defender.wins }}
                /
                {{ detailStore.detail.defender.losses }}
              </dd>
            </div>
          </dl>

          <RouterLink
              v-if="
              detailStore.detail.defender.owner.id
                !== null
            "
              :to="{
              name: 'admin-user-detail',
              params: {
                id:
                  detailStore.detail.defender.owner.id,
              },
            }"
              class="owner-link"
          >
            {{
              detailStore.detail.defender.owner.email
              ?? 'Compte inconnu'
            }}
          </RouterLink>
        </article>
      </section>

      <section class="admin-section">
        <h2>Récompenses et classement</h2>

        <div class="table-container">
          <table class="result-table">
            <thead>
            <tr>
              <th>Voiture</th>
              <th>XP</th>
              <th>Argent</th>
              <th>Elo avant</th>
              <th>Elo après</th>
              <th>Variation</th>
            </tr>
            </thead>

            <tbody>
            <tr>
              <td>
                {{
                  detailStore.detail.attacker
                      .pilotName
                }}
              </td>

              <td>
                +{{
                  detailStore.detail.rewards
                      .attacker.xp
                }}
              </td>

              <td>
                +{{
                  detailStore.detail.rewards
                      .attacker.money
                }}
              </td>

              <td>
                {{
                  detailStore.detail.rating
                      .attacker.before
                }}
              </td>

              <td>
                {{
                  detailStore.detail.rating
                      .attacker.after
                }}
              </td>

              <td>
                {{
                  formatDelta(
                      detailStore.detail.rating
                          .attacker.delta,
                  )
                }}
              </td>
            </tr>

            <tr>
              <td>
                {{
                  detailStore.detail.defender
                      .pilotName
                }}
              </td>

              <td>
                +{{
                  detailStore.detail.rewards
                      .defender.xp
                }}
              </td>

              <td>
                +{{
                  detailStore.detail.rewards
                      .defender.money
                }}
              </td>

              <td>
                {{
                  detailStore.detail.rating
                      .defender.before
                }}
              </td>

              <td>
                {{
                  detailStore.detail.rating
                      .defender.after
                }}
              </td>

              <td>
                {{
                  formatDelta(
                      detailStore.detail.rating
                          .defender.delta,
                  )
                }}
              </td>
            </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="admin-section json-grid">
        <article class="json-panel">
          <header>
            <h2>Snapshot attaquant</h2>

            <button
                type="button"
                @click="
                copyJson(
                  'attacker',
                  detailStore.detail.snapshots
                    .attacker,
                )
              "
            >
              {{
                copiedSection === 'attacker'
                    ? 'Copié'
                    : 'Copier'
              }}
            </button>
          </header>

          <pre>{{
              formatJson(
                  detailStore.detail.snapshots.attacker,
              )
            }}</pre>
        </article>

        <article class="json-panel">
          <header>
            <h2>Snapshot défenseur</h2>

            <button
                type="button"
                @click="
                copyJson(
                  'defender',
                  detailStore.detail.snapshots
                    .defender,
                )
              "
            >
              {{
                copiedSection === 'defender'
                    ? 'Copié'
                    : 'Copier'
              }}
            </button>
          </header>

          <pre>{{
              formatJson(
                  detailStore.detail.snapshots.defender,
              )
            }}</pre>
        </article>
      </section>

      <section class="admin-section json-panel">
        <header>
          <div>
            <h2>Protection anti-farming</h2>

            <p>
              Multiplicateurs et compteurs appliqués
              lors du duel.
            </p>
          </div>

          <button
              type="button"
              @click="
              copyJson(
                'anti-farming',
                detailStore.detail.antiFarming,
              )
            "
          >
            {{
              copiedSection === 'anti-farming'
                  ? 'Copié'
                  : 'Copier'
            }}
          </button>
        </header>

        <pre>{{
            formatJson(detailStore.detail.antiFarming)
          }}</pre>
      </section>

      <section class="admin-section json-panel replay-panel">
        <header>
          <div>
            <h2>Replay JSON complet</h2>

            <p>
              Données brutes enregistrées au moment
              de la simulation.
            </p>
          </div>

          <button
              type="button"
              @click="
              copyJson(
                'replay',
                detailStore.detail.replayData,
              )
            "
          >
            {{
              copiedSection === 'replay'
                  ? 'Copié'
                  : 'Copier le JSON'
            }}
          </button>
        </header>

        <pre>{{
            formatJson(detailStore.detail.replayData)
          }}</pre>
      </section>
    </template>
  </main>
</template>

<style scoped>
.admin-duel-detail {
  width: min(1180px, calc(100% - 32px));
  margin: 0 auto;
  padding: 32px 0 64px;
}

.admin-header,
.duel-summary,
.json-panel header {
  display: flex;
  justify-content: space-between;
  gap: 24px;
}

.admin-header {
  align-items: flex-start;
  margin-bottom: 24px;
}

.admin-eyebrow,
.duel-id {
  margin: 0 0 5px;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  opacity: 0.65;
}

.admin-header h1,
.duel-summary h2,
.admin-section h2 {
  margin: 0;
}

.admin-description,
.duel-summary p,
.json-panel p {
  margin: 7px 0 0;
  opacity: 0.68;
}

.back-link,
.alert button,
.json-panel button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 40px;
  padding: 0 14px;
  border: 1px solid rgba(127, 127, 127, 0.35);
  border-radius: 9px;
  background: rgba(127, 127, 127, 0.08);
  color: inherit;
  font: inherit;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
}

.duel-summary {
  align-items: center;
  padding: 22px;
  border: 1px solid rgba(127, 127, 127, 0.22);
  border-radius: 15px;
  background: rgba(127, 127, 127, 0.06);
}

.duel-metadata {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.duel-metadata span {
  padding: 6px 9px;
  border-radius: 8px;
  background: rgba(127, 127, 127, 0.12);
  font-size: 0.78rem;
  font-weight: 700;
}

.fighters-grid {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: stretch;
  gap: 14px;
  margin-top: 20px;
}

.fighter-card {
  padding: 20px;
  border: 1px solid rgba(127, 127, 127, 0.22);
  border-radius: 14px;
  background: rgba(127, 127, 127, 0.05);
}

.fighter-card.winner {
  border-color: rgba(40, 160, 90, 0.48);
  background: rgba(40, 160, 90, 0.08);
}

.fighter-card header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.car-color {
  width: 38px;
  height: 38px;
  border: 2px solid rgba(127, 127, 127, 0.3);
  border-radius: 50%;
}

.side-label {
  display: block;
  font-size: 0.72rem;
  font-weight: 750;
  text-transform: uppercase;
  opacity: 0.62;
}

.fighter-name,
.owner-link {
  color: inherit;
  font-weight: 800;
  text-decoration: none;
}

.fighter-name:hover,
.owner-link:hover {
  text-decoration: underline;
}

.fighter-card dl {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 9px;
  margin: 18px 0;
}

.fighter-card dl div {
  padding: 9px;
  border-radius: 8px;
  background: rgba(127, 127, 127, 0.08);
}

.fighter-card dt {
  font-size: 0.7rem;
  opacity: 0.62;
}

.fighter-card dd {
  margin: 3px 0 0;
  font-weight: 750;
}

.versus {
  display: flex;
  align-items: center;
  font-weight: 900;
  opacity: 0.5;
}

.admin-section {
  margin-top: 34px;
}

.table-container {
  margin-top: 14px;
  overflow-x: auto;
  border: 1px solid rgba(127, 127, 127, 0.2);
  border-radius: 13px;
}

.result-table {
  width: 100%;
  border-collapse: collapse;
}

.result-table th,
.result-table td {
  padding: 14px;
  border-bottom: 1px solid rgba(127, 127, 127, 0.15);
  text-align: left;
  white-space: nowrap;
}

.result-table tbody tr:last-child td {
  border-bottom: 0;
}

.result-table th {
  font-size: 0.74rem;
  text-transform: uppercase;
  opacity: 0.62;
}

.json-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 14px;
}

.json-panel {
  min-width: 0;
  padding: 18px;
  border: 1px solid rgba(127, 127, 127, 0.21);
  border-radius: 14px;
  background: rgba(127, 127, 127, 0.05);
}

.json-panel header {
  align-items: flex-start;
  margin-bottom: 13px;
}

.json-panel pre {
  max-height: 450px;
  overflow: auto;
  margin: 0;
  padding: 15px;
  border-radius: 9px;
  background: rgba(18, 18, 18, 0.92);
  color: white;
  font-size: 0.76rem;
  line-height: 1.5;
  white-space: pre-wrap;
  word-break: break-word;
}

.replay-panel pre {
  max-height: 700px;
}

.loading-panel,
.alert {
  padding: 34px;
  border-radius: 14px;
  background: rgba(127, 127, 127, 0.08);
  text-align: center;
}

.alert p {
  margin: 7px 0 14px;
}

.alert-error {
  border: 1px solid rgba(190, 50, 50, 0.45);
  background: rgba(190, 50, 50, 0.1);
}

@media (max-width: 750px) {
  .admin-duel-detail {
    width: min(100% - 20px, 1180px);
    padding-top: 20px;
  }

  .admin-header,
  .duel-summary,
  .json-panel header {
    flex-direction: column;
  }

  .back-link,
  .json-panel button {
    width: 100%;
  }

  .duel-metadata {
    justify-content: flex-start;
  }

  .fighters-grid,
  .json-grid {
    grid-template-columns: 1fr;
  }

  .versus {
    justify-content: center;
  }
}
</style>