<script setup lang="ts">
import {
  computed,
  getCurrentInstance,
} from 'vue'

import {
  CAR_BODY_DEFINITIONS,
} from '../config/carBodies'

import type {
  CarBodyStyle,
  CarWheelStyle,
} from '../types/api'

const props = withDefaults(
    defineProps<{
      color: string
      bodyStyle?: CarBodyStyle
      wheelStyle?: CarWheelStyle
      pilotName?: string
    }>(),
    {
      bodyStyle: 'coupe_01',
      wheelStyle: 'street_01',
      pilotName: '',
    },
)

const body =
    computed(
        () =>
            CAR_BODY_DEFINITIONS[
                props.bodyStyle
                ],
    )

/*
 * IDs uniques afin que plusieurs CarVisual
 * puissent être présents sur la même page.
 */
const instance =
    getCurrentInstance()

const uniqueId =
    `car-${instance?.uid ?? 0}`

const bodyPaintId =
    `${uniqueId}-body-paint`

const windowGlassId =
    `${uniqueId}-window-glass`

const headlightGradientId =
    `${uniqueId}-headlight`

const bodyHighlightId =
    `${uniqueId}-highlight`

const shadowBlurId =
    `${uniqueId}-shadow`

const bodyPaintUrl =
    computed(
        () =>
            `url(#${bodyPaintId})`,
    )

const windowGlassUrl =
    computed(
        () =>
            `url(#${windowGlassId})`,
    )

const headlightGradientUrl =
    computed(
        () =>
            `url(#${headlightGradientId})`,
    )

const bodyHighlightUrl =
    computed(
        () =>
            `url(#${bodyHighlightId})`,
    )

const shadowBlurUrl =
    computed(
        () =>
            `url(#${shadowBlurId})`,
    )

const safeColor =
    computed(() => {
      const value =
          props.color.trim()

      if (
          /^#[0-9A-Fa-f]{6}$/.test(
              value,
          )
      ) {
        return value.toUpperCase()
      }

      return '#D9343A'
    })

const darkerColor =
    computed(
        () =>
            adjustColor(
                safeColor.value,
                -42,
            ),
    )

const lighterColor =
    computed(
        () =>
            adjustColor(
                safeColor.value,
                35,
            ),
    )

const veryLightColor =
    computed(
        () =>
            adjustColor(
                safeColor.value,
                65,
            ),
    )

const wheelPositions =
    computed(
        () => [
          body.value.rearWheelX,
          body.value.frontWheelX,
        ],
    )

function wheelArchPath(
    x: number,
): string {
  const y =
      body.value.wheelY

  return `
    M ${x - 61} ${y + 5}
    C ${x - 57} ${y - 32},
      ${x - 32} ${y - 48},
      ${x} ${y - 48}
    C ${x + 34} ${y - 48},
      ${x + 59} ${y - 27},
      ${x + 62} ${y + 6}
  `
}

function adjustColor(
    color: string,
    amount: number,
): string {
  const value =
      color.replace('#', '')

  const red =
      clampColor(
          Number.parseInt(
              value.substring(0, 2),
              16,
          ) + amount,
      )

  const green =
      clampColor(
          Number.parseInt(
              value.substring(2, 4),
              16,
          ) + amount,
      )

  const blue =
      clampColor(
          Number.parseInt(
              value.substring(4, 6),
              16,
          ) + amount,
      )

  return (
      '#'
      + red
          .toString(16)
          .padStart(2, '0')
      + green
          .toString(16)
          .padStart(2, '0')
      + blue
          .toString(16)
          .padStart(2, '0')
  ).toUpperCase()
}

function clampColor(
    value: number,
): number {
  return Math.min(
      255,
      Math.max(
          0,
          value,
      ),
  )
}
</script>

<template>
  <div class="car-visual">
    <svg
        class="car-svg"
        viewBox="0 0 640 260"
        role="img"
        :aria-label="
          pilotName !== ''
            ? `Voiture de ${pilotName}`
            : 'Voiture'
        "
    >
      <defs>
        <linearGradient
            :id="bodyPaintId"
            x1="0%"
            y1="0%"
            x2="0%"
            y2="100%"
        >
          <stop
              offset="0%"
              :stop-color="
                veryLightColor
              "
          />

          <stop
              offset="25%"
              :stop-color="
                lighterColor
              "
          />

          <stop
              offset="52%"
              :stop-color="
                safeColor
              "
          />

          <stop
              offset="100%"
              :stop-color="
                darkerColor
              "
          />
        </linearGradient>

        <linearGradient
            :id="windowGlassId"
            x1="0%"
            y1="0%"
            x2="0%"
            y2="100%"
        >
          <stop
              offset="0%"
              stop-color="#93B4C5"
          />

          <stop
              offset="38%"
              stop-color="#405A68"
          />

          <stop
              offset="100%"
              stop-color="#111820"
          />
        </linearGradient>

        <linearGradient
            :id="
              headlightGradientId
            "
            x1="0%"
            y1="0%"
            x2="100%"
            y2="0%"
        >
          <stop
              offset="0%"
              stop-color="#FFFFFF"
          />

          <stop
              offset="100%"
              stop-color="#B9D8E8"
          />
        </linearGradient>

        <linearGradient
            :id="bodyHighlightId"
            x1="0%"
            y1="0%"
            x2="100%"
            y2="0%"
        >
          <stop
              offset="0%"
              stop-color="#FFFFFF"
              stop-opacity="0"
          />

          <stop
              offset="45%"
              stop-color="#FFFFFF"
              stop-opacity="0.42"
          />

          <stop
              offset="100%"
              stop-color="#FFFFFF"
              stop-opacity="0"
          />
        </linearGradient>

        <filter
            :id="shadowBlurId"
            x="-20%"
            y="-100%"
            width="140%"
            height="300%"
        >
          <feGaussianBlur
              stdDeviation="7"
          />
        </filter>
      </defs>

      <!-- OMBRE -->

      <ellipse
          cx="321"
          cy="222"
          rx="245"
          ry="15"
          fill="#000000"
          opacity="0.25"
          :filter="shadowBlurUrl"
      />

      <!-- ===================================
           CARROSSERIE
      ==================================== -->

      <path
          :d="body.bodyPath"
          :fill="bodyPaintUrl"
          stroke="#15191D"
          stroke-width="4"
          stroke-linejoin="round"
      />

      <!-- ===================================
           VITRES
      ==================================== -->

      <path
          v-if="
            body.rearWindowPath
          "
          :d="
            body.rearWindowPath
          "
          :fill="
            windowGlassUrl
          "
          stroke="#171C21"
          stroke-width="4"
      />

      <path
          :d="
            body.frontWindowPath
          "
          :fill="
            windowGlassUrl
          "
          stroke="#171C21"
          stroke-width="4"
      />

      <!-- MONTANT -->

      <path
          v-if="
            body.pillarPath
          "
          :d="
            body.pillarPath
          "
          fill="none"
          stroke="#11161B"
          stroke-width="7"
          stroke-linecap="round"
      />

      <!-- REFLET VITRE -->

      <path
          :d="
            body.frontWindowPath
          "
          fill="none"
          stroke="#FFFFFF"
          stroke-width="2"
          opacity="0.12"
      />

      <!-- ===================================
           CARROSSERIE : DÉTAILS
      ==================================== -->

      <path
          v-if="
            body.doorLinePath
          "
          :d="
            body.doorLinePath
          "
          fill="none"
          stroke="#14191D"
          stroke-width="2"
          opacity="0.62"
      />

      <path
          :d="
            body.highlightPath
          "
          fill="none"
          :stroke="
            bodyHighlightUrl
          "
          stroke-width="5"
          stroke-linecap="round"
          opacity="0.58"
      />

      <!-- BAS DE CAISSE -->

      <path
          :d="`
            M ${body.rearWheelX + 58} ${body.wheelY - 1}
            L ${body.frontWheelX - 60} ${body.wheelY - 1}
            L ${body.frontWheelX - 64} ${body.wheelY + 11}
            L ${body.rearWheelX + 62} ${body.wheelY + 11}
            Z
          `"
          fill="#15191D"
          opacity="0.82"
      />

      <!-- PHARE AVANT -->

      <path
          :d="
            body.frontLightPath
          "
          :fill="
            headlightGradientUrl
          "
          stroke="#1C242B"
          stroke-width="2"
      />

      <!-- FEU ARRIÈRE -->

      <path
          :d="
            body.rearLightPath
          "
          fill="#D72D34"
          stroke="#541217"
          stroke-width="2"
      />

      <!-- POIGNÉE -->

      <rect
          :x="
            (
              body.rearWheelX
              + body.frontWheelX
            ) / 2 + 25
          "
          y="143"
          width="23"
          height="4"
          rx="2"
          fill="#1B2024"
          opacity="0.75"
      />

      <!-- ===================================
           ROUES
      ==================================== -->

      <g
          v-for="
            wheelX in
              wheelPositions
          "
          :key="wheelX"
          :transform="
            `translate(
              ${wheelX}
              ${body.wheelY}
            )`
          "
      >
        <!-- PNEU -->

        <circle
            r="42"
            fill="#0F1113"
        />

        <circle
            r="35"
            fill="#24282C"
        />

        <!-- BORD DE JANTE -->

        <circle
            r="28"
            fill="#AEB4B8"
        />

        <!-- STREET -->

        <g
            v-if="
              wheelStyle
              === 'street_01'
            "
        >
          <circle
              r="21"
              fill="#43494E"
          />

          <circle
              r="8"
              fill="#16191C"
          />

          <circle
              r="4"
              fill="#C9CCCE"
          />

          <g
              stroke="#C3C7CA"
              stroke-width="6"
              stroke-linecap="round"
          >
            <line
                x1="0"
                y1="-20"
                x2="0"
                y2="-8"
            />

            <line
                x1="19"
                y1="-6"
                x2="7"
                y2="-3"
            />

            <line
                x1="12"
                y1="16"
                x2="5"
                y2="6"
            />

            <line
                x1="-12"
                y1="16"
                x2="-5"
                y2="6"
            />

            <line
                x1="-19"
                y1="-6"
                x2="-7"
                y2="-3"
            />
          </g>
        </g>

        <!-- FIVE SPOKE -->

        <g
            v-else-if="
              wheelStyle
              === 'five_spoke'
            "
        >
          <circle
              r="7"
              fill="#181B1E"
          />

          <g fill="#D9DCDE">
            <path
                v-for="
                  angle in [
                    0,
                    72,
                    144,
                    216,
                    288,
                  ]
                "
                :key="angle"
                d="
                  M -4 -6
                  L -8 -27
                  L 8 -27
                  L 4 -6
                  Z
                "
                :transform="
                  `rotate(${angle})`
                "
            />
          </g>

          <circle
              r="5"
              fill="#393E42"
          />
        </g>

        <!-- MULTI SPOKE -->

        <g v-else>
          <g
              stroke="#D4D7D9"
              stroke-width="3"
          >
            <line
                v-for="
                  angle in 12
                "
                :key="angle"
                x1="0"
                y1="-6"
                x2="0"
                y2="-26"
                :transform="
                  `rotate(
                    ${angle * 30}
                  )`
                "
            />
          </g>

          <circle
              r="7"
              fill="#34393D"
          />

          <circle
              r="3"
              fill="#D5D8DA"
          />
        </g>
      </g>

      <!-- ===================================
           PASSAGES DE ROUE
      ==================================== -->

      <path
          :d="
            wheelArchPath(
              body.rearWheelX,
            )
          "
          fill="none"
          stroke="#13171A"
          stroke-width="5"
      />

      <path
          :d="
            wheelArchPath(
              body.frontWheelX,
            )
          "
          fill="none"
          stroke="#13171A"
          stroke-width="5"
      />
    </svg>
  </div>
</template>

<style scoped>
.car-visual {
  position: relative;

  width: 100%;

  user-select: none;
}

.car-svg {
  display: block;

  width: 100%;
  height: auto;

  overflow: visible;
}
</style>