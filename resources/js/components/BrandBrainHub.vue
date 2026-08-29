<template>
  <div class="space-y-6">
    <!-- Brand Brain Header Banner -->
    <div class="p-6 md:p-8 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800 pb-6">
        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-xl font-extrabold text-white flex items-center gap-2.5">
              <span>🧠</span>
              <span>{{ t('brandBrain.title') }}</span>
            </h2>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
              {{ completeness.status }}
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-1 max-w-2xl leading-relaxed">
            {{ t('brandBrain.subtitle') }}
          </p>
        </div>

        <!-- Completeness Health Meter -->
        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 min-w-[220px]">
          <div class="flex items-center justify-between text-xs mb-2">
            <span class="font-bold text-slate-300">{{ t('brandBrain.completeness') }}</span>
            <span class="font-extrabold font-mono text-emerald-400">{{ completeness.total_score }}%</span>
          </div>
          <div class="w-full h-2.5 rounded-full bg-slate-800 overflow-hidden">
            <div 
              class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-500 rounded-full" 
              :style="{ width: `${completeness.total_score}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Pillar Breakdown Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
        <div 
          v-for="(pillar, key) in completeness.pillars" 
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
        </div>
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

    <!-- Tab 1: Profile & Identity -->
    <div v-if="activeTab === 'identity'" class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
      <h3 class="text-sm font-bold text-white flex items-center gap-2">
        <span>🏢</span>
        <span>{{ t('brandBrain.tabs.identity') }}</span>
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.businessName') }}</label>
          <input v-model="profileForm.business_name" type="text" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="Acme Inc." />
        </div>
        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.industry') }}</label>
          <input v-model="profileForm.industry" type="text" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="Technology / SaaS" />
        </div>
        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.businessType') }}</label>
          <select v-model="profileForm.business_type" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
            <option value="B2B">B2B (Business to Business)</option>
            <option value="B2C">B2C (Business to Consumer)</option>
            <option value="D2C">D2C (Direct to Consumer)</option>
            <option value="Agency">Agency / Service</option>
          </select>
        </div>
        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.website') }}</label>
          <input v-model="profileForm.website" type="text" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="https://brand.ai" />
        </div>
        <div class="col-span-1 md:col-span-2 space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.description') }}</label>
          <textarea v-model="profileForm.description" rows="3" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="Describe the core business offering, value proposition, and customer transformation..."></textarea>
        </div>
        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.tagline') }}</label>
          <input v-model="profileForm.tagline" type="text" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="AI Marketing on Autopilot" />
        </div>
        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.positioning') }}</label>
          <input v-model="profileForm.positioning" type="text" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="Premium autonomous solution for growing brands." />
        </div>
      </div>

      <div class="flex justify-end pt-2">
        <button @click="saveProfile" :disabled="saving" class="tactile-btn tactile-btn-primary text-xs px-5 py-2.5">
          {{ saving ? t('common.processing') : t('brandBrain.buttons.saveProfile') }}
        </button>
      </div>
    </div>

    <!-- Tab 2: Products & Services -->
    <div v-if="activeTab === 'products'" class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
      <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <span>📦</span>
          <span>{{ t('brandBrain.tabs.products') }}</span>
        </h3>
        <button @click="showProductModal = true" class="px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold hover:bg-emerald-500/20">
          + {{ t('brandBrain.buttons.addProduct') }}
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="prod in products" :key="prod.id" class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 flex items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <h4 class="font-bold text-xs text-white">{{ prod.name }}</h4>
              <span class="px-2 py-0.5 text-[9px] font-bold rounded uppercase bg-slate-800 text-slate-400">{{ prod.type }}</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">{{ prod.description || 'No description provided.' }}</p>
            <div v-if="prod.price" class="mt-2 text-xs font-mono text-emerald-400 font-bold">
              {{ prod.price }} {{ prod.currency }}
            </div>
          </div>
          <button @click="deleteProduct(prod.id)" class="text-slate-500 hover:text-red-400 text-xs">🗑️</button>
        </div>
      </div>
    </div>

    <!-- Tab 3: Target Audience -->
    <div v-if="activeTab === 'audience'" class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
      <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <span>🎯</span>
          <span>{{ t('brandBrain.tabs.audience') }}</span>
        </h3>
        <button @click="showAudienceModal = true" class="px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold hover:bg-emerald-500/20">
          + {{ t('brandBrain.buttons.addAudience') }}
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="aud in audiences" :key="aud.id" class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 flex items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <h4 class="font-bold text-xs text-white">{{ aud.name }}</h4>
              <span class="px-2 py-0.5 text-[9px] font-bold rounded uppercase bg-slate-800 text-slate-400">{{ aud.type }}</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">{{ aud.description || 'No description provided.' }}</p>
          </div>
          <button @click="deleteAudience(aud.id)" class="text-slate-500 hover:text-red-400 text-xs">🗑️</button>
        </div>
      </div>
    </div>

    <!-- Tab 4: Brand Voice & Tone -->
    <div v-if="activeTab === 'voice'" class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
      <h3 class="text-sm font-bold text-white flex items-center gap-2">
        <span>🎙️</span>
        <span>{{ t('brandBrain.tabs.voice') }}</span>
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.dialect') }}</label>
          <select v-model="voiceForm.dialect" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
            <option value="saudi">Saudi Arabic (اللهجة السعودية الحجازية / النجدية)</option>
            <option value="gulf">Gulf Arabic (اللهجة الخليجية العامة)</option>
            <option value="egyptian">Egyptian Arabic (اللهجة المصرية)</option>
            <option value="msa">Modern Standard Arabic (الفصحى المعاصرة)</option>
            <option value="english_us">American English (US Professional)</option>
            <option value="english_uk">British English (UK Refined)</option>
          </select>
        </div>

        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.emojiStyle') }}</label>
          <select v-model="voiceForm.emoji_style" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
            <option value="none">None (No Emojis)</option>
            <option value="minimal">Minimal (1-2 per post)</option>
            <option value="moderate">Moderate (Balanced)</option>
            <option value="expressive">Expressive (High engagement)</option>
          </select>
        </div>

        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.formality') }}: {{ voiceForm.formality_scale }} / 5</label>
          <input v-model.number="voiceForm.formality_scale" type="range" min="1" max="5" class="w-full accent-emerald-400" />
        </div>
      </div>

      <div class="flex justify-end pt-2">
        <button @click="saveVoice" :disabled="saving" class="tactile-btn tactile-btn-primary text-xs px-5 py-2.5">
          {{ saving ? t('common.processing') : t('brandBrain.buttons.saveVoice') }}
        </button>
      </div>
    </div>

    <!-- Tab 5: AI Context Inspector -->
    <div v-if="activeTab === 'aiContext'" class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-4">
      <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <div>
          <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <span>🛡️</span>
            <span>{{ t('brandBrain.aiContextBadge') }}</span>
          </h3>
          <p class="text-xs text-slate-400 mt-1">{{ t('brandBrain.aiContextDesc') }}</p>
        </div>
        <button @click="fetchAiContext" class="tactile-btn tactile-btn-primary text-xs px-3 py-1.5">
          {{ t('brandBrain.buttons.previewAiContext') }}
        </button>
      </div>

      <pre class="w-full h-80 p-4 rounded-2xl bg-slate-950 border border-slate-800 text-xs font-mono text-emerald-400 overflow-y-auto">{{ aiContextData || '// Click Preview to load sanitized AI context...' }}</pre>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { t, currentLocale } from '../i18n';

const props = defineProps<{
  authToken: string;
  organizationId?: number;
}>();

const activeTab = ref('identity');
const saving = ref(false);
const completeness = ref<any>({ total_score: 0, status: 'empty', pillars: {} });
const products = ref<any[]>([]);
const audiences = ref<any[]>([]);
const aiContextData = ref<string>('');

const showProductModal = ref(false);
const showAudienceModal = ref(false);

const profileForm = ref({
  business_name: '',
  industry: 'Technology',
  business_type: 'B2B',
  description: '',
  website: '',
  tagline: '',
  positioning: '',
});

const voiceForm = ref({
  dialect: 'saudi',
  emoji_style: 'moderate',
  formality_scale: 3,
});

const tabs = [
  { id: 'identity', titleKey: 'brandBrain.tabs.identity', icon: '🏢' },
  { id: 'products', titleKey: 'brandBrain.tabs.products', icon: '📦' },
  { id: 'audience', titleKey: 'brandBrain.tabs.audience', icon: '🎯' },
  { id: 'voice', titleKey: 'brandBrain.tabs.voice', icon: '🎙️' },
  { id: 'aiContext', titleKey: 'brandBrain.tabs.aiContext', icon: '🛡️' },
];

const fetchBrandBrain = async () => {
  if (!props.authToken) return;
  try {
    const res = await axios.get('/api/v1/brand', {
      headers: {
        Authorization: `Bearer ${props.authToken}`,
        'X-Locale': currentLocale.value,
        ...(props.organizationId ? { 'X-Organization-Id': String(props.organizationId) } : {}),
      },
    });

    const data = res.data?.data;
    if (data) {
      completeness.value = data.completeness || { total_score: 0, status: 'empty', pillars: {} };
      if (data.profile) {
        profileForm.value = {
          business_name: data.profile.business_name || '',
          industry: data.profile.industry || 'Technology',
          business_type: data.profile.business_type || 'B2B',
          description: data.profile.description || '',
          website: data.profile.website || '',
          tagline: data.profile.tagline || '',
          positioning: data.profile.positioning || '',
        };
        products.value = data.profile.products_services || [];
        audiences.value = data.profile.audiences || [];
        if (data.profile.voice) {
          voiceForm.value = {
            dialect: data.profile.voice.dialect || 'saudi',
            emoji_style: data.profile.voice.emoji_style || 'moderate',
            formality_scale: data.profile.voice.formality_scale || 3,
          };
        }
      }
    }
  } catch (err) {
    // Handle error
  }
};

const saveProfile = async () => {
  saving.value = true;
  try {
    await axios.post('/api/v1/brand', profileForm.value, {
      headers: {
        Authorization: `Bearer ${props.authToken}`,
        'X-Locale': currentLocale.value,
        ...(props.organizationId ? { 'X-Organization-Id': String(props.organizationId) } : {}),
      },
    });
    await fetchBrandBrain();
  } catch (err) {
    // Handle error
  } finally {
    saving.value = false;
  }
};

const saveVoice = async () => {
  saving.value = true;
  try {
    await axios.patch('/api/v1/brand/voice', voiceForm.value, {
      headers: {
        Authorization: `Bearer ${props.authToken}`,
        'X-Locale': currentLocale.value,
        ...(props.organizationId ? { 'X-Organization-Id': String(props.organizationId) } : {}),
      },
    });
    await fetchBrandBrain();
  } catch (err) {
    // Handle error
  } finally {
    saving.value = false;
  }
};

const deleteProduct = async (id: number) => {
  try {
    await axios.delete(`/api/v1/brand/products/${id}`, {
      headers: {
        Authorization: `Bearer ${props.authToken}`,
        'X-Locale': currentLocale.value,
        ...(props.organizationId ? { 'X-Organization-Id': String(props.organizationId) } : {}),
      },
    });
    await fetchBrandBrain();
  } catch (err) {
    // Handle error
  }
};

const deleteAudience = async (id: number) => {
  try {
    await axios.delete(`/api/v1/brand/audiences/${id}`, {
      headers: {
        Authorization: `Bearer ${props.authToken}`,
        'X-Locale': currentLocale.value,
        ...(props.organizationId ? { 'X-Organization-Id': String(props.organizationId) } : {}),
      },
    });
    await fetchBrandBrain();
  } catch (err) {
    // Handle error
  }
};

const fetchAiContext = async () => {
  try {
    const res = await axios.get('/api/v1/brand/ai-context?task=content_generation', {
      headers: {
        Authorization: `Bearer ${props.authToken}`,
        'X-Locale': currentLocale.value,
        ...(props.organizationId ? { 'X-Organization-Id': String(props.organizationId) } : {}),
      },
    });
    aiContextData.value = JSON.stringify(res.data?.data, null, 2);
  } catch (err: any) {
    aiContextData.value = JSON.stringify(err.response?.data || { error: err.message }, null, 2);
  }
};

onMounted(() => {
  fetchBrandBrain();
});
</script>
