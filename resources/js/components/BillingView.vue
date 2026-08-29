<template>
  <div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-extrabold text-white tracking-tight">{{ t('billing.title') }}</h2>
        <p class="text-xs text-slate-400 mt-1">{{ t('billing.subtitle') }}</p>
      </div>

      <button @click="showUpgradeModal = true" class="tactile-btn tactile-btn-primary text-xs px-5 py-2.5">
        {{ t('billing.upgradeBtn') }}
      </button>
    </div>

    <!-- Active Subscription Overview Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-3">
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">{{ t('billing.currentPlan') }}</div>
        <div class="flex items-center gap-3">
          <span class="text-2xl font-black text-white">{{ subscription?.plan?.name || 'Starter' }}</span>
          <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold uppercase">
            {{ subscription?.status || 'Active' }}
          </span>
        </div>
        <div class="text-xs text-slate-400">
          {{ subscription?.plan?.currency || 'SAR' }} {{ subscription?.plan?.price_monthly || 0 }} / mo
        </div>
      </div>

      <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-3">
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">{{ t('billing.trialBadge') }}</div>
        <div class="text-2xl font-black text-emerald-400">{{ trialDaysLeft }} Days</div>
        <div class="text-xs text-slate-400">{{ t('billing.trialDaysLeft') }}</div>
      </div>

      <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-3">
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Actions & Management</div>
        <div class="flex items-center gap-3 pt-1">
          <button @click="showUpgradeModal = true" class="px-3.5 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold hover:bg-emerald-500/20 transition-all">
            {{ t('billing.upgradeBtn') }}
          </button>
          <button @click="handleCancelSub" class="px-3.5 py-2 rounded-xl bg-slate-800 text-slate-400 hover:text-red-400 text-xs font-semibold transition-all">
            {{ t('billing.cancelBtn') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Monthly Feature Usage Quotas -->
    <div class="p-6 sm:p-8 rounded-3xl bg-slate-900 border border-slate-800 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-base font-bold text-white">{{ t('billing.usageTitle') }}</h3>
          <p class="text-xs text-slate-400 mt-0.5">Track real consumption across current monthly billing cycle.</p>
        </div>
        <span class="text-xs text-slate-500 font-mono">{{ usage?.ai_strategy?.period_start }} → {{ usage?.ai_strategy?.period_end }}</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- AI Strategy Quota -->
        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-3">
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-slate-300">AI Strategy Generations</span>
            <span class="text-emerald-400 font-bold">
              {{ usage?.ai_strategy?.used || 0 }} / {{ usage?.ai_strategy?.is_unlimited ? '∞' : (usage?.ai_strategy?.limit || 5) }}
            </span>
          </div>
          <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
            <div 
              class="bg-emerald-500 h-full rounded-full transition-all"
              :style="{ width: usage?.ai_strategy?.is_unlimited ? '100%' : `${Math.min(100, ((usage?.ai_strategy?.used || 0) / (usage?.ai_strategy?.limit || 5)) * 100)}%` }"
            ></div>
          </div>
          <div class="text-[11px] text-slate-500">
            {{ usage?.ai_strategy?.remaining || 5 }} remaining this month
          </div>
        </div>

        <!-- Team Members Quota -->
        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-3">
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-slate-300">Team Members</span>
            <span class="text-emerald-400 font-bold">
              1 / {{ usage?.team_members?.is_unlimited ? '∞' : (usage?.team_members?.limit || 2) }}
            </span>
          </div>
          <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
            <div 
              class="bg-emerald-500 h-full rounded-full transition-all"
              :style="{ width: `${Math.min(100, (1 / (usage?.team_members?.limit || 2)) * 100)}%` }"
            ></div>
          </div>
          <div class="text-[11px] text-slate-500">
            RBAC seats allocated
          </div>
        </div>

        <!-- Content Generation Quota -->
        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-3">
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-slate-300">Content Studio Posts</span>
            <span class="text-amber-400 font-bold">Phase 4 Ready</span>
          </div>
          <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
            <div class="bg-amber-400 h-full rounded-full" style="width: 25%"></div>
          </div>
          <div class="text-[11px] text-slate-500">
            Up to {{ usage?.ai_content?.limit || 30 }} posts/mo included in plan
          </div>
        </div>
      </div>
    </div>

    <!-- Upgrade Tier Modal -->
    <div v-if="showUpgradeModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-2xl w-full space-y-6 shadow-2xl">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-white">{{ t('billing.upgradeBtn') }}</h3>
          <button @click="showUpgradeModal = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <div class="grid sm:grid-cols-3 gap-4">
          <div 
            v-for="plan in availablePlans" 
            :key="plan.id"
            :class="[selectedPlanId === plan.id ? 'border-emerald-500 bg-emerald-950/20' : 'border-slate-800 bg-slate-950']"
            class="p-4 rounded-2xl border cursor-pointer hover:border-slate-700 transition-all space-y-3"
            @click="selectedPlanId = plan.id"
          >
            <div class="flex items-center justify-between">
              <span class="font-bold text-white text-sm">{{ plan.name }}</span>
              <span v-if="subscription?.plan_id === plan.id" class="text-[10px] text-emerald-400 font-bold">CURRENT</span>
            </div>
            <div class="text-lg font-extrabold text-white">{{ plan.currency }} {{ plan.price_monthly }}<span class="text-[10px] text-slate-400">/mo</span></div>
            <p class="text-[11px] text-slate-400 leading-relaxed">{{ plan.description }}</p>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button @click="showUpgradeModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300">
            {{ t('common.cancel') }}
          </button>
          <button @click="handleSelectPlan" class="tactile-btn tactile-btn-primary text-xs px-6 py-2">
            Confirm Plan Selection
          </button>
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
}>();

const subscription = ref<any>(null);
const usage = ref<any>({});
const trialDaysLeft = ref(14);
const availablePlans = ref<any[]>([]);
const showUpgradeModal = ref(false);
const selectedPlanId = ref<number | null>(null);

const getHeaders = () => ({
  Authorization: `Bearer ${props.authToken}`,
  'X-Organization-Id': String(props.organizationId),
});

const loadSubscriptionData = async () => {
  try {
    const res = await axios.get('/api/v1/billing/subscription', { headers: getHeaders() });
    subscription.value = res.data?.data?.subscription;
    usage.value = res.data?.data?.usage;
    trialDaysLeft.value = res.data?.data?.trial_remaining_days ?? 14;
    selectedPlanId.value = subscription.value?.plan_id ?? 1;

    // Load available plans
    const plansRes = await axios.get('/api/v1/billing/plans', { headers: getHeaders() });
    availablePlans.value = plansRes.data?.data?.plans ?? [];
  } catch (err) {
    console.error('Failed to load subscription', err);
  }
};

const handleSelectPlan = async () => {
  if (!selectedPlanId.value) return;
  try {
    await axios.post('/api/v1/billing/subscription/select-plan', {
      plan_id: selectedPlanId.value,
    }, { headers: getHeaders() });
    showUpgradeModal.value = false;
    await loadSubscriptionData();
    alert('Plan updated successfully.');
  } catch (err) {
    alert('Failed to update plan.');
  }
};

const handleCancelSub = async () => {
  if (!confirm('Are you sure you want to cancel your active subscription?')) return;
  try {
    await axios.post('/api/v1/billing/subscription/cancel', {}, { headers: getHeaders() });
    await loadSubscriptionData();
    alert('Subscription cancelled.');
  } catch (err) {
    alert('Failed to cancel subscription.');
  }
};

onMounted(() => {
  loadSubscriptionData();
});
</script>
