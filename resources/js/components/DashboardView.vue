<template>
  <div class="space-y-8">
    <!-- Welcome Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-950/60 via-slate-900 to-slate-900 p-6 md:p-8 border border-emerald-500/20 shadow-2xl">
      <div class="relative z-10 max-w-3xl space-y-3">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold">
          <span>✨</span>
          <span>{{ currentOrg?.name || 'Workspace' }} • {{ t('dashboard.phaseBadge') }}</span>
        </div>
        <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">
          {{ t('dashboard.welcome') }}
        </h1>
        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
          {{ t('dashboard.welcomeDesc') }}
        </p>
      </div>

      <!-- Quick Action Buttons -->
      <div class="relative z-10 pt-6 flex flex-wrap items-center gap-3">
        <button 
          @click="$emit('navigate', 'brand_brain')" 
          class="tactile-btn tactile-btn-primary text-xs px-4 py-2 flex items-center gap-1.5"
        >
          <span>🧠</span>
          <span>{{ t('navigation.brandBrain') }}</span>
        </button>
        <button 
          @click="$emit('navigate', 'strategy')" 
          class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white border border-slate-700 transition-all flex items-center gap-1.5"
        >
          <span>🎯</span>
          <span>{{ t('navigation.strategy') }}</span>
        </button>
        <button 
          @click="$emit('navigate', 'billing')" 
          class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-xs font-bold text-slate-300 border border-slate-800 transition-all flex items-center gap-1.5"
        >
          <span>💳</span>
          <span>{{ t('navigation.billing') }}</span>
        </button>
        <button 
          @click="$emit('start-onboarding')" 
          class="px-4 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold hover:bg-emerald-500/20 transition-all"
        >
          <span>🚀</span>
          <span>Setup Wizard</span>
        </button>
      </div>
    </div>

    <!-- Live Performance & Intelligence Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- 1. Brand Brain Completeness -->
      <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4 flex flex-col justify-between">
        <div class="space-y-1">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">{{ t('brandBrain.healthMeter') }}</div>
          <div class="flex items-center justify-between">
            <span class="text-3xl font-black text-white">{{ completenessScore }}%</span>
            <span :class="[completenessScore >= 80 ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-amber-500/10 text-amber-400 border-amber-500/30']" class="px-2.5 py-0.5 rounded-full border text-[10px] font-bold uppercase">
              {{ completenessScore >= 80 ? 'Optimal' : 'Needs Info' }}
            </span>
          </div>
        </div>
        <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
          <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" :style="{ width: `${completenessScore}%` }"></div>
        </div>
        <button @click="$emit('navigate', 'brand_brain')" class="text-xs text-emerald-400 font-bold hover:text-emerald-300 text-left">
          Manage Brand Brain →
        </button>
      </div>

      <!-- 2. Marketing Strategy Status -->
      <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4 flex flex-col justify-between">
        <div class="space-y-1">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">{{ t('strategy.title') }}</div>
          <div class="flex items-center justify-between">
            <span class="text-xl font-bold text-white truncate max-w-[160px]">
              {{ activeStrategy?.name || 'No Active Strategy' }}
            </span>
            <span v-if="activeStrategy" class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold uppercase">
              Active v{{ activeStrategy.version }}
            </span>
          </div>
          <p class="text-[11px] text-slate-400 truncate">
            {{ activeStrategy ? `Goal: ${activeStrategy.primary_objective}` : 'Synthesize quarterly pillars & themes' }}
          </p>
        </div>
        <button @click="$emit('navigate', 'strategy')" class="text-xs text-emerald-400 font-bold hover:text-emerald-300 text-left">
          Open AI Strategist →
        </button>
      </div>

      <!-- 3. Subscription & Quota Status -->
      <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4 flex flex-col justify-between">
        <div class="space-y-1">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">{{ t('billing.currentPlan') }}</div>
          <div class="flex items-center justify-between">
            <span class="text-2xl font-black text-white">{{ subscription?.plan?.name || 'Starter' }}</span>
            <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold uppercase">
              {{ subscription?.status || 'Trialing' }}
            </span>
          </div>
          <p class="text-[11px] text-slate-400">
            {{ trialDaysLeft }} days remaining in trial
          </p>
        </div>
        <button @click="$emit('navigate', 'billing')" class="text-xs text-emerald-400 font-bold hover:text-emerald-300 text-left">
          View Quotas & Upgrades →
        </button>
      </div>
    </div>

    <!-- Upcoming Autonomous Modules Grid (Roadmap) -->
    <div class="space-y-4">
      <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
        <span>🚀</span>
        <span>Marketing Autopilot Pipeline</span>
      </h3>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <!-- Content Studio -->
        <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 space-y-3 relative overflow-hidden">
          <div class="flex items-center justify-between">
            <span class="text-sm font-bold text-white">Content Studio</span>
            <span class="px-2 py-0.5 rounded-full bg-amber-400/10 text-amber-400 border border-amber-400/30 text-[10px] font-bold">Phase 4 Ready</span>
          </div>
          <p class="text-xs text-slate-400 leading-relaxed">Multi-platform copy generator crafting hooks, carousels, and localized dialect captions.</p>
        </div>

        <!-- Social Publishing -->
        <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 space-y-3 relative overflow-hidden">
          <div class="flex items-center justify-between">
            <span class="text-sm font-bold text-white">Social Publishing</span>
            <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 border border-slate-700 text-[10px] font-bold">Phase 5 (Roadmap)</span>
          </div>
          <p class="text-xs text-slate-400 leading-relaxed">Direct social network scheduling to LinkedIn, Instagram, TikTok, and X.</p>
        </div>

        <!-- Analytics & ROI -->
        <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 space-y-3 relative overflow-hidden">
          <div class="flex items-center justify-between">
            <span class="text-sm font-bold text-white">Analytics & ROI</span>
            <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 border border-slate-700 text-[10px] font-bold">Phase 6 (Roadmap)</span>
          </div>
          <p class="text-xs text-slate-400 leading-relaxed">Comprehensive audience insights, conversion tracking, and closed-loop strategy tuning.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { t } from '../i18n';

const props = defineProps<{
  authToken: string;
  organizationId: number;
  currentOrg: any;
}>();

defineEmits(['navigate', 'start-onboarding']);

const completenessScore = ref(0);
const activeStrategy = ref<any>(null);
const subscription = ref<any>(null);
const trialDaysLeft = ref(14);

const getHeaders = () => ({
  Authorization: `Bearer ${props.authToken}`,
  'X-Organization-Id': String(props.organizationId),
});

const loadDashboardData = async () => {
  try {
    // 1. Load Brand completeness
    const brandRes = await axios.get('/api/v1/brand', { headers: getHeaders() });
    completenessScore.value = brandRes.data?.data?.completeness?.total_score ?? 0;

    // 2. Load Strategy
    const stratRes = await axios.get('/api/v1/strategy', { headers: getHeaders() });
    activeStrategy.value = stratRes.data?.data?.strategy;

    // 3. Load Billing
    const billRes = await axios.get('/api/v1/billing/subscription', { headers: getHeaders() });
    subscription.value = billRes.data?.data?.subscription;
    trialDaysLeft.value = billRes.data?.data?.trial_remaining_days ?? 14;
  } catch (err) {
    console.error('Failed to load dashboard data', err);
  }
};

onMounted(() => {
  loadDashboardData();
});
</script>
