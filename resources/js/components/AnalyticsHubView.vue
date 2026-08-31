<template>
  <div class="space-y-8">
    <!-- Top Header & Metrics Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900/60 p-6 rounded-3xl border border-slate-800/80 backdrop-blur-xl">
      <div>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-xl text-amber-400">
            📊
          </div>
          <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
              {{ t('analyticsHub.title') }}
              <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                Phase 8 Complete
              </span>
            </h2>
            <p class="text-xs text-slate-400 max-w-2xl mt-0.5">{{ t('analyticsHub.subtitle') }}</p>
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
          @click="syncAnalytics"
          :disabled="syncing"
          class="tactile-btn tactile-btn-primary px-4 py-2 text-xs flex items-center gap-2"
        >
          <span v-if="syncing" class="animate-spin">⏳</span>
          <span v-else>⚡</span>
          {{ t('analyticsHub.syncBtn') }}
        </button>
      </div>
    </div>

    <!-- Executive KPI Widgets Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5">
      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xl space-y-1">
        <span class="text-[11px] text-slate-400">{{ t('analyticsHub.kpis.totalReach') }}</span>
        <div class="text-xl font-black text-white flex items-baseline gap-1">
          {{ formatNumber(kpis.total_reach) }}
          <span class="text-[10px] text-emerald-400 font-bold">👥</span>
        </div>
      </div>

      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xl space-y-1">
        <span class="text-[11px] text-slate-400">{{ t('analyticsHub.kpis.totalImpressions') }}</span>
        <div class="text-xl font-black text-cyan-400 flex items-baseline gap-1">
          {{ formatNumber(kpis.total_impressions) }}
          <span class="text-[10px] text-cyan-400 font-bold">👁️</span>
        </div>
      </div>

      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xl space-y-1">
        <span class="text-[11px] text-slate-400">{{ t('analyticsHub.kpis.avgEngagementRate') }}</span>
        <div class="text-xl font-black text-emerald-400 flex items-baseline gap-1">
          {{ kpis.avg_engagement_rate || 0 }}%
          <span class="text-[10px] text-emerald-400 font-bold">📈</span>
        </div>
      </div>

      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xl space-y-1">
        <span class="text-[11px] text-slate-400">{{ t('analyticsHub.kpis.totalClicks') }}</span>
        <div class="text-xl font-black text-purple-400 flex items-baseline gap-1">
          {{ formatNumber(kpis.total_clicks) }}
          <span class="text-[10px] text-purple-400 font-bold">🖱️</span>
        </div>
      </div>

      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xl space-y-1">
        <span class="text-[11px] text-slate-400">{{ t('analyticsHub.kpis.publishedPosts') }}</span>
        <div class="text-xl font-black text-amber-400 flex items-baseline gap-1">
          {{ kpis.published_posts_count || 0 }}
          <span class="text-[10px] text-amber-400 font-bold">📝</span>
        </div>
      </div>

      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xl space-y-1">
        <span class="text-[11px] text-slate-400">{{ t('analyticsHub.kpis.totalFollowers') }}</span>
        <div class="text-xl font-black text-blue-400 flex items-baseline gap-1">
          {{ formatNumber(kpis.total_followers) }}
          <span class="text-[10px] text-blue-400 font-bold">🌐</span>
        </div>
      </div>
    </div>

    <!-- AI Autonomous Strategy Recommendations & Learning Panel -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <span>🧠</span> {{ t('analyticsHub.recommendationsTitle') }}
          <span class="px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-300 border border-purple-500/20 text-[10px] font-mono font-bold">
            {{ recommendations.length }} Active
          </span>
        </h3>
      </div>

      <div v-if="recommendations.length === 0" class="p-8 rounded-3xl bg-slate-900/40 border border-slate-800/60 text-center space-y-2">
        <div class="text-3xl">💡</div>
        <h4 class="text-sm font-bold text-white">{{ t('analyticsHub.noRecommendationsTitle') }}</h4>
        <p class="text-xs text-slate-400 max-w-sm mx-auto">{{ t('analyticsHub.noRecommendationsDesc') }}</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div 
          v-for="rec in recommendations" 
          :key="rec.id"
          class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800/80 backdrop-blur-xl space-y-4 flex flex-col justify-between hover:border-purple-500/40 transition-all shadow-lg"
        >
          <div class="space-y-2.5">
            <div class="flex items-center justify-between">
              <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-purple-500/10 text-purple-300 border border-purple-500/20">
                {{ rec.type }}
              </span>
              <span class="text-[11px] font-bold text-emerald-400 font-mono">
                🛡️ {{ (rec.confidence_score * 100).toFixed(0) }}% {{ currentLocale === 'ar' ? 'ثقة' : 'confidence' }}
              </span>
            </div>

            <h4 class="text-xs font-bold text-white leading-snug">{{ rec.title }}</h4>
            <p class="text-[11px] text-slate-300 leading-relaxed">{{ rec.explanation }}</p>

            <!-- Evidence snippet -->
            <div v-if="rec.evidence_json" class="p-3 rounded-2xl bg-slate-950/70 border border-slate-800/70 text-[10px] text-slate-400 space-y-1">
              <span class="font-bold text-slate-300">{{ t('analyticsHub.evidence') }}:</span>
              <div v-for="(val, key) in rec.evidence_json" :key="key" class="flex justify-between">
                <span class="capitalize">{{ key.replace('_', ' ') }}:</span>
                <span class="font-mono text-slate-200 font-bold truncate max-w-[140px]">{{ val }}</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2 pt-2 border-t border-slate-800/60">
            <button 
              v-if="rec.status === 'active'"
              @click="applyRecommendation(rec.id)"
              :disabled="actionLoading"
              class="flex-1 tactile-btn tactile-btn-primary py-2 text-xs font-bold"
            >
              ✨ {{ t('analyticsHub.applyRecommendationBtn') }}
            </button>

            <button 
              v-if="rec.status === 'active'"
              @click="dismissRecommendation(rec.id)"
              :disabled="actionLoading"
              class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 text-xs font-semibold"
            >
              {{ t('analyticsHub.dismissRecommendationBtn') }}
            </button>

            <span v-else class="text-[10px] font-bold text-emerald-400 uppercase py-1">
              ✅ Applied
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Middle Grid: Channels & Content Pillars -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <!-- 1. Channel Performance Matrix -->
      <div class="lg:col-span-6 space-y-4">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <span>🌐</span> {{ t('analyticsHub.channelsTitle') }}
        </h3>

        <div class="bg-slate-900/60 rounded-3xl border border-slate-800/80 p-5 space-y-3 backdrop-blur-xl">
          <div 
            v-for="ch in channels" 
            :key="ch.platform"
            class="p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800/70 flex items-center justify-between"
          >
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-lg">
                {{ getPlatformIcon(ch.platform) }}
              </div>
              <div>
                <h4 class="text-xs font-bold text-white capitalize">{{ ch.platform }}</h4>
                <p class="text-[10px] text-slate-400">{{ ch.posts_count }} {{ currentLocale === 'ar' ? 'منشورات' : 'posts' }}</p>
              </div>
            </div>

            <div class="flex items-center gap-6 text-right">
              <div>
                <div class="text-xs font-bold text-cyan-400">{{ formatNumber(ch.impressions) }}</div>
                <div class="text-[9px] text-slate-500 uppercase">{{ t('analyticsHub.viewsCount') }}</div>
              </div>

              <div>
                <div class="text-xs font-bold text-emerald-400">{{ ch.avg_engagement_rate }}%</div>
                <div class="text-[9px] text-slate-500 uppercase">{{ t('analyticsHub.engagementScore') }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Content Pillars Attribution -->
      <div class="lg:col-span-6 space-y-4">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <span>🎯</span> {{ t('analyticsHub.pillarsTitle') }}
        </h3>

        <div class="bg-slate-900/60 rounded-3xl border border-slate-800/80 p-5 space-y-3 backdrop-blur-xl">
          <div 
            v-for="p in pillarPerformance" 
            :key="p.pillar_id"
            class="p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800/70 space-y-2"
          >
            <div class="flex items-center justify-between">
              <h4 class="text-xs font-bold text-white">{{ p.pillar_name }}</h4>
              <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold">
                ROI {{ p.roi_score }}
              </span>
            </div>

            <div class="flex items-center justify-between text-[10px] text-slate-400 pt-1 border-t border-slate-800/50">
              <span>{{ p.posts_count }} posts • {{ p.objective }}</span>
              <span class="font-bold text-slate-300">{{ p.avg_engagement_rate }}% Avg. Engagement</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Top-Performing Content Leaderboard Table -->
    <div class="space-y-4">
      <h3 class="text-sm font-bold text-white flex items-center gap-2">
        <span>🏆</span> {{ t('analyticsHub.leaderboardTitle') }}
      </h3>

      <div class="bg-slate-900/60 rounded-3xl border border-slate-800/80 backdrop-blur-xl overflow-hidden shadow-xl">
        <table class="w-full text-left text-xs" :dir="currentLocale === 'ar' ? 'rtl' : 'ltr'">
          <thead class="bg-slate-950/60 text-slate-400 uppercase text-[10px] font-bold border-b border-slate-800">
            <tr>
              <th class="p-4">Post & Hook Message</th>
              <th class="p-4">Channel</th>
              <th class="p-4">Views</th>
              <th class="p-4">Engagements</th>
              <th class="p-4 text-right">Engagement Rate</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-slate-300">
            <tr v-for="(metric, idx) in topPosts" :key="metric.id" class="hover:bg-slate-800/30 transition-colors">
              <td class="p-4 max-w-sm">
                <div class="font-bold text-white truncate">{{ metric.post?.title || `Post #${metric.content_post_id}` }}</div>
                <div class="text-[11px] text-slate-400 truncate mt-0.5">{{ metric.post?.hook || metric.post?.caption }}</div>
              </td>
              <td class="p-4">
                <span class="flex items-center gap-1.5 capitalize">
                  {{ getPlatformIcon(metric.post?.primary_platform) }}
                  {{ metric.post?.primary_platform }}
                </span>
              </td>
              <td class="p-4 font-mono">{{ formatNumber(metric.views) }}</td>
              <td class="p-4 font-mono">{{ formatNumber(metric.likes + metric.comments + metric.shares + metric.clicks) }}</td>
              <td class="p-4 text-right">
                <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-bold font-mono">
                  {{ metric.engagement_rate }}%
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { t, currentLocale } from '../i18n';

const props = defineProps<{
  authToken?: string | null;
  organizationId?: number | null;
  brandId?: number | null;
}>();

const loading = ref(false);
const syncing = ref(false);
const actionLoading = ref(false);

const kpis = ref<any>({});
const channels = ref<any[]>([]);
const pillarPerformance = ref<any[]>([]);
const topPosts = ref<any[]>([]);
const recommendations = ref<any[]>([]);

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
    const [overviewRes, pillarsRes, contentRes, recsRes] = await Promise.all([
      fetch('/api/v1/analytics/overview', { headers: getAuthHeaders() }),
      fetch('/api/v1/analytics/pillars', { headers: getAuthHeaders() }),
      fetch('/api/v1/analytics/content', { headers: getAuthHeaders() }),
      fetch('/api/v1/analytics/recommendations', { headers: getAuthHeaders() }),
    ]);

    if (overviewRes.ok) {
      const json = await overviewRes.json();
      kpis.value = json.data?.kpis || {};
      channels.value = json.data?.channels || [];
    }

    if (pillarsRes.ok) {
      const json = await pillarsRes.json();
      pillarPerformance.value = json.data || [];
    }

    if (contentRes.ok) {
      const json = await contentRes.json();
      topPosts.value = json.data || [];
    }

    if (recsRes.ok) {
      const json = await recsRes.json();
      recommendations.value = json.data || [];
    }
  } catch (err) {
    console.error('Failed to load analytics data', err);
  } finally {
    loading.value = false;
  }
}

async function syncAnalytics() {
  if (!props.authToken) return;
  syncing.value = true;

  try {
    const res = await fetch('/api/v1/analytics/sync', {
      method: 'POST',
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      await fetchData();
    }
  } catch (err) {
    console.error('Sync failed', err);
  } finally {
    syncing.value = false;
  }
}

async function applyRecommendation(id: number) {
  if (!props.authToken) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/analytics/recommendations/${id}/apply`, {
      method: 'POST',
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      await fetchData();
    }
  } catch (err) {
    console.error('Apply recommendation failed', err);
  } finally {
    actionLoading.value = false;
  }
}

async function dismissRecommendation(id: number) {
  if (!props.authToken) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/analytics/recommendations/${id}/dismiss`, {
      method: 'POST',
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      await fetchData();
    }
  } catch (err) {
    console.error('Dismiss failed', err);
  } finally {
    actionLoading.value = false;
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

watch(() => props.brandId, () => {
  fetchData();
});

onMounted(() => {
  fetchData();
});
</script>
