import {
    createRouter,
    createWebHistory,
    type RouteRecordRaw,
} from 'vue-router'
import LoginView from '../views/LoginView.vue'
import GarageView from '../views/GarageView.vue'
import { useAuthStore } from '../stores/auth'
import DuelView from '../views/DuelView.vue'

import AdminDashboardView from '../views/admin/AdminDashboardView.vue'
import AdminUsersView from '../views/admin/AdminUsersView.vue'
import AdminUserDetailView from '../views/admin/AdminUserDetailView.vue'
import AdminCarsView from '../views/admin/AdminCarsView.vue'

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
    {
        path: '/admin',
        name: 'admin-dashboard',
        component: AdminDashboardView,
        meta: {
            requiresAuth: true,
            requiresAdmin: true,
        },
    },
    {
        path: '/admin/users',
        name: 'admin-users',
        component: AdminUsersView,
        meta: {
            requiresAuth: true,
            requiresAdmin: true,
        },
    },
    {
        path: '/admin/users/:id',
        name: 'admin-user-detail',
        component: AdminUserDetailView,
        meta: {
            requiresAuth: true,
            requiresAdmin: true,
        },
    },
    {
        path: '/admin/cars',
        name: 'admin-cars',
        component: AdminCarsView,
        meta: {
            requiresAuth: true,
            requiresAdmin: true,
        },
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to) => {
    const authStore = useAuthStore()

    if (
        to.meta.requiresAuth
        && !authStore.isAuthenticated
    ) {
        return {
            name: 'login',
            query: {
                redirect: to.fullPath,
            },
        }
    }

    if (
        to.meta.requiresAdmin
        && !authStore.isAdmin
    ) {
        return {
            name: 'garage',
        }
    }

    if (
        to.name === 'login'
        && authStore.isAuthenticated
    ) {
        return {
            name: 'garage',
        }
    }

    return true
})

export default router