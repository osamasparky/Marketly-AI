<template>
  <div :dir="locale === 'ar' ? 'rtl' : 'ltr'" :class="{'dark': isDark}" class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans transition-colors duration-200">
    <!-- Top Navigation Header -->
    <header class="h-16 border-b border-slate-800/80 bg-slate-900/70 backdrop-blur-md px-6 flex items-center justify-between sticky top-0 z-50">
      <div class="flex items-center gap-4">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-950/50">
            <span class="text-white font-extrabold text-lg">M</span>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-extrabold text-base tracking-tight bg-gradient-to-r from-white via-slate-200 to-emerald-400 bg-clip-text text-transparent">Marketly-AI</span>
              <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Phase 0: Foundation</span>
            </div>
            <p class="text-[11px] text-slate-400">{{ locale === 'ar' ? 'الموظف التسويقي الذكي المستقل' : 'Your Autonomous AI Marketing Employee' }}</p>
          </div>
        </div>
      </div>

      <!-- Right Controls: Health Pill, Language, Theme, User -->
      <div class="flex items-center gap-3">
        <!-- Live API Health Pill -->
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800/80 border border-slate-700/60 text-xs">
          <div class="w-2 h-2 rounded-full" :class="healthStatus.status === 'healthy' ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"></div>
          <span class="text-slate-300 font-mono text-[11px]">API: {{ healthStatus.status || 'Checking...' }}</span>
        </div>

        <!-- Language Switcher -->
        <button 
          @click="toggleLanguage" 
          class="px-2.5 py-1.5 rounded-lg bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700/60 text-xs font-semibold text-slate-300 transition-colors"
          title="Toggle Language"
        >
          {{ locale === 'ar' ? 'English' : 'العربية' }}
        </button>

        <!-- User Profile Pill / Auth Button -->
        <div v-if="authUser" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-950/40 border border-emerald-500/30 text-xs">
          <div class="w-5 h-5 rounded-full bg-emerald-500 text-slate-950 font-bold flex items-center justify-center text-[10px]">
            {{ authUser.name.charAt(0).toUpperCase() }}
          </div>
          <span class="text-emerald-300 font-medium">{{ authUser.name }}</span>
          <button @click="handleLogout" class="text-slate-400 hover:text-red-400 text-[10px] ml-1">({{ locale === 'ar' ? 'خروج' : 'Logout' }})</button>
        </div>
      </div>
    </header>

    <!-- Main Workspace Layout -->
    <div class="flex flex-1 overflow-hidden">
      <!-- Collapsible Sidebar -->
      <aside class="w-64 border-r border-slate-800/80 bg-slate-900/40 p-4 flex flex-col justify-between hidden md:flex">
        <div class="space-y-1">
          <div class="px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">
            {{ locale === 'ar' ? 'وحدات المنصة' : 'Core Workspace' }}
          </div>

          <button 
            v-for="item in navItems" 
            :key="item.id"
            @click="activeNav = item.id"
            :class="[
              activeNav === item.id 
                ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 font-semibold' 
                : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-200 border-transparent',
              'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs border transition-all duration-150'
            ]"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm">{{ item.icon }}</span>
              <span>{{ locale === 'ar' ? item.nameAr : item.nameEn }}</span>
            </div>
            <span v-if="item.badge" class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-slate-800 text-slate-400 border border-slate-700">
              {{ item.badge }}
            </span>
          </button>
        </div>

        <!-- System Architecture Pill -->
        <div class="p-3.5 rounded-2xl bg-slate-800/40 border border-slate-700/40 text-xs space-y-1.5">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-slate-300">Laravel 11 + Vue 3</span>
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
          </div>
          <p class="text-[11px] text-slate-400">Sanctum Auth • Gemini AI • Modular Domains</p>
        </div>
      </aside>

      <!-- Main Content Area -->
      <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-8">
        <!-- Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-950/60 via-slate-900 to-slate-900 p-6 md:p-8 border border-emerald-500/20 shadow-2xl">
          <div class="relative z-10 max-w-3xl space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold">
              <span>✨</span>
              <span>{{ locale === 'ar' ? 'تم اكتمال المرحلة 0 — الهيكل الأساسي والمحركات' : 'Phase 0 Complete — Application Foundation & Architecture' }}</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">
              {{ locale === 'ar' ? 'مرحباً بك في Marketly-AI' : 'Welcome to Marketly-AI Command Center' }}
            </h1>
            <p class="text-sm text-slate-300 leading-relaxed">
              {{ locale === 'ar' 
                ? 'تم تأسيس الهيكل البرمجي بالكامل بنمط Domain-Driven مع تكامل عقود الذكاء الاصطناعي (Gemini)، ونظام النشر الاجتماعي، والمصادقة الأمنية عبر Laravel Sanctum.'
                : 'The production SaaS foundation is fully initialized with Domain-Driven Architecture, Gemini AI provider contracts, Social Publishing adapters, and token authentication.' 
              }}
            </p>
          </div>
        </div>

        <!-- Grid Cards: Live Health & Architecture Verification -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Card 1: Live API Health -->
          <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 shadow-lg space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ locale === 'ar' ? 'فحص صحة الخادم' : 'System Health' }}</span>
              <button @click="fetchHealth" class="text-xs text-emerald-400 hover:underline">Refresh</button>
            </div>
            <div class="space-y-2 font-mono text-xs">
              <div class="flex justify-between py-1 border-b border-slate-800">
                <span class="text-slate-400">Endpoint:</span>
                <span class="text-emerald-400">/api/v1/health</span>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-800">
                <span class="text-slate-400">Database:</span>
                <span class="text-slate-200">{{ healthStatus.database || 'Checking...' }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-800">
                <span class="text-slate-400">PHP Version:</span>
                <span class="text-slate-200">{{ healthStatus.php_version || 'PHP 8.2+' }}</span>
              </div>
              <div class="flex justify-between py-1">
                <span class="text-slate-400">API Version:</span>
                <span class="text-slate-200">v1 (Versioned)</span>
              </div>
            </div>
          </div>

          <!-- Card 2: AI & Social Provider Contracts -->
          <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 shadow-lg space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ locale === 'ar' ? 'عقود المزودين' : 'Provider Contracts' }}</span>
              <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Configured</span>
            </div>
            <div class="space-y-2 text-xs">
              <div class="flex items-center justify-between py-1 border-b border-slate-800">
                <span class="text-slate-300">AIProvider Contract</span>
                <span class="text-emerald-400 font-mono">Gemini Ready</span>
              </div>
              <div class="flex items-center justify-between py-1 border-b border-slate-800">
                <span class="text-slate-300">Image & Video Provider</span>
                <span class="text-emerald-400 font-mono">Standardized</span>
              </div>
              <div class="flex items-center justify-between py-1">
                <span class="text-slate-300">Social Publishers</span>
                <span class="text-emerald-400 font-mono">6 Platforms</span>
              </div>
            </div>
          </div>

          <!-- Card 3: Modular Domains -->
          <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 shadow-lg space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ locale === 'ar' ? 'النطاقات المعمارية' : 'Domain Modules' }}</span>
              <span class="text-[10px] px-2 py-0.5 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20">11 Domains</span>
            </div>
            <div class="flex flex-wrap gap-1.5">
              <span v-for="d in ['Identity', 'Tenancy', 'Brand', 'Strategy', 'Content', 'Creative', 'Publishing', 'Analytics', 'AI', 'Billing', 'Admin']" :key="d" class="px-2 py-1 rounded bg-slate-800 text-[10px] text-slate-300 border border-slate-700">
                {{ d }}
              </span>
            </div>
          </div>
        </div>

        <!-- Authentication Testing Console -->
        <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
          <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div>
              <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span>🔐</span>
                <span>{{ locale === 'ar' ? 'وحدة اختبار المصادقة (Sanctum Auth Test Console)' : 'Authentication Test Console (Sanctum Auth)' }}</span>
              </h2>
              <p class="text-xs text-slate-400 mt-1">
                {{ locale === 'ar' ? 'اختبار مسارات التسجيل وتسجيل الدخول واستدعاء /api/v1/me بشكل مباشر' : 'Test live registration, login, token creation, and /api/v1/me validation' }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Register / Login Form -->
            <div class="space-y-4 bg-slate-950/60 p-5 rounded-2xl border border-slate-800">
              <h3 class="text-xs font-bold uppercase text-slate-300">{{ authMode === 'register' ? 'Register New Account' : 'Login Existing Account' }}</h3>
              
              <div v-if="authMode === 'register'" class="space-y-1.5">
                <label class="text-[11px] font-medium text-slate-400">Full Name</label>
                <input v-model="authForm.name" type="text" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500" placeholder="Osama Sabry" />
              </div>

              <div class="space-y-1.5">
                <label class="text-[11px] font-medium text-slate-400">Email Address</label>
                <input v-model="authForm.email" type="email" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500" placeholder="admin@marketly.ai" />
              </div>

              <div class="space-y-1.5">
                <label class="text-[11px] font-medium text-slate-400">Password</label>
                <input v-model="authForm.password" type="password" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500" placeholder="••••••••" />
              </div>

              <div class="flex items-center gap-3 pt-2">
                <button @click="submitAuth" :disabled="authLoading" class="tactile-btn tactile-btn-primary text-xs w-full py-2.5">
                  <span v-if="authLoading">Processing...</span>
                  <span v-else>{{ authMode === 'register' ? 'Create Account & Get Token' : 'Authenticate & Sign In' }}</span>
                </button>
              </div>

              <div class="text-center pt-1">
                <button @click="authMode = authMode === 'register' ? 'login' : 'register'" class="text-[11px] text-slate-400 hover:text-emerald-400 underline">
                  {{ authMode === 'register' ? 'Already have an account? Sign in' : 'Need a new account? Register' }}
                </button>
              </div>
            </div>

            <!-- Token & Response Inspector -->
            <div class="space-y-3 bg-slate-950/60 p-5 rounded-2xl border border-slate-800 flex flex-col justify-between">
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold uppercase text-slate-300">API Response Stream</span>
                  <button @click="testMeEndpoint" :disabled="!authToken" class="text-[11px] text-emerald-400 hover:underline disabled:opacity-40">
                    Call /api/v1/me
                  </button>
                </div>
                <pre class="w-full h-44 p-3 rounded-xl bg-slate-900 border border-slate-800 text-[11px] font-mono text-emerald-400 overflow-y-auto">{{ authOutput || '// Submit form to view API JSON response...' }}</pre>
              </div>

              <div v-if="authToken" class="p-2.5 rounded-xl bg-emerald-950/30 border border-emerald-500/20 text-xs">
                <span class="text-emerald-400 font-bold text-[10px] block">ACTIVE BEARER TOKEN:</span>
                <span class="text-slate-300 font-mono text-[10px] truncate block">{{ authToken }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Master Roadmap Progress -->
        <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-4">
          <h2 class="text-base font-bold text-white flex items-center gap-2">
            <span>🗺️</span>
            <span>{{ locale === 'ar' ? 'خارطة طريق بناء النظام (Development Phases)' : 'Master Implementation Roadmap' }}</span>
          </h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <div v-for="phase in phases" :key="phase.num" :class="[
              phase.status === 'completed' ? 'border-emerald-500/40 bg-emerald-950/20' : 
              phase.status === 'next' ? 'border-amber-500/40 bg-amber-950/20' : 'border-slate-800 bg-slate-950/30',
              'p-3.5 rounded-2xl border flex flex-col justify-between gap-2'
            ]">
              <div>
                <div class="flex items-center justify-between">
                  <span class="text-[10px] font-bold uppercase text-slate-400">Phase {{ phase.num }}</span>
                  <span :class="[
                    phase.status === 'completed' ? 'text-emerald-400 bg-emerald-500/10' : 
                    phase.status === 'next' ? 'text-amber-400 bg-amber-500/10' : 'text-slate-500 bg-slate-800',
                    'text-[9px] font-bold px-1.5 py-0.5 rounded'
                  ]">{{ phase.status.toUpperCase() }}</span>
                </div>
                <h4 class="text-xs font-bold text-slate-200 mt-1">{{ locale === 'ar' ? phase.titleAr : phase.titleEn }}</h4>
              </div>
              <p class="text-[10px] text-slate-400">{{ locale === 'ar' ? phase.descAr : phase.descEn }}</p>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

const locale = ref<'en' | 'ar'>('en');
const isDark = ref(true);
const activeNav = ref('dashboard');

const healthStatus = ref<Record<string, any>>({});
const authMode = ref<'register' | 'login'>('register');
const authLoading = ref(false);
const authUser = ref<any>(null);
const authToken = ref<string>('');
const authOutput = ref<string>('');

const authForm = ref({
  name: 'Osama Sabry',
  email: 'admin@marketly.ai',
  password: 'Password123!',
});

const navItems = [
  { id: 'dashboard', nameEn: 'Dashboard', nameAr: 'لوحة القيادة', icon: '📊', badge: 'Ready' },
  { id: 'brand_brain', nameEn: 'Brand Brain', nameAr: 'عقل العلامة التجارية', icon: '🧠', badge: 'Phase 2' },
  { id: 'strategy', nameEn: 'AI Strategy', nameAr: 'استراتيجية الذكاء', icon: '🎯', badge: 'Phase 3' },
  { id: 'content', nameEn: 'Content Studio', nameAr: 'استوديو المحتوى', icon: '✍️', badge: 'Phase 4' },
  { id: 'creative', nameEn: 'Creative Studio', nameAr: 'استوديو الإبداع', icon: '🎨', badge: 'Phase 5' },
  { id: 'calendar', nameEn: 'Content Calendar', nameAr: 'تقويم النشر', icon: '📅', badge: 'Phase 6' },
  { id: 'publishing', nameEn: 'Social Publishing', nameAr: 'النشر التلقائي', icon: '⚡', badge: 'Phase 7' },
  { id: 'analytics', nameEn: 'Analytics & ROI', nameAr: 'التحليلات والعائد', icon: '📈', badge: 'Phase 9' },
  { id: 'assistant', nameEn: 'AI Assistant', nameAr: 'المساعد التسويقي', icon: '🤖', badge: 'Phase 10' },
  { id: 'settings', nameEn: 'Settings', nameAr: 'الإعدادات', icon: '⚙️', badge: '' },
];

const phases = [
  { num: '0', titleEn: 'Foundation', titleAr: 'الأساس المعماري', descEn: 'Laravel 11, Vue 3, Sanctum, Domain Contracts', descAr: 'لارافيل، فيو، سانكتوم، عقود النطاقات', status: 'completed' },
  { num: '1', titleEn: 'Tenancy & Identity', titleAr: 'الهوية وتعدد المستأجرين', descEn: 'Organizations, Roles, Policies, Isolation', descAr: 'المنظمات، الأدوار، العزل التام', status: 'next' },
  { num: '2', titleEn: 'Brand Brain', titleAr: 'عقل العلامة التجارية', descEn: 'Knowledge Ingestion, Facts, Approval', descAr: 'استيعاب المستندات، استخراج الحقائق', status: 'pending' },
  { num: '3', titleEn: 'AI Strategy', titleAr: 'استراتيجية الذكاء الاصطناعي', descEn: 'Pillars, 30-Day Plan, Gemini Agent', descAr: 'ركائز المحتوى، خطة 30 يوم', status: 'pending' },
  { num: '4', titleEn: 'Content Studio', titleAr: 'استوديو المحتوى', descEn: 'Captions, Hooks, Quality Agent, Dialects', descAr: 'النصوص، الخطافات، فحص الجودة', status: 'pending' },
  { num: '5', titleEn: 'Creative Studio', titleAr: 'استوديو الإبداع', descEn: 'Prompts, Aspect Ratios, Video Briefs', descAr: 'توليد الصور، مقاسات النشر، الفيديو', status: 'pending' },
  { num: '6', titleEn: 'Content Calendar', titleAr: 'تقويم المحتوى', descEn: 'Interactive Scheduling, Status Flow', descAr: 'جدولة المنشورات وتدفق الحالات', status: 'pending' },
  { num: '7-12', titleEn: 'Publishing & SaaS', titleAr: 'النشر والتوسع التجاري', descEn: 'OAuth, Workers, Analytics, Assistant, Billing', descAr: 'النشر التلقائي، التحليلات، الفوترة', status: 'pending' },
];

const toggleLanguage = () => {
  locale.value = locale.value === 'en' ? 'ar' : 'en';
};

const fetchHealth = async () => {
  try {
    const res = await axios.get('/api/v1/health');
    healthStatus.value = res.data.data;
  } catch (err: any) {
    healthStatus.value = { status: 'error', message: err.message };
  }
};

const submitAuth = async () => {
  authLoading.value = true;
  authOutput.value = 'Contacting server...';
  try {
    const endpoint = authMode.value === 'register' ? '/api/v1/auth/register' : '/api/v1/auth/login';
    const payload = authMode.value === 'register' 
      ? { name: authForm.value.name, email: authForm.value.email, password: authForm.value.password }
      : { email: authForm.value.email, password: authForm.value.password };

    const res = await axios.post(endpoint, payload);
    authOutput.value = JSON.stringify(res.data, null, 2);
    if (res.data?.data?.token) {
      authToken.value = res.data.data.token;
      authUser.value = res.data.data.user;
    }
  } catch (err: any) {
    authOutput.value = JSON.stringify(err.response?.data || { error: err.message }, null, 2);
  } finally {
    authLoading.value = false;
  }
};

const testMeEndpoint = async () => {
  if (!authToken.value) return;
  authOutput.value = 'Calling /api/v1/me with Bearer Token...';
  try {
    const res = await axios.get('/api/v1/me', {
      headers: {
        Authorization: `Bearer ${authToken.value}`,
      },
    });
    authOutput.value = JSON.stringify(res.data, null, 2);
    authUser.value = res.data.data.user;
  } catch (err: any) {
    authOutput.value = JSON.stringify(err.response?.data || { error: err.message }, null, 2);
  }
};

const handleLogout = async () => {
  if (!authToken.value) return;
  try {
    await axios.post('/api/v1/auth/logout', {}, {
      headers: {
        Authorization: `Bearer ${authToken.value}`,
      },
    });
  } catch (e) {
    // Ignore error
  }
  authToken.value = '';
  authUser.value = null;
  authOutput.value = 'Logged out successfully.';
};

onMounted(() => {
  fetchHealth();
});
</script>
