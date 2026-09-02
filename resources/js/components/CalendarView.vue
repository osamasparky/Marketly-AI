<template>
  <div class="space-y-6">
    <!-- Top Header & Metrics Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900/60 p-6 rounded-3xl border border-slate-800/80 backdrop-blur-xl">
      <div>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-xl text-cyan-400">
            📅
          </div>
          <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
              {{ t('marketingCalendar.title') }}
              <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                Phase 6 Complete
              </span>
            </h2>
            <p class="text-xs text-slate-400 max-w-2xl mt-0.5">{{ t('marketingCalendar.subtitle') }}</p>
          </div>
        </div>
      </div>

      <!-- Action Controls -->
      <div class="flex flex-wrap items-center gap-3">
        <!-- Date Navigation Controls -->
        <div class="flex items-center bg-slate-950/80 border border-slate-800 rounded-xl p-1 text-xs">
          <button @click="navigateMonth(-1)" class="px-2.5 py-1 rounded-lg hover:bg-slate-800 text-slate-300 transition-colors">◀</button>
          <button @click="goToToday" class="px-3 py-1 rounded-lg hover:bg-slate-800 text-slate-200 font-bold transition-colors">{{ t('marketingCalendar.today') }}</button>
          <button @click="navigateMonth(1)" class="px-2.5 py-1 rounded-lg hover:bg-slate-800 text-slate-300 transition-colors">▶</button>
        </div>

        <!-- View Switcher Tabs (Month vs Week) -->
        <div class="flex items-center bg-slate-950/80 border border-slate-800 rounded-xl p-1 text-xs">
          <button 
            @click="currentView = 'month'"
            :class="[currentView === 'month' ? 'bg-slate-800 text-emerald-400 font-bold' : 'text-slate-400 hover:text-white']"
            class="px-3 py-1 rounded-lg transition-all"
          >
            {{ t('marketingCalendar.viewMonth') }}
          </button>
          <button 
            @click="currentView = 'week'"
            :class="[currentView === 'week' ? 'bg-slate-800 text-emerald-400 font-bold' : 'text-slate-400 hover:text-white']"
            class="px-3 py-1 rounded-lg transition-all"
          >
            {{ t('marketingCalendar.viewWeek') }}
          </button>
        </div>

        <!-- Auto-Plan Wizard Button -->
        <button 
          @click="showPlanWizard = true"
          class="tactile-btn tactile-btn-primary px-4 py-2 text-xs flex items-center gap-2"
        >
          <span>✨</span>
          {{ t('marketingCalendar.autoPlanBtn') }}
        </button>
      </div>
    </div>

    <!-- Calendar Summary Metric Badges -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
      <div class="p-3 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
        <span class="text-xs text-slate-400">{{ t('marketingCalendar.metrics.scheduled') }}</span>
        <span class="text-sm font-black text-cyan-400">{{ metrics.scheduled_count || 0 }}</span>
      </div>
      <div class="p-3 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
        <span class="text-xs text-slate-400">{{ t('marketingCalendar.metrics.approved') }}</span>
        <span class="text-sm font-black text-emerald-400">{{ metrics.approved_count || 0 }}</span>
      </div>
      <div class="p-3 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
        <span class="text-xs text-slate-400">{{ t('marketingCalendar.metrics.inReview') }}</span>
        <span class="text-sm font-black text-amber-400">{{ metrics.in_review_count || 0 }}</span>
      </div>
      <div class="p-3 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
        <span class="text-xs text-slate-400">{{ t('marketingCalendar.metrics.drafts') }}</span>
        <span class="text-sm font-black text-slate-300">{{ metrics.draft_count || 0 }}</span>
      </div>
      <div class="p-3 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
        <span class="text-xs text-slate-400">{{ t('marketingCalendar.metrics.published') }}</span>
        <span class="text-sm font-black text-emerald-500">{{ metrics.published_count || 0 }}</span>
      </div>
    </div>

    <!-- Current Month / Year Banner -->
    <div class="flex items-center justify-between px-2">
      <h3 class="text-lg font-extrabold text-white tracking-tight">
        {{ currentMonthYearTitle }}
      </h3>
      <span class="text-xs text-slate-400">
        {{ currentLocale === 'ar' ? 'اسحب المنشورات لإعادة جدولتها بسهولة' : 'Drag and drop cards across days to reschedule' }}
      </span>
    </div>

    <!-- 1. Month View Calendar Grid -->
    <div v-if="currentView === 'month'" class="bg-slate-900/60 rounded-3xl border border-slate-800/80 p-4 backdrop-blur-xl overflow-hidden">
      <!-- Days of Week Header -->
      <div class="grid grid-cols-7 gap-2 mb-2 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">
        <div v-for="d in daysOfWeek" :key="d" class="py-2 rounded-xl bg-slate-950/40">
          {{ d }}
        </div>
      </div>

      <!-- Month Day Cells Grid -->
      <div class="grid grid-cols-7 gap-2">
        <div 
          v-for="(cell, idx) in monthCalendarCells" 
          :key="idx"
          @dragover.prevent
          @drop="handleDropOnDay(cell.dateStr)"
          :class="[
            cell.isCurrentMonth ? 'bg-slate-950/70 border-slate-800/80' : 'bg-slate-950/20 border-slate-900/40 opacity-40',
            cell.isToday ? 'ring-2 ring-emerald-500/50 bg-emerald-950/10' : '',
            'min-h-[140px] p-2.5 rounded-2xl border flex flex-col justify-between transition-colors'
          ]"
        >
          <!-- Cell Header: Date Number & Count -->
          <div class="flex items-center justify-between">
            <span 
              class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
              :class="cell.isToday ? 'bg-emerald-500 text-slate-950' : 'text-slate-400'"
            >
              {{ cell.dayNumber }}
            </span>
            <span v-if="cell.posts.length > 0" class="text-[10px] font-bold text-slate-500">
              {{ cell.posts.length }} {{ currentLocale === 'ar' ? 'منشور' : 'posts' }}
            </span>
          </div>

          <!-- Post Cards in Day Cell -->
          <div class="space-y-1.5 my-2 flex-1 overflow-y-auto max-h-[160px]">
            <div 
              v-for="post in cell.posts" 
              :key="post.id"
              draggable="true"
              @dragstart="handleDragStart(post)"
              @click="openDrawer(post)"
              class="p-2 rounded-xl bg-slate-900 hover:bg-slate-850 border border-slate-800 hover:border-emerald-500/40 cursor-grab active:cursor-grabbing transition-all space-y-1 text-left"
              :dir="currentLocale === 'ar' ? 'rtl' : 'ltr'"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-1">
                  <span class="text-xs">{{ getPlatformIcon(post.primary_platform) }}</span>
                  <span class="text-[10px] font-bold uppercase text-slate-300">{{ post.primary_platform }}</span>
                </div>
                <span 
                  class="px-1.5 py-0.2 rounded text-[8px] font-bold uppercase tracking-wider"
                  :class="getStatusBadgeClass(post.status)"
                >
                  {{ post.status }}
                </span>
              </div>
              <p class="text-[11px] font-medium text-white line-clamp-1 leading-tight">{{ post.title }}</p>
              <div class="flex items-center justify-between text-[9px] text-slate-400 pt-0.5">
                <span>⏱️ {{ formatTime(post.scheduled_at) }}</span>
                <span v-if="post.latest_audit" class="text-emerald-400 font-bold">{{ post.latest_audit.score }}%</span>
              </div>
            </div>
          </div>

          <!-- Cell Footer Plus Action -->
          <div class="text-right">
            <button 
              @click="openNewPostForDay(cell.dateStr)"
              class="text-[10px] text-slate-600 hover:text-emerald-400 transition-colors"
              title="Add post for this day"
            >
              ➕
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Week View Calendar Grid -->
    <div v-else class="bg-slate-900/60 rounded-3xl border border-slate-800/80 p-4 backdrop-blur-xl overflow-x-auto">
      <div class="grid grid-cols-7 gap-3 min-w-[750px]">
        <div 
          v-for="day in weekDaysList" 
          :key="day.dateStr"
          @dragover.prevent
          @drop="handleDropOnDay(day.dateStr)"
          class="bg-slate-950/70 border border-slate-800/80 p-3 rounded-2xl min-h-[350px] flex flex-col space-y-3"
          :class="{'ring-2 ring-emerald-500/50': day.isToday}"
        >
          <div class="flex items-center justify-between pb-2 border-b border-slate-800">
            <div>
              <div class="text-xs font-bold text-slate-400 uppercase">{{ day.dayName }}</div>
              <div class="text-sm font-black text-white">{{ day.dateFormatted }}</div>
            </div>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-800 text-slate-300">
              {{ day.posts.length }}
            </span>
          </div>

          <!-- Posts in Day -->
          <div class="space-y-2 flex-1 overflow-y-auto">
            <div 
              v-for="post in day.posts" 
              :key="post.id"
              draggable="true"
              @dragstart="handleDragStart(post)"
              @click="openDrawer(post)"
              class="p-3 rounded-xl bg-slate-900 hover:bg-slate-850 border border-slate-800 hover:border-emerald-500/40 cursor-grab space-y-1.5 transition-all text-left"
              :dir="currentLocale === 'ar' ? 'rtl' : 'ltr'"
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-200 flex items-center gap-1">
                  {{ getPlatformIcon(post.primary_platform) }} {{ post.primary_platform }}
                </span>
                <span 
                  class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase"
                  :class="getStatusBadgeClass(post.status)"
                >
                  {{ post.status }}
                </span>
              </div>
              <p class="text-xs font-bold text-white line-clamp-2">{{ post.title }}</p>
              <div class="flex items-center justify-between text-[10px] text-slate-400 pt-1 border-t border-slate-800/50">
                <span>⏱️ {{ formatTime(post.scheduled_at) }}</span>
                <span v-if="post.latest_audit" class="text-emerald-400 font-bold">🛡️ {{ post.latest_audit.score }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Post Action Drawer / Modal -->
    <div v-if="selectedPost" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-6 shadow-2xl overflow-y-auto max-h-[90vh]">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div class="flex items-center gap-2">
            <span class="text-lg">{{ getPlatformIcon(selectedPost.primary_platform) }}</span>
            <h3 class="text-base font-bold text-white">{{ selectedPost.title }}</h3>
          </div>
          <button @click="selectedPost = null" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <div class="space-y-4 text-xs">
          <!-- Status & Platform -->
          <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-950 border border-slate-800">
            <div>
              <span class="text-slate-400 font-semibold">Platform:</span>
              <span class="text-white font-bold ml-1 uppercase">{{ selectedPost.primary_platform }}</span>
            </div>
            <div>
              <span class="text-slate-400 font-semibold">Status:</span>
              <span class="ml-1 px-2 py-0.5 rounded font-bold uppercase text-[10px]" :class="getStatusBadgeClass(selectedPost.status)">
                {{ selectedPost.status }}
              </span>
            </div>
          </div>

          <!-- Post Hook & Caption Preview -->
          <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-1">
            <span class="text-slate-400 font-bold">Content Copy Preview:</span>
            <p class="text-slate-200 leading-relaxed">{{ selectedPost.hook || selectedPost.caption }}</p>
          </div>

          <!-- Scheduled Date / Time Input -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-300">{{ t('marketingCalendar.drawer.scheduledFor') }}</label>
            <input 
              v-model="drawerScheduledAt" 
              type="datetime-local" 
              class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-emerald-500/50 text-xs"
            />
          </div>

          <!-- Approval Workflow Actions -->
          <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-800">
            <button 
              v-if="selectedPost.status === 'draft'"
              @click="submitForReview(selectedPost.id)"
              :disabled="actionLoading"
              class="px-3 py-2 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 border border-amber-500/40 text-amber-300 font-bold transition-colors"
            >
              📝 {{ t('marketingCalendar.drawer.submitReviewAction') }}
            </button>

            <button 
              v-if="selectedPost.status !== 'approved' && selectedPost.status !== 'scheduled'"
              @click="approvePost(selectedPost.id)"
              :disabled="actionLoading"
              class="px-3 py-2 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/40 text-emerald-300 font-bold transition-colors"
            >
              ✅ {{ t('marketingCalendar.drawer.approveAction') }}
            </button>

            <button 
              @click="reschedulePost(selectedPost.id)"
              :disabled="actionLoading"
              class="tactile-btn tactile-btn-primary px-4 py-2 font-bold"
            >
              💾 {{ t('marketingCalendar.drawer.rescheduleAction') }}
            </button>

            <button 
              v-if="selectedPost.status === 'scheduled' || selectedPost.status === 'approved'"
              @click="publishLiveNow(selectedPost.id)"
              :disabled="actionLoading"
              class="px-3 py-2 rounded-xl bg-purple-600/30 hover:bg-purple-600/50 border border-purple-500/40 text-purple-300 font-bold transition-colors flex items-center gap-1.5"
            >
              <span>🚀</span>
              <span>{{ currentLocale === 'ar' ? 'نشر فوري الآن على الصفحة' : 'Publish Live Now' }}</span>
            </button>

            <button 
              v-if="selectedPost.status === 'scheduled'"
              @click="unschedulePost(selectedPost.id)"
              :disabled="actionLoading"
              class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold transition-colors"
            >
              ↩️ {{ t('marketingCalendar.drawer.unscheduleAction') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 7/14/30-Day Auto-Planner Wizard Modal -->
    <div v-if="showPlanWizard" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div class="flex items-center gap-2.5">
            <span class="text-xl">✨</span>
            <h3 class="text-base font-bold text-white">{{ t('marketingCalendar.wizard.title') }}</h3>
          </div>
          <button @click="showPlanWizard = false" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form @submit.prevent="handleAutoPlan" class="space-y-4 text-xs">
          <!-- Horizon Selector -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-300">{{ t('marketingCalendar.wizard.horizonLabel') }}</label>
            <div class="grid grid-cols-3 gap-2">
              <button 
                type="button" 
                @click="planForm.horizon_days = 7"
                :class="[planForm.horizon_days === 7 ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/50 font-bold' : 'bg-slate-950 text-slate-400 border-slate-800']"
                class="p-2.5 rounded-xl border text-xs text-center transition-all"
              >
                {{ t('marketingCalendar.wizard.horizon7') }}
              </button>
              <button 
                type="button" 
                @click="planForm.horizon_days = 14"
                :class="[planForm.horizon_days === 14 ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/50 font-bold' : 'bg-slate-950 text-slate-400 border-slate-800']"
                class="p-2.5 rounded-xl border text-xs text-center transition-all"
              >
                {{ t('marketingCalendar.wizard.horizon14') }}
              </button>
              <button 
                type="button" 
                @click="planForm.horizon_days = 30"
                :class="[planForm.horizon_days === 30 ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/50 font-bold' : 'bg-slate-950 text-slate-400 border-slate-800']"
                class="p-2.5 rounded-xl border text-xs text-center transition-all"
              >
                {{ t('marketingCalendar.wizard.horizon30') }}
              </button>
            </div>
          </div>

          <!-- Platforms -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-300">{{ t('marketingCalendar.wizard.platformsLabel') }}</label>
            <div class="grid grid-cols-4 gap-2">
              <label 
                v-for="platform in ['linkedin', 'instagram', 'x', 'tiktok']" 
                :key="platform"
                class="p-2 rounded-xl bg-slate-950 border border-slate-800 flex items-center gap-2 cursor-pointer"
              >
                <input type="checkbox" :value="platform" v-model="planForm.platforms" class="accent-emerald-500 rounded" />
                <span class="text-xs uppercase font-bold text-slate-300">{{ platform }}</span>
              </label>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
            <button 
              type="button" 
              @click="showPlanWizard = false"
              class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:bg-slate-700 text-xs font-semibold"
            >
              {{ t('common.cancel') }}
            </button>
            <button 
              type="submit" 
              :disabled="planning"
              class="tactile-btn tactile-btn-primary px-5 py-2 text-xs flex items-center gap-2"
            >
              <span v-if="planning" class="animate-spin">⏳</span>
              <span v-else>✨</span>
              {{ planning ? t('common.processing') : t('marketingCalendar.wizard.generateAction') }}
            </button>
          </div>
        </form>
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
const planning = ref(false);
const currentView = ref<'month' | 'week'>('month');
const activeDate = ref(new Date());

const calendarPosts = ref<any[]>([]);
const metrics = ref<any>({});
const selectedPost = ref<any | null>(null);
const drawerScheduledAt = ref('');
const draggedPost = ref<any | null>(null);

const showPlanWizard = ref(false);
const planForm = ref({
  horizon_days: 7,
  platforms: ['linkedin', 'instagram', 'x', 'tiktok'],
});

const daysOfWeek = computed(() => {
  return [
    t('marketingCalendar.daysOfWeek.sun'),
    t('marketingCalendar.daysOfWeek.mon'),
    t('marketingCalendar.daysOfWeek.tue'),
    t('marketingCalendar.daysOfWeek.wed'),
    t('marketingCalendar.daysOfWeek.thu'),
    t('marketingCalendar.daysOfWeek.fri'),
    t('marketingCalendar.daysOfWeek.sat'),
  ];
});

const currentMonthYearTitle = computed(() => {
  return activeDate.value.toLocaleDateString(currentLocale.value === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'long',
    year: 'numeric',
  });
});

const monthCalendarCells = computed(() => {
  const year = activeDate.value.getFullYear();
  const month = activeDate.value.getMonth();

  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);

  const startDayOfWeek = firstDay.getDay(); // 0 = Sun
  const totalDays = lastDay.getDate();

  const todayStr = new Date().toISOString().split('T')[0];
  const cells = [];

  // Previous month trailing days
  const prevMonthLastDay = new Date(year, month, 0).getDate();
  for (let i = startDayOfWeek - 1; i >= 0; i--) {
    const dayNum = prevMonthLastDay - i;
    const d = new Date(year, month - 1, dayNum);
    const dateStr = d.toISOString().split('T')[0];
    cells.push({
      dayNumber: dayNum,
      dateStr: dateStr,
      isCurrentMonth: false,
      isToday: dateStr === todayStr,
      posts: getPostsForDate(dateStr),
    });
  }

  // Current month days
  for (let i = 1; i <= totalDays; i++) {
    const d = new Date(year, month, i);
    const dateStr = d.toISOString().split('T')[0];
    cells.push({
      dayNumber: i,
      dateStr: dateStr,
      isCurrentMonth: true,
      isToday: dateStr === todayStr,
      posts: getPostsForDate(dateStr),
    });
  }

  // Next month leading days to round out to full rows
  const remaining = 35 - cells.length > 0 ? 35 - cells.length : 42 - cells.length;
  for (let i = 1; i <= remaining; i++) {
    const d = new Date(year, month + 1, i);
    const dateStr = d.toISOString().split('T')[0];
    cells.push({
      dayNumber: i,
      dateStr: dateStr,
      isCurrentMonth: false,
      isToday: dateStr === todayStr,
      posts: getPostsForDate(dateStr),
    });
  }

  return cells;
});

const weekDaysList = computed(() => {
  const current = new Date(activeDate.value);
  const startOfWeek = new Date(current.setDate(current.getDate() - current.getDay()));
  const todayStr = new Date().toISOString().split('T')[0];

  const days = [];
  for (let i = 0; i < 7; i++) {
    const d = new Date(startOfWeek);
    d.setDate(d.getDate() + i);
    const dateStr = d.toISOString().split('T')[0];
    days.push({
      dateStr: dateStr,
      dayName: daysOfWeek.value[i],
      dateFormatted: d.toLocaleDateString(currentLocale.value === 'ar' ? 'ar-SA' : 'en-US', { month: 'short', day: 'numeric' }),
      isToday: dateStr === todayStr,
      posts: getPostsForDate(dateStr),
    });
  }
  return days;
});

function getPostsForDate(dateStr: string) {
  return calendarPosts.value.filter(p => {
    if (p.scheduled_at) {
      return p.scheduled_at.startsWith(dateStr);
    }
    return p.created_at?.startsWith(dateStr);
  });
}

function getAuthHeaders() {
  return {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${props.authToken}`,
    'X-Organization-Id': String(props.organizationId || ''),
    ...(props.brandId ? { 'X-Brand-Id': String(props.brandId) } : {}),
  };
}

async function fetchCalendar() {
  if (!props.authToken) return;
  loading.value = true;

  try {
    const year = activeDate.value.getFullYear();
    const month = activeDate.value.getMonth();
    const startStr = new Date(year, month - 1, 20).toISOString().split('T')[0];
    const endStr = new Date(year, month + 2, 10).toISOString().split('T')[0];

    const res = await fetch(`/api/v1/calendar?start_date=${startStr}&end_date=${endStr}`, {
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      const json = await res.json();
      calendarPosts.value = json.data?.posts || [];
      metrics.value = json.data?.metrics || {};
    }
  } catch (err) {
    console.error('Failed to load calendar', err);
  } finally {
    loading.value = false;
  }
}

function navigateMonth(step: number) {
  const d = new Date(activeDate.value);
  d.setMonth(d.getMonth() + step);
  activeDate.value = d;
  fetchCalendar();
}

function goToToday() {
  activeDate.value = new Date();
  fetchCalendar();
}

function handleDragStart(post: any) {
  draggedPost.value = post;
}

async function handleDropOnDay(dateStr: string) {
  if (!draggedPost.value || !props.authToken) return;

  const prevTime = draggedPost.value.scheduled_at ? draggedPost.value.scheduled_at.split(' ')[1] || '10:00:00' : '10:00:00';
  const newDateTime = `${dateStr} ${prevTime}`;

  try {
    const res = await fetch(`/api/v1/calendar/posts/${draggedPost.value.id}/reschedule`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({ scheduled_at: newDateTime }),
    });

    if (res.ok) {
      draggedPost.value = null;
      await fetchCalendar();
    }
  } catch (err) {
    console.error('Failed to reschedule on drop', err);
  }
}

function openDrawer(post: any) {
  selectedPost.value = post;
  if (post.scheduled_at) {
    drawerScheduledAt.value = post.scheduled_at.replace(' ', 'T').substring(0, 16);
  } else {
    drawerScheduledAt.value = new Date().toISOString().substring(0, 16);
  }
}

async function reschedulePost(postId: number) {
  if (!props.authToken || !drawerScheduledAt.value) return;
  actionLoading.value = true;

  try {
    const formatted = drawerScheduledAt.value.replace('T', ' ') + ':00';
    const res = await fetch(`/api/v1/calendar/posts/${postId}/reschedule`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({ scheduled_at: formatted }),
    });

    if (res.ok) {
      selectedPost.value = null;
      await fetchCalendar();
    }
  } catch (err) {
    console.error('Reschedule failed', err);
  } finally {
    actionLoading.value = false;
  }
}

async function submitForReview(postId: number) {
  if (!props.authToken) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/calendar/posts/${postId}/submit-review`, {
      method: 'POST',
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      selectedPost.value = null;
      await fetchCalendar();
    }
  } catch (err) {
    console.error('Submit review failed', err);
  } finally {
    actionLoading.value = false;
  }
}

async function approvePost(postId: number) {
  if (!props.authToken) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/calendar/posts/${postId}/approve`, {
      method: 'POST',
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      selectedPost.value = null;
      await fetchCalendar();
    }
  } catch (err) {
    console.error('Approve failed', err);
  } finally {
    actionLoading.value = false;
  }
}

async function publishLiveNow(postId: number) {
  if (!props.authToken) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/social/posts/${postId}/publish-now`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({}),
    });

    if (res.ok) {
      const json = await res.json();
      selectedPost.value = null;
      await fetchCalendar();
      alert(currentLocale.value === 'ar' ? '🚀 تم نشر المنشور بنجاح ومباشرة على صفحتك في فيسبوك!' : 'Post published live to your Facebook Page successfully!');
    } else {
      const err = await res.json();
      alert(err.message || 'Publishing failed');
    }
  } catch (err: any) {
    alert(err.message || 'Error occurred while publishing to Facebook');
  } finally {
    actionLoading.value = false;
  }
}

async function unschedulePost(postId: number) {
  if (!props.authToken) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/calendar/posts/${postId}/unschedule`, {
      method: 'POST',
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      selectedPost.value = null;
      await fetchCalendar();
    }
  } catch (err) {
    console.error('Unschedule failed', err);
  } finally {
    actionLoading.value = false;
  }
}

async function handleAutoPlan() {
  if (!props.authToken) return;
  planning.value = true;

  try {
    const res = await fetch('/api/v1/calendar/plan', {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({
        horizon_days: planForm.value.horizon_days,
        platforms: planForm.value.platforms,
      }),
    });

    if (res.ok) {
      showPlanWizard.value = false;
      await fetchCalendar();
    } else {
      const err = await res.json();
      alert(err.message || 'Auto-plan failed');
    }
  } catch (err) {
    console.error('Auto-plan error', err);
  } finally {
    planning.value = false;
  }
}

function openNewPostForDay(dateStr: string) {
  alert(`Create new post for ${dateStr} in Content Studio`);
}

function getPlatformIcon(platform: string) {
  const icons: Record<string, string> = {
    linkedin: '💼',
    instagram: '📸',
    x: '🐦',
    tiktok: '🎬',
    facebook: '👥',
  };
  return icons[platform] || '📱';
}

function getStatusBadgeClass(status: string) {
  if (status === 'published') return 'bg-emerald-500/20 text-emerald-400';
  if (status === 'scheduled') return 'bg-cyan-500/20 text-cyan-400';
  if (status === 'approved') return 'bg-emerald-500/10 text-emerald-300';
  if (status === 'in_review') return 'bg-amber-500/20 text-amber-400';
  return 'bg-slate-800 text-slate-400';
}

function formatTime(dateStr?: string) {
  if (!dateStr) return 'Draft';
  const parts = dateStr.split(' ');
  if (parts.length > 1) {
    return parts[1].substring(0, 5);
  }
  return '';
}

watch(() => props.brandId, () => {
  fetchCalendar();
});

onMounted(() => {
  fetchCalendar();
});
</script>
