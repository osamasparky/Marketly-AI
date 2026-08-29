<template>
  <div class="space-y-8">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-extrabold text-white tracking-tight">{{ t('tenancy.membersTitle') }}</h2>
        <p class="text-xs text-slate-400 mt-1">Manage team members, roles, and granular RBAC permissions.</p>
      </div>

      <button @click="showInviteModal = true" class="tactile-btn tactile-btn-primary text-xs px-5 py-2.5">
        {{ t('tenancy.inviteMember') }}
      </button>
    </div>

    <!-- Members Table Card -->
    <div class="rounded-3xl bg-slate-900 border border-slate-800 overflow-hidden shadow-2xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
          <thead class="bg-slate-950/60 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
            <tr>
              <th class="p-4 sm:px-6">Member</th>
              <th class="p-4 sm:px-6">Role</th>
              <th class="p-4 sm:px-6">Status</th>
              <th class="p-4 sm:px-6">Joined Date</th>
              <th class="p-4 sm:px-6 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60">
            <tr v-for="mem in members" :key="mem.id" class="hover:bg-slate-800/30 transition-colors">
              <td class="p-4 sm:px-6 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 font-bold flex items-center justify-center text-xs">
                  {{ mem.user?.name?.charAt(0) || 'U' }}
                </div>
                <div>
                  <div class="font-bold text-white">{{ mem.user?.name }}</div>
                  <div class="text-[11px] text-slate-400">{{ mem.user?.email }}</div>
                </div>
              </td>
              <td class="p-4 sm:px-6">
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold uppercase">
                  {{ mem.role?.slug || 'viewer' }}
                </span>
              </td>
              <td class="p-4 sm:px-6">
                <span class="inline-flex items-center gap-1.5 text-xs text-slate-300">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                  <span>{{ mem.status }}</span>
                </span>
              </td>
              <td class="p-4 sm:px-6 text-slate-400 font-mono text-[11px]">
                {{ new Date(mem.created_at).toLocaleDateString() }}
              </td>
              <td class="p-4 sm:px-6 text-right">
                <button 
                  v-if="mem.role?.slug !== 'owner'" 
                  @click="handleRemoveMember(mem.user?.id)" 
                  class="text-xs text-red-400 hover:text-red-300 font-semibold"
                >
                  Remove
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Invite Member Modal -->
    <div v-if="showInviteModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
        <h3 class="text-base font-bold text-white">{{ t('tenancy.inviteMember') }}</h3>
        
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('tenancy.inviteEmail') }}</label>
          <input v-model="inviteForm.email" type="email" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="colleague@brand.com" />
        </div>

        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-slate-300">{{ t('tenancy.role') }}</label>
          <select v-model="inviteForm.role" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
            <option value="admin">{{ t('tenancy.adminRole') }}</option>
            <option value="manager">{{ t('tenancy.managerRole') }}</option>
            <option value="editor">{{ t('tenancy.editorRole') }}</option>
            <option value="viewer">{{ t('tenancy.viewerRole') }}</option>
          </select>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button @click="showInviteModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-xs text-slate-300">{{ t('common.cancel') }}</button>
          <button @click="handleSendInvite" class="tactile-btn tactile-btn-primary text-xs px-5 py-2">{{ t('tenancy.sendInvite') }}</button>
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

const members = ref<any[]>([]);
const showInviteModal = ref(false);
const inviteForm = ref({ email: '', role: 'editor' });

const getHeaders = () => ({
  Authorization: `Bearer ${props.authToken}`,
  'X-Organization-Id': String(props.organizationId),
});

const loadMembers = async () => {
  try {
    const res = await axios.get(`/api/v1/organizations/${props.organizationId}/members`, { headers: getHeaders() });
    members.value = res.data?.data?.members ?? [];
  } catch (err) {
    console.error('Failed to load team members', err);
  }
};

const handleSendInvite = async () => {
  if (!inviteForm.value.email) return;
  try {
    await axios.post(`/api/v1/organizations/${props.organizationId}/invitations`, inviteForm.value, { headers: getHeaders() });
    showInviteModal.value = false;
    inviteForm.value.email = '';
    alert('Invitation dispatched successfully.');
    await loadMembers();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to dispatch invitation.');
  }
};

const handleRemoveMember = async (userId: number) => {
  if (!confirm('Are you sure you want to remove this member from the organization?')) return;
  try {
    await axios.delete(`/api/v1/organizations/${props.organizationId}/members/${userId}`, { headers: getHeaders() });
    await loadMembers();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to remove member.');
  }
};

onMounted(() => {
  loadMembers();
});
</script>
