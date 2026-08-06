import {
    createRouter,
    createWebHistory,
    type RouteRecordRaw,
} from 'vue-router'
import LoginView from '../views/LoginView.vue'
import GarageView from '../views/GarageView.vue'
import { useAuthStore } from '../stores/auth'
import DuelView from '../views/DuelView.vue'

const routes: RouteRecordRaw[] = [
    {
        path: '/',
        redirect: '/garage',
    },
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: {
            guestOnly: true,
        },
    },
    {
        path: '/garage',
        name: 'garage',
        component: GarageView,
        meta: {
            requiresAuth: true,
        },
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/garage',
    },
    {
        path: '/duel',
        name: 'duel',
        component: DuelView,
        meta: {
            requiresAuth: true,
        },
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach(async (to) => {
    const authStore = useAuthStore()

    try {
        await authStore.bootstrap()
    } catch {
        return {
            name: 'login',
        }
    }

    if (
        to.meta.requiresAuth === true &&
        !authStore.isAuthenticated
    ) {
        return {
            name: 'login',
            query: {
                redirect: to.fullPath,
            },
        }
    }

    if (
        to.meta.guestOnly === true &&
        authStore.isAuthenticated
    ) {
        return {
            name: 'garage',
        }
    }

    return true
})

export default router