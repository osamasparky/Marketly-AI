<template>
  <div class="space-y-6">
    <!-- AI Strategist Header Banner -->
    <div class="p-6 md:p-8 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800 pb-6">
        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-xl font-extrabold text-white flex items-center gap-2.5">
              <span>🎯</span>
              <span>{{ t('strategy.title') }}</span>
            </h2>
            <span v-if="strategy" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase" :class="strategy.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20'">
              {{ strategy.status === 'active' ? t('strategy.activeStrategy') : t('strategy.draftStrategy') }}
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-1 max-w-2xl leading-relaxed">
            {{ t('strategy.subtitle') }}
          </p>
        </div>

        <div class="flex items-center gap-3">
          <!-- Strategy Health Meter -->
          <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 min-w-[200px]">
            <div class="flex items-center justify-between text-xs mb-2">
              <span class="font-bold text-slate-300">{{ t('strategy.healthMeter') }}</span>
              <span class="font-extrabold font-mono text-emerald-400">{{ health.total_score }}%</span>
            </div>
            <div class="w-full h-2.5 rounded-full bg-slate-800 overflow-hidden">
              <div 
                class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-500 rounded-full" 
                :style="{ width: `${health.total_score}%` }"
              ></div>
            </div>
          </div>

          <button @click="showWizard = true" class="tactile-btn tactile-btn-primary text-xs px-4 py-3 whitespace-nowrap">
            ✨ {{ t('strategy.generateBtn') }}
          </button>
        </div>
      </div>

      <!-- Health Pillars Breakdown -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
        <div 
          v-for="(pillar, key) in health.pillars" 
          :key="key"
          class="p-3 rounded-xl border bg-slate-950/40 text-xs space-y-1.5"
          :class="pillar.is_complete ? 'border-emerald-500/30' : 'border-slate-800'"
        >
          <div class="flex items-center justify-between text-[11px]">
            <span class="text-slate-400 truncate">{{ pillar.name }}</span>
            <span :class="pillar.is_complete ? 'text-emerald-400' : 'text-slate-500'">
              {{ pillar.is_complete ? '✓' : '⚠' }}
            </span>
          </div>
          <div class="text-sm font-extrabold font-mono text-white">
            {{ pillar.score }} <span class="text-[10px] text-slate-500 font-normal">/ {{ pillar.max }}</span>
          </div>
          <p class="text-[10px] text-slate-400 truncate">{{ pillar.description }}</p>
        </div>
      </div>
    </div>

    <!-- No Strategy State -->
    <div v-if="!strategy" class="p-12 text-center rounded-3xl bg-slate-900/40 border border-slate-800/80 space-y-4">
      <span class="text-4xl block">📊</span>
      <h3 class="text-base font-bold text-white">{{ t('strategy.noStrategy') }}</h3>
      <button @click="showWizard = true" class="tactile-btn tactile-btn-primary text-xs px-6 py-2.5">
        ✨ {{ t('strategy.generateBtn') }}
      </button>
    </div>

    <!-- Active / Draft Strategy Content View -->
    <div v-else class="space-y-6">
      <!-- Strategy Action Bar -->
      <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h3 class="text-sm font-bold text-white">{{ strategy.name }}</h3>
          <p class="text-xs text-slate-400 mt-0.5">Objective: <span class="text-emerald-400 uppercase font-bold">{{ strategy.primary_objective }}</span> • Version {{ strategy.version }}</p>
        </div>

        <div class="flex items-center gap-2">
          <button 
            v-if="strategy.status === 'draft'" 
            @click="activateStrategy(strategy.id)" 
            :disabled="actionLoading"
            class="tactile-btn tactile-btn-primary text-xs px-4 py-2"
          >
            🚀 {{ t('strategy.activateBtn') }}
          </button>
          <button 
            v-if="strategy.status === 'active'" 
            @click="pauseStrategy(strategy.id)" 
            :disabled="actionLoading"
            class="px-3 py-2 rounded-xl bg-slate-800 text-xs text-slate-300 hover:bg-slate-700"
          >
            ⏸️ {{ t('strategy.pauseBtn') }}
          </button>
        </div>
      </div>

      <!-- Tabbed Navigation -->
      <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 pb-2">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            activeTab === tab.id 
              ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/40 font-bold' 
              : 'text-slate-400 hover:text-slate-200 border-transparent',
            'px-4 py-2 rounded-xl text-xs border transition-colors duration-150 flex items-center gap-2'
          ]"
        >
          <span>{{ tab.icon }}</span>
          <span>{{ t(tab.titleKey) }}</span>
        </button>
      </div>

      <!-- Tab 1: Overview & AI Rationale -->
      <div v-if="activeTab === 'overview'" class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-4">
        <div class="space-y-2">
          <h4 class="text-xs font-bold uppercase text-slate-400">{{ t('strategy.fields.rationale') }}</h4>
          <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/80 text-xs leading-relaxed text-slate-300">
            {{ strategy.rationale || strategy.description }}
          </div>
        </div>
      </div>

      <!-- Tab 2: Content Pillars & Percentage Mix -->
      <div v-if="activeTab === 'pillars'" class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <span>📊</span>
          <span>{{ t('strategy.tabs.pillars') }}</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="pillar in strategy.pillars" :key="pillar.id" class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="font-bold text-xs text-white">{{ pillar.name }}</h4>
              <span class="px-2 py-0.5 text-xs font-mono font-bold rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                {{ pillar.recommended_percentage }}%
              </span>
            </div>
            <p class="text-[11px] text-slate-400 leading-relaxed">{{ pillar.description }}</p>
            <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden">
              <div class="h-full bg-emerald-400 rounded-full" :style="{ width: `${pillar.recommended_percentage}%` }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 3: Campaign Themes -->
      <div v-if="activeTab === 'campaigns'" class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <span>🚀</span>
          <span>{{ t('strategy.tabs.campaigns') }}</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="theme in strategy.campaign_themes" :key="theme.id" class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-2">
            <div class="flex items-center justify-between">
              <h4 class="font-bold text-xs text-white">{{ theme.name }}</h4>
              <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded bg-slate-800 text-slate-400">
                {{ theme.duration_weeks }} WEEKS
              </span>
            </div>
            <p class="text-[11px] text-slate-300 font-medium">"{{ theme.core_message }}"</p>
            <div class="flex flex-wrap gap-1 pt-1">
              <span v-for="fmt in theme.recommended_formats" :key="fmt" class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-emerald-950/40 text-emerald-400 border border-emerald-500/20">
                {{ fmt }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 4: Content Opportunities -->
      <div v-if="activeTab === 'opportunities'" class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <span>💡</span>
          <span>{{ t('strategy.tabs.opportunities') }}</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="opp in strategy.opportunities" :key="opp.id" class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-2">
            <div class="flex items-center justify-between">
              <h4 class="font-bold text-xs text-white">{{ opp.title }}</h4>
              <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded" :class="opp.priority === 'high' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 'bg-slate-800 text-slate-400'">
                {{ opp.priority }}
              </span>
            </div>
            <p class="text-[11px] text-slate-400">{{ opp.description }}</p>
            <div class="text-[10px] text-slate-500 font-mono">
              Timing: {{ opp.recommended_timing || 'Immediate' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Strategy Generation Wizard Modal -->
    <div v-if="showWizard" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full space-y-5 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <span>✨</span>
            <span>{{ t('strategy.wizard.title') }}</span>
          </h3>
          <button @click="showWizard = false" class="text-slate-400 hover:text-white text-xs">✕</button>
        </div>

        <!-- Step 1: Objective -->
        <div class="space-y-2">
          <label class="text-[11px] font-medium text-slate-400">{{ t('strategy.wizard.objectiveLabel') }}</label>
          <select v-model="wizardForm.primary_objective" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
            <option value="lead_generation">Lead Generation (توليد العملاء المحتملين)</option>
            <option value="sales">Direct Sales & Conversions (المبيعات المباشرة)</option>
            <option value="brand_awareness">Brand Awareness & Reach (الانتشار والوعي بالعلامة)</option>
            <option value="engagement">Community & Engagement (التفاعل وبناء المجتمع)</option>
            <option value="education">Education & Thought Leadership (التعليم وبناء الموثوقية)</option>
          </select>
        </div>

        <!-- Step 2: Time Horizon -->
        <div class="space-y-2">
          <label class="text-[11px] font-medium text-slate-400">{{ t('strategy.wizard.timeHorizonLabel') }}: {{ wizardForm.time_horizon_months }} Months</label>
          <input v-model.number="wizardForm.time_horizon_months" type="range" min="1" max="6" class="w-full accent-emerald-400" />
        </div>

        <!-- Step 3: Platforms -->
        <div class="space-y-2">
          <label class="text-[11px] font-medium text-slate-400">{{ t('strategy.wizard.platformsLabel') }}</label>
          <div class="grid grid-cols-3 gap-2 text-xs">
            <label v-for="plat in ['linkedin', 'instagram', 'x', 'tiktok']" :key="plat" class="flex items-center gap-2 p-2 rounded-xl bg-slate-950 border border-slate-800 cursor-pointer">
              <input type="checkbox" :value="plat" v-model="wizardForm.target_platforms" class="accent-emerald-400" />
              <span class="capitalize text-slate-300 text-[11px]">{{ plat }}</span>
            </label>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
          <button @click="showWizard = false" class="px-4 py-2 rounded-xl bg-slate-800 text-xs text-slate-300">{{ t('common.cancel') }}</button>
          <button @click="handleGenerateStrategy" :disabled="generating" class="tactile-btn tactile-btn-primary text-xs px-5 py-2.5">
            {{ generating ? t('common.processing') : t('strategy.wizard.generateAction') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { t, currentLocale } from '../i18n';

const props = defineProps<{
  authToken: string;
  organizationId?: number;
  brandId?: number;
}>();

const activeTab = ref('overview');
const strategy = ref<any>(null);
const health = ref<any>({ total_score: 0, status: 'empty', pillars: {} });
const showWizard = ref(false);
const generating = ref(false);
const actionLoading = ref(false);

const getHeaders = () => ({
  Authorization: `Bearer ${props.authToken}`,
  'X-Locale': currentLocale.value,
  ...(props.organizationId ? { 'X-Organization-Id': String(props.organizationId) } : {}),
  ...(props.brandId ? { 'X-Brand-Id': String(props.brandId) } : {}),
});

const wizardForm = ref({
  primary_objective: 'lead_generation',
  time_horizon_months: 3,
  target_platforms: ['linkedin', 'instagram'],
});

const tabs = [
  { id: 'overview', titleKey: 'strategy.tabs.overview', icon: '🧠' },
  { id: 'pillars', titleKey: 'strategy.tabs.pillars', icon: '📊' },
  { id: 'campaigns', titleKey: 'strategy.tabs.campaigns', icon: '🚀' },
  { id: 'opportunities', titleKey: 'strategy.tabs.opportunities', icon: '💡' },
];

const fetchStrategy = async () => {
  if (!props.authToken) return;
  try {
    const res = await axios.get('/api/v1/strategy', {
      headers: getHeaders(),
    });
    strategy.value = res.data?.data?.strategy;
    health.value = res.data?.data?.health || { total_score: 0, status: 'empty', pillars: {} };
  } catch (err) {
    // Handle error
  }
};

const handleGenerateStrategy = async () => {
  generating.value = true;
  try {
    const res = await axios.post('/api/v1/strategy/generate', wizardForm.value, {
      headers: getHeaders(),
    });
    showWizard.value = false;
    await fetchStrategy();
  } catch (err: any) {
    alert(err.response?.data?.message || err.message || 'Strategy generation failed.');
  } finally {
    generating.value = false;
  }
};

const activateStrategy = async (id: number) => {
  actionLoading.value = true;
  try {
    await axios.post(`/api/v1/strategy/${id}/activate`, {}, {
      headers: getHeaders(),
    });
    await fetchStrategy();
  } catch (err) {
    // Handle error
  } finally {
    actionLoading.value = false;
  }
};

const pauseStrategy = async (id: number) => {
  actionLoading.value = true;
  try {
    await axios.post(`/api/v1/strategy/${id}/pause`, {}, {
      headers: getHeaders(),
    });
    await fetchStrategy();
  } catch (err) {
    // Handle error
  } finally {
    actionLoading.value = false;
  }
};

watch(() => props.brandId, () => {
  fetchStrategy();
});

onMounted(() => {
  fetchStrategy();
});
</script>
