<template>
  <div class="max-w-4xl mx-auto space-y-8">
    <div>
      <h2 class="text-2xl font-extrabold text-white tracking-tight">{{ t('navigation.settings') }}</h2>
      <p class="text-xs text-slate-400 mt-1">Configure your organization preferences and user profile settings.</p>
    </div>

    <!-- Organization Profile Card -->
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
        <span class="text-xs text-slate-500 font-mono">User ID: {{ currentUser?.id }} • Verified</span>
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
});
</script>
