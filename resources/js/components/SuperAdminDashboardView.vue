<template>
  <div class="space-y-8">
    <!-- Top Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900/60 p-6 rounded-3xl border border-slate-800/80 backdrop-blur-xl">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-xl text-amber-400">
          👑
        </div>
        <div>
          <h2 class="text-xl font-bold text-white flex items-center gap-2">
            {{ t('superAdmin.title') }}
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">
              Master Admin
            </span>
          </h2>
          <p class="text-xs text-slate-400 max-w-2xl mt-0.5">{{ t('superAdmin.subtitle') }}</p>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <button 
          @click="fetchData" 
          :disabled="loading"
          class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-slate-300 transition-colors flex items-center gap-1.5"
        >
          <span :class="{'animate-spin': loading}">🔄</span>
          {{ t('common.refresh') }}
        </button>
      </div>
    </div>

    <!-- Global KPIs Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5">
      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xl space-y-1">
        <span class="text-[11px] text-slate-400">{{ t('superAdmin.kpis.mrr') }}</span>
        <div class="text-xl font-black text-emerald-400 flex items-baseline gap-1">
          ${{ formatNumber(kpis.estimated_mrr) }}
          <span class="text-[10px] text-emerald-500 font-bold">/mo</span>
        </div>
      </div>

      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xl space-y-1">
        <span class="text-[11px] text-slate-400">{{ t('superAdmin.kpis.totalCompanies') }}</span>
        <div class="text-xl font-black text-white flex items-baseline gap-1">
          {{ kpis.total_organizations || 0 }}
          <span class="text-[10px] text-cyan-400 font-bold">🏢</span>
        </div>
      </div>

      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xl space-y-1">
        <span class="text-[11px] text-slate-400">{{ t('superAdmin.kpis.activeSubscriptions') }}</span>
        <div class="text-xl font-black text-purple-400 flex items-baseline gap-1">
          {{ kpis.active_subscriptions || 0 }}
          <span class="text-[10px] text-purple-400 font-bold">💳</span>
        </div>
      </div>

      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xl space-y-1">
        <span class="text-[11px] text-slate-400">{{ t('superAdmin.kpis.publishedPosts') }}</span>
        <div class="text-xl font-black text-amber-400 flex items-baseline gap-1">
          {{ formatNumber(kpis.published_posts) }}
          <span class="text-[10px] text-amber-400 font-bold">📝</span>
        </div>
      </div>

      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xl space-y-1">
        <span class="text-[11px] text-slate-400">{{ t('superAdmin.kpis.aiGenerations') }}</span>
        <div class="text-xl font-black text-cyan-400 flex items-baseline gap-1">
          {{ formatNumber(kpis.ai_generations_count) }}
          <span class="text-[10px] text-cyan-400 font-bold">✨</span>
        </div>
      </div>
    </div>

    <!-- Plan Revenue Distribution Breakdown -->
    <div class="space-y-4">
      <h3 class="text-sm font-bold text-white flex items-center gap-2">
        <span>📊</span> {{ t('superAdmin.subscriptions.title') }}
      </h3>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div 
          v-for="plan in planDistribution" 
          :key="plan.plan_id"
          class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800/80 backdrop-blur-xl space-y-2 flex flex-col justify-between"
        >
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-bold text-white capitalize">{{ plan.name }}</h4>
            <span class="text-[11px] font-mono text-cyan-400 font-bold">${{ plan.price_monthly }}/mo</span>
          </div>

          <div class="flex items-center justify-between pt-2 border-t border-slate-800/60 text-[11px] text-slate-400">
            <span>{{ plan.subscribers_count }} subscribers</span>
            <span class="font-bold text-emerald-400 font-mono">${{ plan.revenue_contribution }}/mo</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Organizations Management Section -->
    <div class="space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <span>🏢</span> {{ t('superAdmin.companies.title') }}
        </h3>

        <!-- Search & Filter Controls -->
        <div class="flex items-center gap-2">
          <input 
            v-model="searchQuery" 
            @input="debounceFetchOrgs"
            type="text" 
            :placeholder="t('superAdmin.companies.searchPlaceholder')" 
            class="px-3.5 py-1.5 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-cyan-500 outline-none w-52 sm:w-64"
          />

          <select 
            v-model="statusFilter" 
            @change="fetchOrganizations"
            class="px-3 py-1.5 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-white focus:border-cyan-500 outline-none"
          >
            <option value="all">{{ t('superAdmin.companies.allStatus') }}</option>
            <option value="active">{{ t('superAdmin.companies.activeOnly') }}</option>
            <option value="suspended">{{ t('superAdmin.companies.suspendedOnly') }}</option>
          </select>
        </div>
      </div>

      <!-- Organizations Table -->
      <div class="bg-slate-900/60 rounded-3xl border border-slate-800/80 backdrop-blur-xl overflow-hidden shadow-xl">
        <table class="w-full text-left text-xs" :dir="currentLocale === 'ar' ? 'rtl' : 'ltr'">
          <thead class="bg-slate-950/60 text-slate-400 uppercase text-[10px] font-bold border-b border-slate-800">
            <tr>
              <th class="p-4">Company / Organization</th>
              <th class="p-4">Plan & Subscription</th>
              <th class="p-4">Channels</th>
              <th class="p-4">AI Usage (Mo)</th>
              <th class="p-4">Posts</th>
              <th class="p-4">AI Keys</th>
              <th class="p-4">Status</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-slate-300">
            <tr v-for="org in organizations" :key="org.id" class="hover:bg-slate-800/30 transition-colors">
              <td class="p-4">
                <div class="font-bold text-white flex items-center gap-1.5">
                  {{ org.name }}
                  <span class="text-[10px] text-slate-500 font-mono">#{{ org.id }}</span>
                </div>
                <div class="text-[11px] text-slate-400 truncate max-w-xs mt-0.5">
                  {{ org.slug }} • {{ org.industry || 'General Business' }}
                </div>
              </td>

              <td class="p-4">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold capitalize" :class="getPlanBadgeClass(org.current_plan?.slug)">
                  {{ org.current_plan?.name || 'Free Trial' }}
                </span>
              </td>

              <!-- Social Accounts Connected vs Plan Limit -->
              <td class="p-4 font-mono text-xs">
                <div class="flex items-center gap-1.5">
                  <span :class="org.connected_social_accounts_count > 0 ? 'text-cyan-400 font-bold' : 'text-slate-500'">
                    {{ org.connected_social_accounts_count ?? 0 }}
                  </span>
                  <span class="text-slate-600">/</span>
                  <span class="text-slate-400">
                    {{ org.social_accounts_limit === -1 ? '∞' : (org.social_accounts_limit ?? 0) }}
                  </span>
                </div>
              </td>

              <!-- AI Content Quota Used this month vs Limit -->
              <td class="p-4 font-mono text-xs">
                <div class="flex items-center gap-1.5">
                  <span :class="org.ai_content_used_this_month > 0 ? 'text-amber-400 font-bold' : 'text-slate-400'">
                    {{ org.ai_content_used_this_month ?? 0 }}
                  </span>
                  <span class="text-slate-600">/</span>
                  <span class="text-slate-400">
                    {{ org.ai_content_limit === -1 ? '∞' : (org.ai_content_limit ?? 30) }}
                  </span>
                </div>
              </td>

              <td class="p-4 font-mono text-xs">{{ org.published_posts_count || 0 }} / {{ org.posts_count || 0 }}</td>

              <td class="p-4">
                <span v-if="org.has_custom_ai_keys" class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold">
                  BYOK Active
                </span>
                <span v-else class="text-slate-500 text-[10px]">Default</span>
              </td>

              <td class="p-4">
                <span 
                  class="px-2.5 py-1 rounded-full text-[10px] font-bold capitalize"
                  :class="org.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'"
                >
                  {{ org.status }}
                </span>
              </td>

              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <!-- 1-Click Login as Company Button -->
                  <button 
                    @click="impersonateOrg(org)"
                    :disabled="impersonatingId === org.id"
                    class="px-3 py-1.5 rounded-xl bg-cyan-500/10 hover:bg-cyan-500/20 border border-cyan-500/30 text-cyan-300 text-xs font-bold transition-all flex items-center gap-1"
                    title="Login as Company"
                  >
                    <span>🏢</span>
                    {{ t('superAdmin.companies.loginAsCompany') }}
                  </button>

                  <!-- Plan Switcher Button -->
                  <button 
                    @click="openPlanModal(org)"
                    class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-semibold transition-colors"
                    title="Change Plan"
                  >
                    ⚡
                  </button>

                  <!-- Status Toggle Button -->
                  <button 
                    @click="toggleStatus(org)"
                    class="px-2.5 py-1.5 rounded-xl border text-xs font-semibold transition-colors"
                    :class="org.status === 'active' ? 'bg-amber-500/10 border-amber-500/30 text-amber-400 hover:bg-amber-500/20' : 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20'"
                    :title="org.status === 'active' ? 'Suspend Organization' : 'Activate Organization'"
                  >
                    {{ org.status === 'active' ? '🔒' : '✅' }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Plan Override Modal -->
    <div v-if="selectedOrgForPlan" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-6 shadow-2xl">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-bold text-white flex items-center gap-2">
            <span>⚡</span> {{ t('superAdmin.companies.planModalTitle') }}
          </h3>
          <button @click="selectedOrgForPlan = null" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>

        <p class="text-xs text-slate-400">
          Override subscription plan for <strong class="text-white">{{ selectedOrgForPlan.name }}</strong>:
        </p>

        <div class="space-y-2.5">
          <div 
            v-for="plan in availablePlans" 
            :key="plan.id"
            @click="targetPlanId = plan.id"
            class="p-3.5 rounded-2xl border cursor-pointer transition-all flex items-center justify-between"
            :class="targetPlanId === plan.id ? 'bg-cyan-500/10 border-cyan-500 text-white' : 'bg-slate-950/60 border-slate-800 text-slate-300 hover:border-slate-700'"
          >
            <div>
              <div class="text-xs font-bold capitalize">{{ plan.name }}</div>
              <div class="text-[10px] text-slate-400">{{ plan.description || 'Full platform capabilities' }}</div>
            </div>
            <div class="text-xs font-mono font-bold text-cyan-400">${{ plan.price_monthly }}/mo</div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
          <button @click="selectedOrgForPlan = null" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold">
            {{ t('common.cancel') }}
          </button>
          <button @click="savePlanChange" :disabled="planActionLoading" class="tactile-btn tactile-btn-primary px-5 py-2 text-xs font-bold">
            <span v-if="planActionLoading" class="animate-spin">⏳</span>
            {{ t('common.save') }}
          </button>
        </div>
      </div>
    </div>

    <!-- System Diagnostics & Platform Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <!-- Platform Breakdown -->
      <div class="lg:col-span-6 space-y-4">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <span>🌐</span> {{ t('superAdmin.reports.platformBreakdown') }}
        </h3>

        <div class="bg-slate-900/60 rounded-3xl border border-slate-800/80 p-5 space-y-3 backdrop-blur-xl">
          <div v-for="item in platformBreakdown" :key="item.primary_platform" class="p-3 rounded-2xl bg-slate-950/70 border border-slate-800/70 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <span class="text-base">{{ getPlatformIcon(item.primary_platform) }}</span>
              <span class="text-xs font-bold text-white capitalize">{{ item.primary_platform }}</span>
            </div>
            <span class="text-xs font-mono font-bold text-cyan-400">{{ formatNumber(item.total_posts) }} posts</span>
          </div>
        </div>
      </div>

      <!-- Infrastructure Health -->
      <div class="lg:col-span-6 space-y-4">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <span>🛡️</span> {{ t('superAdmin.reports.systemHealth') }}
        </h3>

        <div class="bg-slate-900/60 rounded-3xl border border-slate-800/80 p-5 space-y-3 backdrop-blur-xl text-xs">
          <div class="flex justify-between py-2 border-b border-slate-800/60">
            <span class="text-slate-400">Database Engine:</span>
            <span class="font-mono text-emerald-400 font-bold">Operational (SQLite / MySQL Active)</span>
          </div>
          <div class="flex justify-between py-2 border-b border-slate-800/60">
            <span class="text-slate-400">Cache & Session Driver:</span>
            <span class="font-mono text-cyan-400 font-bold">Encrypted File / Redis</span>
          </div>
          <div class="flex justify-between py-2 border-b border-slate-800/60">
            <span class="text-slate-400">Framework Environment:</span>
            <span class="font-mono text-slate-200">Laravel 11 • PHP {{ systemHealth?.php_version || '8.2' }}</span>
          </div>
          <div class="flex justify-between py-2">
            <span class="text-slate-400">Tenant Isolation Guard:</span>
            <span class="font-mono text-emerald-400 font-bold">Strict Multi-Tenant Active</span>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { t, currentLocale } from '../i18n';

const props = defineProps<{
  authToken?: string | null;
  organizationId?: number | null;
}>();

const emit = defineEmits(['impersonate-success']);

const loading = ref(false);
const impersonatingId = ref<number | null>(null);
const kpis = ref<any>({});
const planDistribution = ref<any[]>([]);
const organizations = ref<any[]>([]);
const searchQuery = ref('');
const statusFilter = ref('all');
const platformBreakdown = ref<any[]>([]);
const systemHealth = ref<any>({});

// Plan modal state
const selectedOrgForPlan = ref<any>(null);
const targetPlanId = ref<number>(1);
const planActionLoading = ref(false);
const availablePlans = ref<any[]>([
  { id: 1, name: 'Starter Plan', price_monthly: 29, description: 'Single workspace, basic AI generations' },
  { id: 2, name: 'Professional Plan', price_monthly: 79, description: 'Multi-channel publishing, full strategy suite' },
  { id: 3, name: 'Enterprise Plan', price_monthly: 299, description: 'Unlimited seats, custom BYOK AI models' },
]);

let debounceTimer: any = null;

function getAuthHeaders() {
  return {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${props.authToken}`,
    'X-Organization-Id': String(props.organizationId || ''),
  };
}

async function fetchData() {
  if (!props.authToken) return;
  loading.value = true;

  try {
    const [kpiRes, reportsRes] = await Promise.all([
      fetch('/api/v1/super-admin/kpis', { headers: getAuthHeaders() }),
      fetch('/api/v1/super-admin/reports', { headers: getAuthHeaders() }),
    ]);

    if (kpiRes.ok) {
      const json = await kpiRes.json();
      kpis.value = json.data?.kpis || {};
      planDistribution.value = json.data?.plan_distribution || [];
    }

    if (reportsRes.ok) {
      const json = await reportsRes.json();
      platformBreakdown.value = json.data?.platform_breakdown || [];
      systemHealth.value = json.data?.system_health || {};
    }

    await fetchOrganizations();
  } catch (err) {
    console.error('Super Admin fetch failed', err);
  } finally {
    loading.value = false;
  }
}

async function fetchOrganizations() {
  if (!props.authToken) return;

  try {
    let url = `/api/v1/super-admin/organizations?status=${statusFilter.value}`;
    if (searchQuery.value.trim()) {
      url += `&search=${encodeURIComponent(searchQuery.value.trim())}`;
    }

    const res = await fetch(url, { headers: getAuthHeaders() });
    if (res.ok) {
      const json = await res.json();
      organizations.value = json.data?.organizations || [];
    }
  } catch (err) {
    console.error('Failed to fetch organizations', err);
  }
}

function debounceFetchOrgs() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchOrganizations();
  }, 300);
}

async function impersonateOrg(org: any) {
  if (!props.authToken) return;
  impersonatingId.value = org.id;

  try {
    const res = await fetch(`/api/v1/super-admin/organizations/${org.id}/impersonate`, {
      method: 'POST',
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      const json = await res.json();
      emit('impersonate-success', json.data);
    } else {
      alert('Impersonation failed.');
    }
  } catch (err) {
    console.error('Impersonate error', err);
  } finally {
    impersonatingId.value = null;
  }
}

async function toggleStatus(org: any) {
  if (!props.authToken) return;
  const newStatus = org.status === 'active' ? 'suspended' : 'active';

  try {
    const res = await fetch(`/api/v1/super-admin/organizations/${org.id}/status`, {
      method: 'PATCH',
      headers: getAuthHeaders(),
      body: JSON.stringify({ status: newStatus }),
    });

    if (res.ok) {
      org.status = newStatus;
    }
  } catch (err) {
    console.error('Status toggle failed', err);
  }
}

function openPlanModal(org: any) {
  selectedOrgForPlan.value = org;
  targetPlanId.value = org.current_plan?.id || 1;
}

async function savePlanChange() {
  if (!selectedOrgForPlan.value || !props.authToken) return;
  planActionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/super-admin/organizations/${selectedOrgForPlan.value.id}/plan`, {
      method: 'PATCH',
      headers: getAuthHeaders(),
      body: JSON.stringify({ plan_id: targetPlanId.value }),
    });

    if (res.ok) {
      await fetchOrganizations();
      selectedOrgForPlan.value = null;
    }
  } catch (err) {
    console.error('Failed to change plan', err);
  } finally {
    planActionLoading.value = false;
  }
}

function getPlanBadgeClass(slug?: string) {
  switch (slug) {
    case 'enterprise':
      return 'bg-purple-500/20 text-purple-300 border border-purple-500/30';
    case 'professional':
      return 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30';
    default:
      return 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30';
  }
}

function getPlatformIcon(platform?: string) {
  const icons: Record<string, string> = {
    linkedin: '💼',
    instagram: '📸',
    x: '🐦',
    tiktok: '🎬',
    facebook: '👥',
  };
  return icons[platform?.toLowerCase() || ''] || '🌐';
}

function formatNumber(num?: number) {
  if (!num) return '0';
  return num.toLocaleString(currentLocale.value === 'ar' ? 'ar-SA' : 'en-US');
}

onMounted(() => {
  fetchData();
});
</script>
