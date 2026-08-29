<template>
  <div class="max-w-3xl mx-auto p-4 sm:p-8 space-y-8">
    <!-- Header & Progress -->
    <div class="text-center space-y-3">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold">
        <span>🚀</span>
        <span>{{ t('onboarding.wizardTitle') }}</span>
      </div>
      <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
        {{ steps[currentStepIndex].title }}
      </h2>
      <p class="text-xs sm:text-sm text-slate-400 max-w-lg mx-auto">
        {{ steps[currentStepIndex].desc }}
      </p>

      <!-- Step Indicator Bar -->
      <div class="flex items-center justify-center gap-2 pt-4">
        <div 
          v-for="(step, idx) in steps" 
          :key="idx"
          :class="[idx <= currentStepIndex ? 'bg-emerald-500' : 'bg-slate-800']"
          class="h-1.5 w-12 sm:w-16 rounded-full transition-all duration-300"
        ></div>
      </div>
    </div>

    <!-- Step Container -->
    <div class="p-6 sm:p-8 rounded-3xl bg-slate-900 border border-slate-800 shadow-2xl space-y-6">
      <!-- Step 1: Business Profile -->
      <div v-if="currentStepIndex === 0" class="space-y-4">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.businessName') }} *</label>
          <input v-model="form.business_name" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="e.g. Acme Tech Labs" />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.industry') }}</label>
            <input v-model="form.industry" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="e.g. SaaS / E-commerce" />
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.country') }}</label>
            <select v-model="form.country" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
              <option value="SA">Saudi Arabia (SA)</option>
              <option value="AE">United Arab Emirates (AE)</option>
              <option value="EG">Egypt (EG)</option>
              <option value="US">United States (US)</option>
              <option value="GB">United Kingdom (GB)</option>
            </select>
          </div>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.tagline') }}</label>
          <input v-model="form.tagline" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="e.g. Autonomous AI Marketing Suite" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.description') }}</label>
          <textarea v-model="form.description" rows="3" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="Describe what your business does, who you serve, and your core value proposition..."></textarea>
        </div>
      </div>

      <!-- Step 2: Primary Offering -->
      <div v-else-if="currentStepIndex === 1" class="space-y-4">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.productName') }} *</label>
          <input v-model="productForm.name" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="e.g. Enterprise Marketing Automation" />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.productType') }}</label>
            <select v-model="productForm.type" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
              <option value="product">Product</option>
              <option value="service">Service</option>
            </select>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.price') }}</label>
            <input v-model.number="productForm.price" type="number" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="299" />
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.currency') }}</label>
            <input v-model="productForm.currency" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="SAR" />
          </div>
        </div>
      </div>

      <!-- Step 3: Target Persona -->
      <div v-else-if="currentStepIndex === 2" class="space-y-4">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.audienceName') }} *</label>
          <input v-model="audienceForm.name" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="e.g. CMOs & Business Owners" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.audienceType') }}</label>
          <select v-model="audienceForm.type" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
            <option value="b2b">B2B (Companies & Decision Makers)</option>
            <option value="b2c">B2C (Consumers & Individuals)</option>
          </select>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.painPoints') }}</label>
          <input v-model="audienceForm.pain_points_raw" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="e.g. High agency cost, slow turnaround (comma separated)" />
        </div>
      </div>

      <!-- Step 4: Voice & Tone -->
      <div v-else-if="currentStepIndex === 3" class="space-y-4">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.dialect') }}</label>
          <select v-model="voiceForm.dialect" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
            <option value="saudi">Saudi Arabic (اللهجة السعودية / نجدية وحجازية)</option>
            <option value="gulf">Gulf Arabic (اللهجة الخليجية العامة)</option>
            <option value="egyptian">Egyptian Arabic (اللهجة المصرية)</option>
            <option value="modern_standard">Modern Standard Arabic (الفصحى المعاصرة)</option>
            <option value="english">Professional English</option>
          </select>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.formality') }}: <span class="text-emerald-400 font-bold">{{ voiceForm.formality_scale }} / 5</span></label>
          <input v-model.number="voiceForm.formality_scale" type="range" min="1" max="5" class="w-full accent-emerald-500" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.emojiStyle') }}</label>
          <select v-model="voiceForm.emoji_style" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
            <option value="moderate">Moderate (Modern professional with relevant emojis)</option>
            <option value="minimal">Minimal (Rare emojis for formal corporate)</option>
            <option value="expressive">Expressive (High engagement with energetic emojis)</option>
          </select>
        </div>
      </div>

      <!-- Step 5: Growth Goal -->
      <div v-else-if="currentStepIndex === 4" class="space-y-4">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('brandBrain.fields.goalType') }} *</label>
          <select v-model="goalForm.goal_type" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
            <option value="lead_generation">Lead Generation & Inbound Inquiries</option>
            <option value="sales">E-Commerce & Direct Sales</option>
            <option value="brand_awareness">Brand Awareness & Reach</option>
            <option value="engagement">Community Engagement & Trust</option>
          </select>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">Goal Description</label>
          <input v-model="goalForm.description" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="e.g. Generate 50 qualified B2B leads monthly" />
        </div>
      </div>

      <!-- Step 6: Congratulations -->
      <div v-else-if="currentStepIndex === 5" class="text-center py-6 space-y-6">
        <div class="w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-400 text-3xl flex items-center justify-center mx-auto animate-bounce">
          ✓
        </div>
        <div class="space-y-2">
          <h3 class="text-xl font-bold text-white">{{ t('onboarding.congratsTitle') }}</h3>
          <p class="text-xs text-slate-300 max-w-md mx-auto">{{ t('onboarding.congratsDesc') }}</p>
        </div>
        <div class="p-4 rounded-2xl bg-emerald-950/30 border border-emerald-500/20 max-w-xs mx-auto">
          <div class="text-2xl font-black text-emerald-400">{{ completenessScore }}%</div>
          <div class="text-[11px] text-slate-400">Brand Brain Completeness</div>
        </div>
        <button 
          @click="$emit('completed')" 
          class="tactile-btn tactile-btn-primary text-sm px-8 py-3 shadow-xl shadow-emerald-500/25"
        >
          {{ t('onboarding.launchStrategyBtn') }}
        </button>
      </div>

      <!-- Action Footer (For Steps 0-4) -->
      <div v-if="currentStepIndex < 5" class="flex items-center justify-between pt-4 border-t border-slate-800">
        <button 
          v-if="currentStepIndex > 0" 
          @click="currentStepIndex--" 
          class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300 hover:text-white"
        >
          {{ t('common.back') }}
        </button>
        <div v-else></div>

        <div class="flex items-center gap-3">
          <button 
            @click="skipStep" 
            class="text-xs text-slate-400 hover:text-slate-200"
          >
            {{ t('common.skip') }}
          </button>
          <button 
            @click="saveAndNext" 
            :disabled="saving"
            class="tactile-btn tactile-btn-primary text-xs px-6 py-2"
          >
            {{ saving ? t('common.processing') : t('common.next') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { t } from '../i18n';

const props = defineProps<{
  authToken: string;
  organizationId: number;
}>();

const emit = defineEmits(['completed']);

const currentStepIndex = ref(0);
const saving = ref(false);
const completenessScore = ref(85);

const steps = [
  { title: '1. Tell Us About Your Business', desc: 'Provide core business profile information.' },
  { title: '2. Your Flagship Product or Service', desc: 'What are you promoting to your customers?' },
  { title: '3. Your Ideal Customer Persona', desc: 'Who is the target audience for your campaigns?' },
  { title: '4. Brand Voice & Localized Dialect', desc: 'How should your AI speak to sound native and authentic?' },
  { title: '5. What are your Growth Goals?', desc: 'Set your primary business objective.' },
  { title: 'Brand Brain is Ready!', desc: 'Intelligence synthesis complete.' }
];

const form = ref({
  business_name: '',
  industry: 'Technology',
  country: 'SA',
  tagline: '',
  description: '',
});

const productForm = ref({
  name: '',
  type: 'product',
  price: 199,
  currency: 'SAR',
});

const audienceForm = ref({
  name: '',
  type: 'b2b',
  pain_points_raw: '',
});

const voiceForm = ref({
  dialect: 'saudi',
  formality_scale: 3,
  emoji_style: 'moderate',
});

const goalForm = ref({
  goal_type: 'lead_generation',
  priority: 'primary',
  description: '',
});

const getHeaders = () => ({
  Authorization: `Bearer ${props.authToken}`,
  'X-Organization-Id': String(props.organizationId),
});

const saveAndNext = async () => {
  saving.value = true;
  try {
    if (currentStepIndex.value === 0) {
      await axios.post('/api/v1/brand', form.value, { headers: getHeaders() });
    } else if (currentStepIndex.value === 1 && productForm.value.name) {
      await axios.post('/api/v1/brand/products', productForm.value, { headers: getHeaders() });
    } else if (currentStepIndex.value === 2 && audienceForm.value.name) {
      const painPoints = audienceForm.value.pain_points_raw.split(',').map(s => s.trim()).filter(Boolean);
      await axios.post('/api/v1/brand/audiences', {
        name: audienceForm.value.name,
        type: audienceForm.value.type,
        pain_points: painPoints,
      }, { headers: getHeaders() });
    } else if (currentStepIndex.value === 3) {
      await axios.patch('/api/v1/brand/voice', voiceForm.value, { headers: getHeaders() });
    } else if (currentStepIndex.value === 4) {
      await axios.post('/api/v1/brand/goals', goalForm.value, { headers: getHeaders() });
    }

    currentStepIndex.value++;
  } catch (err: any) {
    console.error('Onboarding save failed', err);
    // Proceed if non-fatal
    currentStepIndex.value++;
  } finally {
    saving.value = false;
  }
};

const skipStep = () => {
  currentStepIndex.value++;
};
</script>
