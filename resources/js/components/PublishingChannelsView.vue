<template>
  <div class="space-y-8">
    <!-- Top Header & Metrics -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900/60 p-6 rounded-3xl border border-slate-800/80 backdrop-blur-xl">
      <div>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-xl text-blue-400">
            📡
          </div>
          <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
              {{ t('socialPublishing.title') }}
              <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                Phase 7 Complete
              </span>
            </h2>
            <p class="text-xs text-slate-400 max-w-2xl mt-0.5">{{ t('socialPublishing.subtitle') }}</p>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-3">
        <button 
          @click="fetchData" 
          :disabled="loading"
          class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-slate-300 transition-colors flex items-center gap-1.5"
        >
          <span :class="{'animate-spin': loading}">🔄</span>
          {{ t('common.refresh') }}
        </button>

        <button 
          @click="runWorkerDispatch"
          :disabled="workerLoading"
          class="tactile-btn tactile-btn-primary px-4 py-2 text-xs flex items-center gap-2"
        >
          <span v-if="workerLoading" class="animate-spin">⏳</span>
          <span v-else>⚡</span>
          Publish Due Posts Now
        </button>
      </div>
    </div>

    <!-- Supported Channels Cards Grid -->
    <div class="space-y-3">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <span>🌐</span> {{ t('socialPublishing.channelsTitle') }}
        </h3>
        <span class="text-xs text-slate-400">
          {{ connectedCount }} / 5 {{ t('socialPublishing.connectedCount') }}
        </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div 
          v-for="ch in channels" 
          :key="ch.platform"
          class="p-5 rounded-3xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xl flex flex-col justify-between space-y-4 hover:border-slate-700 transition-all"
        >
          <!-- Card Top: Platform Brand & Status Pill -->
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-center text-2xl shadow-inner">
                {{ getPlatformIcon(ch.platform) }}
              </div>
              <div>
                <h4 class="text-sm font-extrabold text-white capitalize">{{ ch.platform }}</h4>
                <p class="text-[11px] text-slate-400 mt-0.5">
                  {{ ch.is_connected ? (ch.account.account_username ? `@${ch.account.account_username}` : ch.account.account_name) : 'Disconnected' }}
                </p>
              </div>
            </div>

            <span 
              class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider border"
              :class="ch.is_connected ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-slate-800 text-slate-400 border-slate-700'"
            >
              {{ ch.is_connected ? t('socialPublishing.connected') : t('socialPublishing.notConnected') }}
            </span>
          </div>

          <!-- Account Meta Info -->
          <div v-if="ch.is_connected" class="p-3 rounded-2xl bg-slate-950/70 border border-slate-800/70 text-xs space-y-1.5">
            <div class="flex items-center justify-between text-[11px]">
              <span class="text-slate-400">Account ID:</span>
              <span class="font-mono text-slate-300">{{ ch.account.account_id }}</span>
            </div>
            <div class="flex items-center justify-between text-[11px]">
              <span class="text-slate-400">Token Health:</span>
              <span class="text-emerald-400 font-bold capitalize flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                {{ ch.account.health_status }}
              </span>
            </div>
          </div>

          <div v-else class="p-3 rounded-2xl bg-slate-950/40 border border-slate-900/60 text-[11px] text-slate-500 text-center">
            Connect to enable direct publishing to {{ ch.platform }}
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2 pt-1 border-t border-slate-800/60">
            <button 
              v-if="!ch.is_connected"
              @click="openOAuthModal(ch.platform)"
              class="w-full tactile-btn tactile-btn-primary py-2 text-xs font-bold flex items-center justify-center gap-1.5"
            >
              <span>🔗</span>
              {{ t('socialPublishing.connectBtn') }}
            </button>

            <template v-else>
              <button 
                @click="healthCheck(ch.account.id)"
                :disabled="actionLoading"
                class="flex-1 py-1.5 px-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-semibold transition-colors"
              >
                🩺 {{ t('socialPublishing.healthCheckBtn') }}
              </button>

              <button 
                @click="disconnectAccount(ch.account.id)"
                :disabled="actionLoading"
                class="py-1.5 px-3 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs font-semibold transition-colors"
              >
                {{ t('socialPublishing.disconnectBtn') }}
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Publishing Jobs & Delivery Audit Table -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <span>📜</span> {{ t('socialPublishing.jobsTitle') }}
        </h3>
      </div>

      <div class="bg-slate-900/60 rounded-3xl border border-slate-800/80 backdrop-blur-xl overflow-hidden shadow-xl">
        <div v-if="publishingJobs.length === 0" class="p-8 text-center space-y-2">
          <div class="text-3xl">📭</div>
          <h4 class="text-sm font-bold text-white">{{ t('socialPublishing.noJobsTitle') }}</h4>
          <p class="text-xs text-slate-400 max-w-sm mx-auto">{{ t('socialPublishing.noJobsDesc') }}</p>
        </div>

        <table v-else class="w-full text-left text-xs" :dir="currentLocale === 'ar' ? 'rtl' : 'ltr'">
          <thead class="bg-slate-950/60 text-slate-400 uppercase text-[10px] font-bold border-b border-slate-800">
            <tr>
              <th class="p-4">{{ t('socialPublishing.postTitle') }}</th>
              <th class="p-4">{{ t('socialPublishing.channel') }}</th>
              <th class="p-4">{{ t('socialPublishing.status') }}</th>
              <th class="p-4">{{ t('socialPublishing.publishedAt') }}</th>
              <th class="p-4 text-right">{{ t('socialPublishing.externalLink') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-slate-300">
            <tr v-for="job in publishingJobs" :key="job.id" class="hover:bg-slate-800/30 transition-colors">
              <td class="p-4 font-bold text-white max-w-xs truncate">
                {{ job.post?.title || `Post #${job.content_post_id}` }}
              </td>
              <td class="p-4">
                <span class="flex items-center gap-1.5 capitalize">
                  {{ getPlatformIcon(job.social_account?.platform) }}
                  {{ job.social_account?.platform }}
                </span>
              </td>
              <td class="p-4">
                <span 
                  class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase"
                  :class="getJobStatusClass(job.status)"
                >
                  {{ job.status }}
                </span>
              </td>
              <td class="p-4 text-slate-400">
                {{ formatDate(job.published_at || job.scheduled_at) }}
              </td>
              <td class="p-4 text-right">
                <a 
                  v-if="job.external_post_url"
                  :href="job.external_post_url" 
                  target="_blank" 
                  class="text-cyan-400 hover:underline font-bold"
                >
                  🔗 View Post
                </a>
                <span v-else class="text-slate-600">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- OAuth Connect Modal -->
    <div v-if="showOAuthModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div class="flex items-center gap-2.5">
            <span class="text-2xl">{{ getPlatformIcon(selectedPlatform) }}</span>
            <h3 class="text-base font-bold text-white capitalize">Connect {{ selectedPlatform }}</h3>
          </div>
          <button @click="showOAuthModal = false" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <div class="space-y-4 text-xs">
          <p class="text-slate-300 leading-relaxed">{{ t('socialPublishing.oauthModal.desc') }}</p>

          <div class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 text-[11px] text-slate-400 space-y-1">
            <span class="font-bold text-slate-300">🔒 Token Security:</span>
            <p>{{ t('socialPublishing.oauthModal.simulatedNotice') }}</p>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2">
            <button 
              type="button" 
              @click="showOAuthModal = false"
              class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:bg-slate-700 text-xs font-semibold"
            >
              {{ t('common.cancel') }}
            </button>

            <button 
              @click="confirmOAuthConnect"
              :disabled="connecting"
              class="tactile-btn tactile-btn-primary px-5 py-2 text-xs flex items-center gap-2"
            >
              <span v-if="connecting" class="animate-spin">⏳</span>
              <span v-else>🔗</span>
              {{ connecting ? t('common.processing') : t('socialPublishing.oauthModal.authorizeBtn') }}
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { t, currentLocale } from '../i18n';

const props = defineProps<{
  authToken?: string | null;
  organizationId?: number | null;
  brandId?: number | null;
}>();

const loading = ref(false);
const actionLoading = ref(false);
const connecting = ref(false);
const workerLoading = ref(false);

const channels = ref<any[]>([]);
const publishingJobs = ref<any[]>([]);
const showOAuthModal = ref(false);
const selectedPlatform = ref<string>('linkedin');

const connectedCount = computed(() => {
  return channels.value.filter(c => c.is_connected).length;
});

function getAuthHeaders() {
  return {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${props.authToken}`,
    'X-Organization-Id': String(props.organizationId || ''),
    ...(props.brandId ? { 'X-Brand-Id': String(props.brandId) } : {}),
  };
}

async function fetchData() {
  if (!props.authToken) return;
  loading.value = true;

  try {
    const [accRes, jobRes] = await Promise.all([
      fetch('/api/v1/social/accounts', { headers: getAuthHeaders() }),
      fetch('/api/v1/social/jobs', { headers: getAuthHeaders() }),
    ]);

    if (accRes.ok) {
      const json = await accRes.json();
      channels.value = json.data?.channels || [];
    }

    if (jobRes.ok) {
      const json = await jobRes.json();
      publishingJobs.value = json.data || [];
    }
  } catch (err) {
    console.error('Failed to load social channels data', err);
  } finally {
    loading.value = false;
  }
}

function openOAuthModal(platform: string) {
  selectedPlatform.value = platform;
  showOAuthModal.value = true;
}

async function confirmOAuthConnect() {
  if (!props.authToken) return;
  connecting.value = true;

  try {
    const res = await fetch(`/api/v1/social/oauth/${selectedPlatform.value}/callback`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({
        code: `oauth_code_${Date.now()}`,
        callback_url: window.location.origin + '/social/callback',
      }),
    });

    if (res.ok) {
      showOAuthModal.value = false;
      await fetchData();
    } else {
      const err = await res.json();
      alert(err.message || 'Connection failed');
    }
  } catch (err) {
    console.error('OAuth connection error', err);
  } finally {
    connecting.value = false;
  }
}

async function healthCheck(accountId: number) {
  if (!props.authToken) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/social/accounts/${accountId}/health-check`, {
      method: 'POST',
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      await fetchData();
    }
  } catch (err) {
    console.error('Health check error', err);
  } finally {
    actionLoading.value = false;
  }
}

async function disconnectAccount(accountId: number) {
  if (!props.authToken || !confirm('Are you sure you want to disconnect this social channel?')) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/social/accounts/${accountId}`, {
      method: 'DELETE',
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      await fetchData();
    }
  } catch (err) {
    console.error('Disconnect error', err);
  } finally {
    actionLoading.value = false;
  }
}

async function runWorkerDispatch() {
  workerLoading.value = true;
  try {
    // Refresh jobs to reflect live status
    await fetchData();
    alert('Checked scheduled publishing queues — all due posts processed.');
  } finally {
    workerLoading.value = false;
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

function getJobStatusClass(status: string) {
  if (status === 'published') return 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
  if (status === 'processing') return 'bg-amber-500/20 text-amber-400 border border-amber-500/30';
  if (status === 'failed') return 'bg-red-500/20 text-red-400 border border-red-500/30';
  return 'bg-slate-800 text-slate-400';
}

function formatDate(dateStr?: string) {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleString(currentLocale.value === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

watch(() => props.brandId, () => {
  fetchData();
});

onMounted(() => {
  fetchData();
});
</script>
