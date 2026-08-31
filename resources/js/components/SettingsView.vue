<template>
  <div class="max-w-4xl mx-auto space-y-8">
    <div>
      <h2 class="text-2xl font-extrabold text-white tracking-tight">{{ t('navigation.settings') }}</h2>
      <p class="text-xs text-slate-400 mt-1">Configure your organization preferences, BYOK AI model keys, and company details.</p>
    </div>

    <!-- AI Model Providers & BYOK API Keys Card -->
    <div class="p-6 sm:p-8 rounded-3xl bg-slate-900 border border-slate-800 space-y-6 shadow-2xl backdrop-blur-xl">
      <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-xl text-purple-400">
            🤖
          </div>
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              {{ t('aiSettings.title') }}
              <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-purple-500/10 text-purple-300 border border-purple-500/20">
                BYOK Encrypted
              </span>
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">{{ t('aiSettings.subtitle') }}</p>
          </div>
        </div>
      </div>

      <!-- Primary LLM Engine Picker -->
      <div class="space-y-1.5">
        <label class="text-xs font-semibold text-slate-300 flex items-center gap-1.5">
          <span>🧠</span> {{ t('aiSettings.preferredModel') }}
        </label>
        <select v-model="aiForm.preferred_model" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-purple-500 outline-none">
          <option value="gemini-1.5-pro">Google Gemini 1.5 Pro (Recommended for Strategy & Multilingual)</option>
          <option value="gemini-1.5-flash">Google Gemini 1.5 Flash (Ultra Fast)</option>
          <option value="gpt-4o">OpenAI GPT-4o (Omni Reasoning)</option>
          <option value="gpt-4o-mini">OpenAI GPT-4o Mini (Cost Efficient)</option>
          <option value="claude-3-5-sonnet">Anthropic Claude 3.5 Sonnet (Advanced Copywriting)</option>
          <option value="deepseek-chat">DeepSeek Chat (High Speed Coding & Analysis)</option>
        </select>
      </div>

      <!-- API Key Inputs Grid -->
      <div class="grid sm:grid-cols-2 gap-4 pt-2">
        <!-- Gemini Key -->
        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <label class="text-xs font-semibold text-slate-300 flex items-center gap-1">
              <span>✨</span> {{ t('aiSettings.geminiKey') }}
            </label>
            <span v-if="aiConfigMasked.gemini_api_key_configured" class="text-[10px] font-bold text-emerald-400">
              Configured ({{ aiConfigMasked.gemini_api_key_preview }})
            </span>
          </div>
          <div class="relative">
            <input 
              v-model="aiForm.gemini_api_key" 
              :type="showKeys.gemini ? 'text' : 'password'" 
              placeholder="AIzaSy..." 
              class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-mono placeholder-slate-600 focus:border-purple-500 outline-none" 
            />
            <button type="button" @click="showKeys.gemini = !showKeys.gemini" class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs text-slate-400">
              {{ showKeys.gemini ? '🙈' : '👁️' }}
            </button>
          </div>
        </div>

        <!-- OpenAI Key -->
        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <label class="text-xs font-semibold text-slate-300 flex items-center gap-1">
              <span>🟢</span> {{ t('aiSettings.openaiKey') }}
            </label>
            <span v-if="aiConfigMasked.openai_api_key_configured" class="text-[10px] font-bold text-emerald-400">
              Configured ({{ aiConfigMasked.openai_api_key_preview }})
            </span>
          </div>
          <div class="relative">
            <input 
              v-model="aiForm.openai_api_key" 
              :type="showKeys.openai ? 'text' : 'password'" 
              placeholder="sk-proj-..." 
              class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-mono placeholder-slate-600 focus:border-purple-500 outline-none" 
            />
            <button type="button" @click="showKeys.openai = !showKeys.openai" class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs text-slate-400">
              {{ showKeys.openai ? '🙈' : '👁️' }}
            </button>
          </div>
        </div>

        <!-- Claude Key -->
        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <label class="text-xs font-semibold text-slate-300 flex items-center gap-1">
              <span>🟣</span> {{ t('aiSettings.claudeKey') }}
            </label>
            <span v-if="aiConfigMasked.anthropic_api_key_configured" class="text-[10px] font-bold text-emerald-400">
              Configured ({{ aiConfigMasked.anthropic_api_key_preview }})
            </span>
          </div>
          <div class="relative">
            <input 
              v-model="aiForm.anthropic_api_key" 
              :type="showKeys.claude ? 'text' : 'password'" 
              placeholder="sk-ant-api..." 
              class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-mono placeholder-slate-600 focus:border-purple-500 outline-none" 
            />
            <button type="button" @click="showKeys.claude = !showKeys.claude" class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs text-slate-400">
              {{ showKeys.claude ? '🙈' : '👁️' }}
            </button>
          </div>
        </div>

        <!-- DeepSeek Key -->
        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <label class="text-xs font-semibold text-slate-300 flex items-center gap-1">
              <span>🔵</span> {{ t('aiSettings.deepseekKey') }}
            </label>
            <span v-if="aiConfigMasked.deepseek_api_key_configured" class="text-[10px] font-bold text-emerald-400">
              Configured ({{ aiConfigMasked.deepseek_api_key_preview }})
            </span>
          </div>
          <div class="relative">
            <input 
              v-model="aiForm.deepseek_api_key" 
              :type="showKeys.deepseek ? 'text' : 'password'" 
              placeholder="sk-deepseek-..." 
              class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-mono placeholder-slate-600 focus:border-purple-500 outline-none" 
            />
            <button type="button" @click="showKeys.deepseek = !showKeys.deepseek" class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs text-slate-400">
              {{ showKeys.deepseek ? '🙈' : '👁️' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Custom AI Directives -->
      <div class="space-y-1.5 pt-2">
        <label class="text-xs font-semibold text-slate-300 flex items-center gap-1.5">
          <span>📝</span> {{ t('aiSettings.customInstructions') }}
        </label>
        <textarea 
          v-model="aiForm.custom_instructions" 
          rows="3" 
          placeholder="e.g. Always emphasize modern tech innovations and avoid overly informal jargon in Arabic and English." 
          class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-purple-500 outline-none"
        ></textarea>
      </div>

      <!-- Company Profile Details Grid -->
      <div class="border-t border-slate-800/80 pt-4 space-y-4">
        <h4 class="text-xs font-bold text-slate-200 uppercase tracking-wider">Company Profile & Contact</h4>
        <div class="grid sm:grid-cols-3 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-300">{{ t('aiSettings.websiteUrl') }}</label>
            <input v-model="aiForm.website_url" type="url" placeholder="https://example.com" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" />
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-300">{{ t('aiSettings.industry') }}</label>
            <input v-model="aiForm.industry" type="text" placeholder="e.g. Real Estate, SaaS" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" />
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-300">{{ t('aiSettings.billingEmail') }}</label>
            <input v-model="aiForm.billing_email" type="email" placeholder="finance@example.com" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" />
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between pt-4 border-t border-slate-800/80">
        <span class="text-[11px] text-slate-400">{{ t('aiSettings.keysMaskedNotice') }}</span>
        <button @click="handleSaveAiConfig" :disabled="savingAi" class="tactile-btn tactile-btn-primary text-xs px-6 py-2.5 flex items-center gap-2">
          <span v-if="savingAi" class="animate-spin">⏳</span>
          <span v-else>💾</span>
          {{ t('aiSettings.saveBtn') }}
        </button>
      </div>
    </div>

    <!-- Organization Workspace Card -->
    <div class="p-6 sm:p-8 rounded-3xl bg-slate-900 border border-slate-800 space-y-6 shadow-2xl">
      <h3 class="text-base font-bold text-white flex items-center gap-2">
        <span>🏢</span>
        <span>Organization Workspace Details</span>
      </h3>

      <div class="grid sm:grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('tenancy.orgName') }}</label>
          <input v-model="orgForm.name" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">Timezone</label>
          <select v-model="orgForm.timezone" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
            <option value="Asia/Riyadh">Asia/Riyadh (GMT+3)</option>
            <option value="Asia/Dubai">Asia/Dubai (GMT+4)</option>
            <option value="Africa/Cairo">Africa/Cairo (GMT+2)</option>
            <option value="Europe/London">Europe/London (GMT+0)</option>
            <option value="America/New_York">America/New_York (EST)</option>
          </select>
        </div>
      </div>

      <div class="flex justify-end pt-2">
        <button @click="handleSaveOrg" class="tactile-btn tactile-btn-primary text-xs px-6 py-2.5">
          {{ t('common.save') }}
        </button>
      </div>
    </div>

    <!-- User Account Card -->
    <div class="p-6 sm:p-8 rounded-3xl bg-slate-900 border border-slate-800 space-y-6 shadow-2xl">
      <h3 class="text-base font-bold text-white flex items-center gap-2">
        <span>👤</span>
        <span>User Profile & Session</span>
      </h3>

      <div class="grid sm:grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('dashboard.fullName') }}</label>
          <input :value="currentUser?.name" readonly type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/60 border border-slate-800 text-xs text-slate-400 cursor-not-allowed" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('dashboard.email') }}</label>
          <input :value="currentUser?.email" readonly type="email" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/60 border border-slate-800 text-xs text-slate-400 cursor-not-allowed" />
        </div>
      </div>

      <div class="flex items-center justify-between pt-4 border-t border-slate-800">
        <span class="text-xs text-slate-500 font-mono">User ID: {{ currentUser?.id }} • {{ currentUser?.is_super_admin ? '👑 Super Admin' : 'Tenant Member' }}</span>
        <button @click="$emit('logout')" class="px-4 py-2 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500/20 text-xs font-bold transition-all">
          {{ t('common.logout') }}
        </button>
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
  currentUser: any;
}>();

const emit = defineEmits(['logout', 'org-updated']);

const orgForm = ref({
  name: '',
  timezone: 'Asia/Riyadh',
});

const aiForm = ref({
  preferred_model: 'gemini-1.5-pro',
  gemini_api_key: '',
  openai_api_key: '',
  anthropic_api_key: '',
  deepseek_api_key: '',
  custom_instructions: '',
  website_url: '',
  industry: '',
  billing_email: '',
});

const aiConfigMasked = ref<any>({});
const showKeys = ref({
  gemini: false,
  openai: false,
  claude: false,
  deepseek: false,
});

const savingAi = ref(false);

const getHeaders = () => ({
  Authorization: `Bearer ${props.authToken}`,
  'X-Organization-Id': String(props.organizationId),
});

const loadOrgData = async () => {
  try {
    const res = await axios.get(`/api/v1/organizations/${props.organizationId}`, { headers: getHeaders() });
    const org = res.data?.data?.organization;
    if (org) {
      orgForm.value.name = org.name;
      orgForm.value.timezone = org.timezone || 'Asia/Riyadh';
    }
  } catch (err) {
    console.error('Failed to load organization', err);
  }
};

const loadAiConfig = async () => {
  try {
    const res = await axios.get(`/api/v1/organizations/${props.organizationId}/ai-config`, { headers: getHeaders() });
    const data = res.data?.data;
    if (data) {
      aiConfigMasked.value = data.ai_config || {};
      aiForm.value.preferred_model = data.ai_config?.preferred_model || 'gemini-1.5-pro';
      aiForm.value.custom_instructions = data.ai_config?.custom_instructions || '';
      if (data.organization) {
        aiForm.value.website_url = data.organization.website_url || '';
        aiForm.value.industry = data.organization.industry || '';
        aiForm.value.billing_email = data.organization.billing_email || '';
      }
    }
  } catch (err) {
    console.error('Failed to load AI config', err);
  }
};

const handleSaveAiConfig = async () => {
  savingAi.value = true;
  try {
    const res = await axios.patch(`/api/v1/organizations/${props.organizationId}/ai-config`, aiForm.value, { headers: getHeaders() });
    alert('AI model settings & company profile saved successfully.');
    await loadAiConfig();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to update AI settings.');
  } finally {
    savingAi.value = false;
  }
};

const handleSaveOrg = async () => {
  try {
    const res = await axios.patch(`/api/v1/organizations/${props.organizationId}`, orgForm.value, { headers: getHeaders() });
    alert('Organization settings updated successfully.');
    emit('org-updated', res.data?.data?.organization);
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to update organization.');
  }
};

onMounted(() => {
  loadOrgData();
  loadAiConfig();
});
</script>
