<template>
  <div :dir="isRtl ? 'rtl' : 'ltr'" :class="{'dark': isDark}" class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans transition-colors duration-200">
    <!-- Top Navigation Header -->
    <header class="h-16 border-b border-slate-800/80 bg-slate-900/70 backdrop-blur-md px-6 flex items-center justify-between sticky top-0 z-50">
      <div class="flex items-center gap-4">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-950/50">
            <span class="text-white font-extrabold text-lg">M</span>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-extrabold text-base tracking-tight bg-gradient-to-r from-white via-slate-200 to-emerald-400 bg-clip-text text-transparent">
                {{ t('common.appName') }}
              </span>
              <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                {{ t('dashboard.phaseBadge') }}
              </span>
            </div>
            <p class="text-[11px] text-slate-400">{{ t('common.tagline') }}</p>
          </div>
        </div>

        <!-- Organization Switcher Dropdown (If authenticated) -->
        <div v-if="authUser && userOrgs.length > 0" class="relative hidden sm:block">
          <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-800/90 border border-slate-700/80 text-xs">
            <span class="text-slate-400">🏢</span>
            <select 
              :value="currentOrg?.id" 
              @change="handleOrgSwitch(Number(($event.target as HTMLSelectElement).value))"
              class="bg-transparent text-emerald-400 font-bold text-xs focus:outline-none cursor-pointer"
            >
              <option v-for="org in userOrgs" :key="org.id" :value="org.id" class="bg-slate-900 text-slate-200">
                {{ org.name }} ({{ org.role }})
              </option>
            </select>
            <button @click="showNewOrgModal = true" class="text-slate-400 hover:text-emerald-400 text-xs px-1" :title="t('tenancy.createOrg')">
              ➕
            </button>
          </div>
        </div>
      </div>

      <!-- Right Controls: Health Pill, Language, User -->
      <div class="flex items-center gap-3">
        <!-- Live API Health Pill -->
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800/80 border border-slate-700/60 text-xs">
          <div class="w-2 h-2 rounded-full" :class="healthStatus.status === 'healthy' ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"></div>
          <span class="text-slate-300 font-mono text-[11px]">API: {{ healthStatus.status || t('common.loading') }}</span>
        </div>

        <!-- Language Switcher Button -->
        <button 
          @click="toggleLanguage" 
          class="px-2.5 py-1.5 rounded-lg bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700/60 text-xs font-semibold text-emerald-400 transition-colors"
          :title="currentLocale === 'ar' ? 'Switch to English' : 'التحويل إلى العربية'"
        >
          {{ currentLocale === 'ar' ? 'English (LTR)' : 'العربية (RTL)' }}
        </button>

        <!-- User Profile Pill / Auth Button -->
        <div v-if="authUser" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-950/40 border border-emerald-500/30 text-xs">
          <div class="w-5 h-5 rounded-full bg-emerald-500 text-slate-950 font-bold flex items-center justify-center text-[10px]">
            {{ authUser.name.charAt(0).toUpperCase() }}
          </div>
          <div class="flex flex-col">
            <span class="text-emerald-300 font-medium leading-none">{{ authUser.name }}</span>
            <span class="text-[9px] text-emerald-400/80 uppercase font-bold">{{ userRole }}</span>
          </div>
          <button @click="handleLogout" class="text-slate-400 hover:text-red-400 text-[10px] mx-1">
            ({{ t('common.logout') }})
          </button>
        </div>
      </div>
    </header>

    <!-- Main Workspace Layout -->
    <div class="flex flex-1 overflow-hidden">
      <!-- Collapsible Sidebar -->
      <aside class="w-64 border-r border-slate-800/80 bg-slate-900/40 p-4 flex flex-col justify-between hidden md:flex">
        <div class="space-y-1">
          <div class="px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">
            {{ t('navigation.workspace') }}
          </div>

          <button 
            v-for="item in navItems" 
            :key="item.id"
            @click="activeNav = item.id"
            :class="[
              activeNav === item.id 
                ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 font-semibold' 
                : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-200 border-transparent',
              'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs border transition-all duration-150'
            ]"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm">{{ item.icon }}</span>
              <span>{{ t(item.titleKey) }}</span>
            </div>
            <span v-if="item.badge" class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-slate-800 text-slate-400 border border-slate-700">
              {{ item.badge }}
            </span>
          </button>
        </div>

        <!-- Current Tenant / Architecture Pill -->
        <div class="p-3.5 rounded-2xl bg-slate-800/40 border border-slate-700/40 text-xs space-y-1.5">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-slate-300">{{ currentOrg?.name || 'Multi-Tenant Ready' }}</span>
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
          </div>
          <p class="text-[10px] text-slate-400">Role: <span class="text-emerald-400 uppercase font-bold">{{ userRole }}</span> • {{ permissionsList.length }} Perms</p>
        </div>
      </aside>

      <!-- Main Content Area -->
      <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-8">
        <!-- Brand Brain View -->
        <div v-if="activeNav === 'brand_brain'">
          <BrandBrainHub 
            :auth-token="authToken" 
            :organization-id="currentOrg?.id"
          />
        </div>

        <!-- Dashboard View (Default) -->
        <div v-else class="space-y-8">
          <!-- Banner -->
          <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-950/60 via-slate-900 to-slate-900 p-6 md:p-8 border border-emerald-500/20 shadow-2xl">
            <div class="relative z-10 max-w-3xl space-y-3">
              <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold">
                <span>🛡️</span>
                <span>{{ t('dashboard.phaseBadge') }}</span>
              </div>
              <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">
                {{ t('dashboard.welcome') }}
              </h1>
              <p class="text-sm text-slate-300 leading-relaxed">
                {{ t('dashboard.welcomeDesc') }}
              </p>
            </div>
          </div>

          <!-- Identity, Multi-Tenancy & Auth Console -->
          <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
              <div>
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                  <span>🔐</span>
                  <span>{{ t('dashboard.authConsoleTitle') }}</span>
                </h2>
                <p class="text-xs text-slate-400 mt-1">
                  {{ t('dashboard.authConsoleDesc') }}
                </p>
              </div>

              <div class="flex items-center gap-2">
                <button 
                  v-if="authUser"
                  @click="showNewOrgModal = true"
                  class="px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold hover:bg-emerald-500/20"
                >
                  + {{ t('tenancy.createOrg') }}
                </button>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Auth Forms (Register / Login / Forgot) -->
              <div class="space-y-4 bg-slate-950/60 p-5 rounded-2xl border border-slate-800">
                <div class="flex items-center justify-between">
                  <h3 class="text-xs font-bold uppercase text-slate-300">
                    {{ authMode === 'register' ? t('dashboard.registerMode') : (authMode === 'login' ? t('dashboard.loginMode') : t('dashboard.forgotMode')) }}
                  </h3>
                </div>
                
                <div v-if="authMode === 'register'" class="space-y-1.5">
                  <label class="text-[11px] font-medium text-slate-400">{{ t('dashboard.fullName') }}</label>
                  <input v-model="authForm.name" type="text" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500" placeholder="Osama Sabry" />
                </div>

                <div class="space-y-1.5">
                  <label class="text-[11px] font-medium text-slate-400">{{ t('dashboard.email') }}</label>
                  <input v-model="authForm.email" type="email" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500" placeholder="admin@marketly.ai" />
                </div>

                <div v-if="authMode !== 'forgot'" class="space-y-1.5">
                  <label class="text-[11px] font-medium text-slate-400">{{ t('dashboard.password') }}</label>
                  <input v-model="authForm.password" type="password" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500" placeholder="••••••••" />
                </div>

                <div class="flex items-center gap-3 pt-2">
                  <button @click="submitAuth" :disabled="authLoading" class="tactile-btn tactile-btn-primary text-xs w-full py-2.5">
                    <span v-if="authLoading">{{ t('common.processing') }}</span>
                    <span v-else>
                      {{ authMode === 'register' ? t('dashboard.btnRegister') : (authMode === 'login' ? t('dashboard.btnLogin') : t('dashboard.btnForgot')) }}
                    </span>
                  </button>
                </div>

                <div class="flex items-center justify-between text-[11px] pt-1">
                  <button 
                    v-if="authMode !== 'login'" 
                    @click="authMode = 'login'" 
                    class="text-slate-400 hover:text-emerald-400 underline"
                  >
                    {{ t('dashboard.toggleToLogin') }}
                  </button>
                  <button 
                    v-if="authMode !== 'register'" 
                    @click="authMode = 'register'" 
                    class="text-slate-400 hover:text-emerald-400 underline"
                  >
                    {{ t('dashboard.toggleToRegister') }}
                  </button>
                  <button 
                    v-if="authMode !== 'forgot'" 
                    @click="authMode = 'forgot'" 
                    class="text-slate-400 hover:text-amber-400 underline"
                  >
                    {{ t('dashboard.toggleToForgot') }}
                  </button>
                </div>
              </div>

              <!-- Token & Response Inspector -->
              <div class="space-y-3 bg-slate-950/60 p-5 rounded-2xl border border-slate-800 flex flex-col justify-between">
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase text-slate-300">{{ t('dashboard.responseStream') }}</span>
                    <button @click="testMeEndpoint" :disabled="!authToken" class="text-[11px] text-emerald-400 hover:underline disabled:opacity-40">
                      {{ t('dashboard.callMeBtn') }}
                    </button>
                  </div>
                  <pre class="w-full h-44 p-3 rounded-xl bg-slate-900 border border-slate-800 text-[11px] font-mono text-emerald-400 overflow-y-auto">{{ authOutput || '// JSON Response stream...' }}</pre>
                </div>

                <div v-if="authToken" class="p-2.5 rounded-xl bg-emerald-950/30 border border-emerald-500/20 text-xs">
                  <span class="text-emerald-400 font-bold text-[10px] block">{{ t('dashboard.activeToken') }}:</span>
                  <span class="text-slate-300 font-mono text-[10px] truncate block">{{ authToken }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Members & Team Permissions Section (When Authenticated) -->
          <div v-if="authUser" class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
              <div>
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                  <span>👥</span>
                  <span>{{ t('tenancy.membersTitle') }}</span>
                </h3>
                <p class="text-xs text-slate-400">{{ currentOrg?.name }}</p>
              </div>
              <button 
                @click="showInviteModal = true" 
                class="px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold hover:bg-emerald-500/20"
              >
                + {{ t('tenancy.inviteMember') }}
              </button>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-xs text-start">
                <thead>
                  <tr class="border-b border-slate-800 text-slate-400 font-bold">
                    <th class="py-2.5 px-3 text-start">{{ t('dashboard.fullName') }}</th>
                    <th class="py-2.5 px-3 text-start">{{ t('dashboard.email') }}</th>
                    <th class="py-2.5 px-3 text-start">{{ t('tenancy.role') }}</th>
                    <th class="py-2.5 px-3 text-start">{{ t('common.status') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                  <tr v-for="m in orgMembers" :key="m.membership_id" class="hover:bg-slate-800/30">
                    <td class="py-2.5 px-3 font-semibold text-slate-200">{{ m.name }}</td>
                    <td class="py-2.5 px-3 font-mono text-slate-400">{{ m.email }}</td>
                    <td class="py-2.5 px-3">
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        {{ m.role }}
                      </span>
                    </td>
                    <td class="py-2.5 px-3">
                      <span class="text-emerald-400 text-[11px] font-medium">{{ m.status }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Master Roadmap Progress -->
          <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-4">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
              <span>🗺️</span>
              <span>{{ t('dashboard.roadmapTitle') }}</span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
              <div v-for="phase in phases" :key="phase.num" :class="[
                phase.status === 'completed' ? 'border-emerald-500/40 bg-emerald-950/20' : 
                phase.status === 'next' ? 'border-amber-500/40 bg-amber-950/20' : 'border-slate-800 bg-slate-950/30',
                'p-3.5 rounded-2xl border flex flex-col justify-between gap-2'
              ]">
                <div>
                  <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase text-slate-400">Phase {{ phase.num }}</span>
                    <span :class="[
                      phase.status === 'completed' ? 'text-emerald-400 bg-emerald-500/10' : 
                      phase.status === 'next' ? 'text-amber-400 bg-amber-500/10' : 'text-slate-500 bg-slate-800',
                      'text-[9px] font-bold px-1.5 py-0.5 rounded'
                    ]">{{ phase.status.toUpperCase() }}</span>
                  </div>
                  <h4 class="text-xs font-bold text-slate-200 mt-1">{{ isRtl ? phase.titleAr : phase.titleEn }}</h4>
                </div>
                <p class="text-[10px] text-slate-400">{{ isRtl ? phase.descAr : phase.descEn }}</p>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Create Organization Modal -->
    <div v-if="showNewOrgModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
        <h3 class="text-sm font-bold text-white">{{ t('tenancy.createOrg') }}</h3>
        
        <div class="space-y-1.5">
          <label class="text-[11px] text-slate-400">{{ t('tenancy.orgName') }}</label>
          <input v-model="newOrgForm.name" type="text" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white" placeholder="Acme Co." />
        </div>

        <div class="space-y-1.5">
          <label class="text-[11px] text-slate-400">{{ t('tenancy.orgType') }}</label>
          <select v-model="newOrgForm.type" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white">
            <option value="business">{{ t('tenancy.business') }}</option>
            <option value="agency">{{ t('tenancy.agency') }}</option>
          </select>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button @click="showNewOrgModal = false" class="px-3 py-2 rounded-xl bg-slate-800 text-xs text-slate-300">{{ t('common.cancel') }}</button>
          <button @click="handleCreateOrg" class="tactile-btn tactile-btn-primary text-xs px-4 py-2">{{ t('common.save') }}</button>
        </div>
      </div>
    </div>

    <!-- Invite Member Modal -->
    <div v-if="showInviteModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
        <h3 class="text-sm font-bold text-white">{{ t('tenancy.inviteMember') }}</h3>
        
        <div class="space-y-1.5">
          <label class="text-[11px] text-slate-400">{{ t('tenancy.inviteEmail') }}</label>
          <input v-model="inviteForm.email" type="email" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white" placeholder="colleague@brand.com" />
        </div>

        <div class="space-y-1.5">
          <label class="text-[11px] text-slate-400">{{ t('tenancy.role') }}</label>
          <select v-model="inviteForm.role" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white">
            <option value="admin">{{ t('tenancy.adminRole') }}</option>
            <option value="manager">{{ t('tenancy.managerRole') }}</option>
            <option value="editor">{{ t('tenancy.editorRole') }}</option>
            <option value="viewer">{{ t('tenancy.viewerRole') }}</option>
          </select>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button @click="showInviteModal = false" class="px-3 py-2 rounded-xl bg-slate-800 text-xs text-slate-300">{{ t('common.cancel') }}</button>
          <button @click="handleSendInvite" class="tactile-btn tactile-btn-primary text-xs px-4 py-2">{{ t('tenancy.sendInvite') }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { t, currentLocale, setLocale, isRtl } from './i18n';
import BrandBrainHub from './components/BrandBrainHub.vue';

const isDark = ref(true);
const activeNav = ref('brand_brain');

const healthStatus = ref<Record<string, any>>({});
const authMode = ref<'register' | 'login' | 'forgot'>('register');
const authLoading = ref(false);
const authUser = ref<any>(null);
const authToken = ref<string>('');
const authOutput = ref<string>('');

const userOrgs = ref<any[]>([]);
const currentOrg = ref<any>(null);
const userRole = ref<string>('viewer');
const permissionsList = ref<string[]>([]);
const orgMembers = ref<any[]>([]);

const showNewOrgModal = ref(false);
const newOrgForm = ref({
  name: '',
  type: 'business',
  default_locale: 'en',
});

const showInviteModal = ref(false);
const inviteForm = ref({
  email: '',
  role: 'editor',
});

const authForm = ref({
  name: 'Osama Sabry',
  email: 'admin@marketly.ai',
  password: 'Password123!',
});

const navItems = [
  { id: 'dashboard', titleKey: 'navigation.dashboard', icon: '📊', badge: 'Ready' },
  { id: 'brand_brain', titleKey: 'navigation.brandBrain', icon: '🧠', badge: 'Phase 2' },
  { id: 'strategy', titleKey: 'navigation.strategy', icon: '🎯', badge: 'Phase 3' },
  { id: 'content', titleKey: 'navigation.content', icon: '✍️', badge: 'Phase 4' },
  { id: 'creative', titleKey: 'navigation.creative', icon: '🎨', badge: 'Phase 5' },
  { id: 'calendar', titleKey: 'navigation.calendar', icon: '📅', badge: 'Phase 6' },
  { id: 'publishing', titleKey: 'navigation.publishing', icon: '⚡', badge: 'Phase 7' },
  { id: 'analytics', titleKey: 'navigation.analytics', icon: '📈', badge: 'Phase 9' },
  { id: 'assistant', titleKey: 'navigation.assistant', icon: '🤖', badge: 'Phase 10' },
  { id: 'settings', titleKey: 'navigation.settings', icon: '⚙️', badge: '' },
];

const phases = [
  { num: '0', titleEn: 'Foundation & Security', titleAr: 'الأساس المعماري والأمني', descEn: 'Laravel 11, Vue 3, Sanctum, Domain Contracts, i18n', descAr: 'لارافيل، فيو، سانكتوم، عقود النطاقات، والتعريب الكامل', status: 'completed' },
  { num: '1', titleEn: 'Tenancy & Identity', titleAr: 'الهوية وتعدد المستأجرين', descEn: 'Organizations, Roles, Policies, Isolation', descAr: 'المنظمات، الأدوار، العزل التام', status: 'completed' },
  { num: '2', titleEn: 'Brand Brain', titleAr: 'عقل العلامة التجارية', descEn: 'Knowledge Ingestion, Facts, Approval', descAr: 'استيعاب المستندات، استخراج الحقائق، ومجمع السياق', status: 'completed' },
  { num: '3', titleEn: 'AI Strategy', titleAr: 'استراتيجية الذكاء الاصطناعي', descEn: 'Pillars, 30-Day Plan, Gemini Agent', descAr: 'ركائز المحتوى، خطة 30 يوم', status: 'next' },
  { num: '4', titleEn: 'Content Studio', titleAr: 'استوديو المحتوى', descEn: 'Captions, Hooks, Quality Agent, Dialects', descAr: 'النصوص، الخطافات، فحص الجودة', status: 'pending' },
  { num: '5', titleEn: 'Creative Studio', titleAr: 'استوديو الإبداع', descEn: 'Prompts, Aspect Ratios, Video Briefs', descAr: 'توليد الصور، مقاسات النشر، الفيديو', status: 'pending' },
  { num: '6', titleEn: 'Content Calendar', titleAr: 'تقويم المحتوى', descEn: 'Interactive Scheduling, Status Flow', descAr: 'جدولة المنشورات وتدفق الحالات', status: 'pending' },
  { num: '7-12', titleEn: 'Publishing & SaaS', titleAr: 'النشر والتوسع التجاري', descEn: 'OAuth, Workers, Analytics, Assistant, Billing', descAr: 'النشر التلقائي، التحليلات، الفوترة', status: 'pending' },
];

const toggleLanguage = () => {
  setLocale(currentLocale.value === 'ar' ? 'en' : 'ar');
};

const fetchHealth = async () => {
  try {
    const res = await axios.get('/api/v1/health', {
      headers: { 'X-Locale': currentLocale.value },
    });
    healthStatus.value = res.data.data;
  } catch (err: any) {
    healthStatus.value = { status: 'error', message: err.message };
  }
};

const fetchOrganizations = async () => {
  if (!authToken.value) return;
  try {
    const res = await axios.get('/api/v1/organizations', {
      headers: {
        Authorization: `Bearer ${authToken.value}`,
        'X-Locale': currentLocale.value,
      },
    });
    userOrgs.value = res.data?.data?.organizations || [];
  } catch (e) {
    // Ignore error
  }
};

const fetchMembers = async () => {
  if (!authToken.value || !currentOrg.value?.id) return;
  try {
    const res = await axios.get(`/api/v1/organizations/${currentOrg.value.id}/members`, {
      headers: {
        Authorization: `Bearer ${authToken.value}`,
        'X-Locale': currentLocale.value,
        'X-Organization-Id': String(currentOrg.value.id),
      },
    });
    orgMembers.value = res.data?.data?.members || [];
  } catch (e) {
    // Ignore error
  }
};

const handleOrgSwitch = async (orgId: number) => {
  if (!authToken.value) return;
  try {
    const res = await axios.post(`/api/v1/organizations/{$orgId}/switch`, {}, {
      headers: {
        Authorization: `Bearer ${authToken.value}`,
        'X-Locale': currentLocale.value,
      },
    });
    authOutput.value = JSON.stringify(res.data, null, 2);
    await testMeEndpoint();
    await fetchOrganizations();
    await fetchMembers();
  } catch (err: any) {
    authOutput.value = JSON.stringify(err.response?.data || { error: err.message }, null, 2);
  }
};

const handleCreateOrg = async () => {
  if (!authToken.value || !newOrgForm.value.name) return;
  try {
    const res = await axios.post('/api/v1/organizations', newOrgForm.value, {
      headers: {
        Authorization: `Bearer ${authToken.value}`,
        'X-Locale': currentLocale.value,
      },
    });
    showNewOrgModal.value = false;
    newOrgForm.value.name = '';
    authOutput.value = JSON.stringify(res.data, null, 2);
    await testMeEndpoint();
    await fetchOrganizations();
  } catch (err: any) {
    authOutput.value = JSON.stringify(err.response?.data || { error: err.message }, null, 2);
  }
};

const handleSendInvite = async () => {
  if (!authToken.value || !currentOrg.value?.id || !inviteForm.value.email) return;
  try {
    const res = await axios.post(`/api/v1/organizations/${currentOrg.value.id}/invitations`, inviteForm.value, {
      headers: {
        Authorization: `Bearer ${authToken.value}`,
        'X-Locale': currentLocale.value,
        'X-Organization-Id': String(currentOrg.value.id),
      },
    });
    showInviteModal.value = false;
    inviteForm.value.email = '';
    authOutput.value = JSON.stringify(res.data, null, 2);
    await fetchMembers();
  } catch (err: any) {
    authOutput.value = JSON.stringify(err.response?.data || { error: err.message }, null, 2);
  }
};

const submitAuth = async () => {
  authLoading.value = true;
  authOutput.value = t('common.processing');
  try {
    if (authMode.value === 'forgot') {
      const res = await axios.post('/api/v1/auth/forgot-password', { email: authForm.value.email }, {
        headers: { 'X-Locale': currentLocale.value },
      });
      authOutput.value = JSON.stringify(res.data, null, 2);
      return;
    }

    const endpoint = authMode.value === 'register' ? '/api/v1/auth/register' : '/api/v1/auth/login';
    const payload = authMode.value === 'register' 
      ? { name: authForm.value.name, email: authForm.value.email, password: authForm.value.password }
      : { email: authForm.value.email, password: authForm.value.password };

    const res = await axios.post(endpoint, payload, {
      headers: { 'X-Locale': currentLocale.value },
    });
    authOutput.value = JSON.stringify(res.data, null, 2);
    if (res.data?.data?.token) {
      authToken.value = res.data.data.token;
      await testMeEndpoint();
      await fetchOrganizations();
    }
  } catch (err: any) {
    authOutput.value = JSON.stringify(err.response?.data || { error: err.message }, null, 2);
  } finally {
    authLoading.value = false;
  }
};

const testMeEndpoint = async () => {
  if (!authToken.value) return;
  try {
    const res = await axios.get('/api/v1/me', {
      headers: {
        Authorization: `Bearer ${authToken.value}`,
        'X-Locale': currentLocale.value,
      },
    });
    authOutput.value = JSON.stringify(res.data, null, 2);
    authUser.value = res.data.data.user;
    currentOrg.value = res.data.data.current_organization;
    userRole.value = res.data.data.role;
    permissionsList.value = res.data.data.permissions || [];
    await fetchMembers();
  } catch (err: any) {
    authOutput.value = JSON.stringify(err.response?.data || { error: err.message }, null, 2);
  }
};

const handleLogout = async () => {
  if (!authToken.value) return;
  try {
    await axios.post('/api/v1/auth/logout', {}, {
      headers: {
        Authorization: `Bearer ${authToken.value}`,
        'X-Locale': currentLocale.value,
      },
    });
  } catch (e) {
    // Ignore error
  }
  authToken.value = '';
  authUser.value = null;
  currentOrg.value = null;
  userOrgs.value = [];
  orgMembers.value = [];
  authOutput.value = t('dashboard.loggedOut');
};

onMounted(() => {
  fetchHealth();
});
</script>
