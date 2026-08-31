<template>
  <div :dir="isRtl ? 'rtl' : 'ltr'" :class="{'dark': isDark}" class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans transition-colors duration-200">
    
    <!-- 1. Public Marketing Site View -->
    <div v-if="currentMode === 'website'">
      <PublicWebsite 
        @open-auth="handleOpenAuth" 
      />
    </div>

    <!-- 2. Dedicated Full-Screen Authentication Portal View (No Dashboard Menu) -->
    <div v-else-if="currentMode === 'auth'" class="min-h-screen bg-slate-950 flex flex-col justify-between relative overflow-hidden">
      <!-- Top Simple Header -->
      <header class="h-16 border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-xl px-6 flex items-center justify-between relative z-10">
        <div class="flex items-center gap-3 cursor-pointer" @click="currentMode = 'website'">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center font-black text-white shadow-lg shadow-emerald-500/20">
            M
          </div>
          <div>
            <span class="font-extrabold text-white text-base tracking-tight">{{ t('common.appName') }}</span>
            <span class="text-[10px] text-emerald-400 block -mt-1 font-mono">AUTONOMOUS MARKETING</span>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Language Toggle -->
          <button 
            @click="toggleLanguage" 
            class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-bold text-slate-300 hover:text-emerald-400 transition-colors"
          >
            🌐 {{ currentLocale === 'ar' ? 'English' : 'العربية' }}
          </button>
          
          <!-- Back to Landing Page -->
          <button 
            @click="currentMode = 'website'" 
            class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300 hover:text-white transition-colors flex items-center gap-1.5"
          >
            <span>←</span>
            <span>{{ t('navigation.home') }}</span>
          </button>
        </div>
      </header>

      <!-- Center Auth Card -->
      <main class="flex-1 flex items-center justify-center p-4 py-12 relative z-10">
        <!-- Ambient background glows -->
        <div class="absolute w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute w-80 h-80 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none -bottom-10 right-10"></div>

        <div class="w-full max-w-md bg-slate-900/90 border border-slate-800/90 rounded-3xl p-8 space-y-6 shadow-2xl backdrop-blur-2xl relative">
          <!-- Auth Card Header & Mode Switcher -->
          <div class="space-y-4">
            <div class="text-center space-y-1">
              <h2 class="text-xl font-black text-white">
                {{ authMode === 'login' ? t('dashboard.loginMode') : (authMode === 'register' ? t('dashboard.registerMode') : t('dashboard.forgotMode')) }}
              </h2>
              <p class="text-xs text-slate-400">
                {{ authMode === 'login' ? 'Sign in to access your autonomous marketing workspace' : (authMode === 'register' ? 'Create your company workspace and start your AI engine' : 'Enter your email to receive recovery instructions') }}
              </p>
            </div>

            <!-- Tab Switcher -->
            <div class="grid grid-cols-3 gap-1 bg-slate-950 p-1 rounded-xl border border-slate-800 text-xs text-center">
              <button 
                @click="authMode = 'login'" 
                :class="[authMode === 'login' ? 'bg-emerald-500 text-slate-950 font-bold shadow' : 'text-slate-400 hover:text-slate-200']"
                class="py-1.5 rounded-lg text-xs transition-all"
              >
                {{ t('dashboard.loginMode') }}
              </button>
              <button 
                @click="authMode = 'register'" 
                :class="[authMode === 'register' ? 'bg-emerald-500 text-slate-950 font-bold shadow' : 'text-slate-400 hover:text-slate-200']"
                class="py-1.5 rounded-lg text-xs transition-all"
              >
                {{ t('dashboard.registerMode') }}
              </button>
              <button 
                @click="authMode = 'forgot'" 
                :class="[authMode === 'forgot' ? 'bg-emerald-500 text-slate-950 font-bold shadow' : 'text-slate-400 hover:text-slate-200']"
                class="py-1.5 rounded-lg text-xs transition-all"
              >
                {{ t('dashboard.forgotMode') }}
              </button>
            </div>
          </div>

          <!-- Auth Form -->
          <form @submit.prevent="handleAuthSubmit" class="space-y-4">
            <div v-if="authMode === 'register'" class="space-y-3">
              <div class="space-y-1.5">
                <label class="text-xs text-slate-300 font-semibold">{{ t('dashboard.fullName') }}</label>
                <input v-model="authForm.name" type="text" required placeholder="Osama Sabry" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none" />
              </div>
              <div class="space-y-1.5">
                <label class="text-xs text-slate-300 font-semibold">{{ t('tenancy.orgName') }}</label>
                <input v-model="authForm.company_name" type="text" placeholder="e.g. Apex Marketing Labs" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none" />
              </div>
              <div class="space-y-1.5">
                <label class="text-xs text-slate-300 font-semibold">Industry / Sector</label>
                <input v-model="authForm.industry" type="text" placeholder="e.g. E-Commerce, SaaS, Real Estate" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none" />
              </div>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs text-slate-300 font-semibold">{{ t('dashboard.email') }}</label>
              <input v-model="authForm.email" type="email" required placeholder="admin@company.com" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none" />
            </div>

            <div v-if="authMode !== 'forgot'" class="space-y-1.5">
              <label class="text-xs text-slate-300 font-semibold">{{ t('dashboard.password') }}</label>
              <input v-model="authForm.password" type="password" required placeholder="••••••••" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none" />
            </div>

            <button type="submit" :disabled="authLoading" class="tactile-btn tactile-btn-primary w-full py-3 text-xs font-bold shadow-lg shadow-emerald-500/20">
              {{ authLoading ? t('common.processing') : (authMode === 'login' ? t('dashboard.btnLogin') : (authMode === 'register' ? t('dashboard.btnRegister') : t('dashboard.btnForgot'))) }}
            </button>
          </form>

          <!-- Super Admin Quick Fill Demo Box -->
          <div v-if="authMode === 'login'" class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 space-y-2 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-amber-300 font-bold flex items-center gap-1">
                👑 Super Admin Credentials:
              </span>
              <button 
                @click="fillSuperAdminCreds" 
                type="button" 
                class="px-2 py-0.5 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 text-[10px] font-bold transition-colors"
              >
                ⚡ 1-Click Auto Fill
              </button>
            </div>
            <div class="text-[11px] text-slate-400 font-mono flex flex-col gap-0.5">
              <span>Email: <strong class="text-slate-200">admin@marketly.ai</strong></span>
              <span>Pass: <strong class="text-slate-200">Password123!</strong></span>
            </div>
          </div>
        </div>
      </main>

      <footer class="py-4 text-center text-xs text-slate-600 border-t border-slate-900">
        Marketly AI © 2026 • Autonomous Marketing Multi-Tenant Platform
      </footer>
    </div>

    <!-- 3. Onboarding Wizard View -->
    <div v-else-if="currentMode === 'onboarding'" class="min-h-screen bg-slate-950 flex flex-col justify-between">
      <header class="h-16 border-b border-slate-800/80 bg-slate-900/70 px-6 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center font-black text-white">M</div>
          <span class="font-extrabold text-white">{{ t('common.appName') }}</span>
        </div>
        <button @click="currentMode = 'app'; activeNav = 'dashboard'" class="text-xs text-slate-400 hover:text-white">{{ t('common.skip') }} →</button>
      </header>
      <main class="flex-1 flex items-center justify-center">
        <OnboardingWizard 
          :auth-token="authToken" 
          :organization-id="currentOrg?.id" 
          @completed="currentMode = 'app'; activeNav = 'strategy'" 
        />
      </main>
      <footer class="py-4 text-center text-xs text-slate-600">Marketly AI Onboarding Hub</footer>
    </div>

    <!-- 4. Dedicated Super Admin Console View (Isolated Root Shell) -->
    <div v-else-if="authUser?.is_super_admin && !isImpersonating">
      <SuperAdminShell 
        :auth-token="authToken" 
        :auth-user="authUser" 
        @impersonate="handleImpersonateSuccess" 
        @logout="handleLogout" 
        @view-website="currentMode = 'website'" 
        @toggle-lang="toggleLanguage" 
      />
    </div>

    <!-- 5. Authenticated Tenant SaaS Workspace & Application -->
    <div v-else class="flex flex-col min-h-screen">
      <!-- Top Navigation Header -->
      <header class="h-16 border-b border-slate-800/80 bg-slate-900/70 backdrop-blur-md px-6 flex items-center justify-between sticky top-0 z-50">
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2.5 cursor-pointer" @click="activeNav = 'dashboard'">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-950/50">
              <span class="text-white font-extrabold text-lg">M</span>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-base tracking-tight bg-gradient-to-r from-white via-slate-200 to-emerald-400 bg-clip-text text-transparent">
                  {{ t('common.appName') }}
                </span>
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                  {{ t('dashboard.phaseBadge') }}
                </span>
              </div>
              <p class="text-[11px] text-slate-400">{{ t('common.tagline') }}</p>
            </div>
          </div>

          <!-- Organization Switcher Dropdown (If authenticated) -->
          <div v-if="authUser && userOrgs.length > 0" class="relative hidden sm:block">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-800/90 border border-slate-700/80 text-xs">
              <span class="text-slate-400">🏢</span>
              <select 
                :value="currentOrg?.id" 
                @change="handleOrgSwitch(Number(($event.target as HTMLSelectElement).value))"
                class="bg-transparent text-emerald-400 font-bold text-xs focus:outline-none cursor-pointer"
              >
                <option v-for="org in userOrgs" :key="org.id" :value="org.id" class="bg-slate-900 text-slate-200">
                  {{ org.name }} ({{ org.role }})
                </option>
              </select>
              <button @click="showNewOrgModal = true" class="text-slate-400 hover:text-emerald-400 text-xs px-1" :title="t('tenancy.createOrg')">
                ➕
              </button>
            </div>
          </div>

          <!-- Brand Switcher Dropdown (Phase E Multi-Brand Support) -->
          <div v-if="authUser && orgBrands.length > 0" class="relative hidden sm:block">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-800/90 border border-teal-500/30 text-xs shadow-lg shadow-teal-950/20">
              <span class="text-teal-400">🏷️</span>
              <select 
                :value="activeBrandId" 
                @change="handleBrandSwitch(Number(($event.target as HTMLSelectElement).value))"
                class="bg-transparent text-teal-300 font-bold text-xs focus:outline-none cursor-pointer pr-1"
              >
                <option v-for="brand in orgBrands" :key="brand.id" :value="brand.id" class="bg-slate-900 text-slate-200">
                  {{ brand.business_name }}
                </option>
              </select>
              <button 
                @click="showNewBrandModal = true" 
                class="px-2 py-0.5 rounded-lg bg-teal-500/20 hover:bg-teal-500/30 text-teal-300 font-bold text-[11px] flex items-center gap-1 transition-colors border border-teal-500/30" 
                :title="currentLocale === 'ar' ? 'إضافة براند جديد' : 'Create New Brand'"
              >
                <span>➕</span>
                <span>{{ currentLocale === 'ar' ? 'براند جديد' : 'New Brand' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Right Controls: Public Site Switcher, Language, User -->
        <div class="flex items-center gap-3">
          <!-- Switch to Public Site Preview -->
          <button 
            @click="currentMode = 'website'" 
            class="px-2.5 py-1.5 rounded-lg bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700/60 text-xs font-semibold text-slate-300 hover:text-white transition-colors"
          >
            🌐 {{ t('navigation.home') }}
          </button>

          <!-- Language Switcher Button -->
          <button 
            @click="toggleLanguage" 
            class="px-2.5 py-1.5 rounded-lg bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700/60 text-xs font-semibold text-emerald-400 transition-colors"
            :title="currentLocale === 'ar' ? 'Switch to English' : 'التحويل إلى العربية'"
          >
            {{ currentLocale === 'ar' ? 'English (LTR)' : 'العربية (RTL)' }}
          </button>

          <!-- User Profile Pill / Auth Button -->
          <div v-if="authUser" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-950/40 border border-emerald-500/30 text-xs">
            <div class="w-5 h-5 rounded-full bg-emerald-500 text-slate-950 font-bold flex items-center justify-center text-[10px]">
              {{ authUser.name.charAt(0).toUpperCase() }}
            </div>
            <div class="flex flex-col">
              <span class="text-emerald-300 font-medium leading-none">{{ authUser.name }}</span>
              <span class="text-[9px] text-emerald-400/80 uppercase font-bold">{{ userRole }}</span>
            </div>
            <button @click="handleLogout" class="text-slate-400 hover:text-red-400 text-[10px] mx-1">
              ({{ t('common.logout') }})
            </button>
          </div>
          <div v-else>
            <button @click="currentMode = 'auth'" class="tactile-btn tactile-btn-primary text-xs px-4 py-1.5">
              {{ t('dashboard.loginMode') }}
            </button>
          </div>
        </div>
      </header>

      <!-- Impersonation Notice Banner (Super Admin Mode) -->
      <div v-if="isImpersonating" class="bg-gradient-to-r from-amber-500/20 via-amber-500/30 to-amber-500/20 border-b border-amber-500/40 px-6 py-2.5 flex items-center justify-between text-xs text-amber-200 shadow-md">
        <div class="flex items-center gap-2 font-semibold">
          <span>⚠️</span>
          <span>{{ currentLocale === 'ar' ? 'وضع المحاكاة النشط: أنت تتصفح الآن كـ' : 'Active Impersonation Mode: Browsing as' }} <strong class="text-white font-black underline">{{ currentOrg?.name }}</strong></span>
        </div>
        <button 
          @click="exitImpersonation" 
          class="px-4 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/20 transition-all flex items-center gap-1.5"
        >
          <span>👑</span>
          <span>{{ currentLocale === 'ar' ? 'العودة للوحة تحكم السوبر أدمن' : 'Exit Impersonation' }}</span>
        </button>
      </div>

      <!-- Main Workspace Layout -->
      <div class="flex flex-1 overflow-hidden">
        <!-- Collapsible Sidebar -->
        <aside class="w-64 border-r border-slate-800/80 bg-slate-900/40 p-4 flex flex-col justify-between hidden md:flex">
          <div class="space-y-1">
            <div class="px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">
              {{ t('navigation.workspace') }}
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
                <span>{{ t(item.titleKey) }}</span>
              </div>
              <span v-if="item.badge" class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-slate-800 text-slate-400 border border-slate-700">
                {{ item.badge }}
              </span>
            </button>
          </div>

          <!-- Current Tenant / Architecture Pill -->
          <div class="p-3.5 rounded-2xl bg-slate-800/40 border border-slate-700/40 text-xs space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold text-slate-300 truncate max-w-[140px]">{{ currentOrg?.name || 'Multi-Tenant Ready' }}</span>
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            </div>
            <p class="text-[10px] text-slate-400">Role: <span class="text-emerald-400 uppercase font-bold">{{ userRole }}</span> • {{ permissionsList.length }} Perms</p>
          </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-8">
          <!-- 1. Dashboard View -->
          <div v-if="activeNav === 'dashboard'">
            <DashboardView 
              :auth-token="authToken"
              :organization-id="currentOrg?.id"
              :brand-id="activeBrandId || undefined"
              :current-org="currentOrg"
              @navigate="activeNav = $event"
              @start-onboarding="currentMode = 'onboarding'"
            />
          </div>

          <!-- 2. Brand Brain View -->
          <div v-else-if="activeNav === 'brand_brain'">
            <BrandBrainHub 
              :auth-token="authToken" 
              :organization-id="currentOrg?.id"
              :brand-id="activeBrandId || undefined"
              @brand-updated="fetchBrands"
            />
          </div>

          <!-- 3. Strategy Hub View -->
          <div v-else-if="activeNav === 'strategy'">
            <StrategyHub 
              :auth-token="authToken" 
              :organization-id="currentOrg?.id"
              :brand-id="activeBrandId || undefined"
              @navigate-content="activeNav = 'content'"
            />
          </div>

          <!-- 4. Content Studio View (Phase 4) -->
          <div v-else-if="activeNav === 'content'">
            <ContentStudioView 
              :auth-token="authToken" 
              :organization-id="currentOrg?.id"
              :brand-id="activeBrandId || undefined"
            />
          </div>

          <!-- 5. Creative Studio View (Phase 5) -->
          <div v-else-if="activeNav === 'creative'">
            <CreativeStudioView 
              :auth-token="authToken" 
              :organization-id="currentOrg?.id"
              :brand-id="activeBrandId || undefined"
            />
          </div>

          <!-- 6. Marketing Calendar View (Phase 6) -->
          <div v-else-if="activeNav === 'calendar'">
            <CalendarView 
              :auth-token="authToken" 
              :organization-id="currentOrg?.id"
              :brand-id="activeBrandId || undefined"
            />
          </div>

          <!-- 7. Social Publishing View (Phase 7) -->
          <div v-else-if="activeNav === 'social'">
            <PublishingChannelsView 
              :auth-token="authToken" 
              :organization-id="currentOrg?.id"
              :brand-id="activeBrandId || undefined"
            />
          </div>

          <!-- 8. Analytics & ROI View (Phase 8) -->
          <div v-else-if="activeNav === 'analytics'">
            <AnalyticsHubView 
              :auth-token="authToken" 
              :organization-id="currentOrg?.id"
              :brand-id="activeBrandId || undefined"
            />
          </div>

          <!-- 9. Billing View -->
          <div v-else-if="activeNav === 'billing'">
            <BillingView 
              :auth-token="authToken" 
              :organization-id="currentOrg?.id"
            />
          </div>

          <!-- 10. Team View -->
          <div v-else-if="activeNav === 'team'">
            <TeamView 
              :auth-token="authToken" 
              :organization-id="currentOrg?.id"
            />
          </div>

          <!-- 11. Settings View -->
          <div v-else-if="activeNav === 'settings'">
            <SettingsView 
              :auth-token="authToken" 
              :organization-id="currentOrg?.id" 
              :current-user="authUser"
              @logout="handleLogout"
              @org-updated="currentOrg = $event"
            />
          </div>

          <!-- 12. Super Admin Dashboard View -->
          <div v-else-if="activeNav === 'super_admin'">
            <SuperAdminDashboardView 
              :auth-token="authToken"
              :organization-id="currentOrg?.id"
              @impersonate-success="handleImpersonateSuccess"
            />
          </div>
        </main>
      </div>
    </div>

    <!-- Create Organization Modal -->
    <div v-if="showNewOrgModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
        <h3 class="text-base font-bold text-white">{{ t('tenancy.createOrg') }}</h3>
        <div class="space-y-1.5">
          <label class="text-xs text-slate-400">{{ t('tenancy.orgName') }}</label>
          <input v-model="newOrgForm.name" type="text" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="Acme Labs" />
        </div>
        <div class="flex items-center justify-end gap-2 pt-2">
          <button @click="showNewOrgModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-xs text-slate-300">{{ t('common.cancel') }}</button>
          <button @click="handleCreateOrg" class="tactile-btn tactile-btn-primary text-xs px-5 py-2">{{ t('common.save') }}</button>
        </div>
      </div>
    </div>

    <!-- Create Brand Modal (Multi-Brand Support) -->
    <div v-if="showNewBrandModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <span>🏷️</span>
            <span>{{ currentLocale === 'ar' ? 'إنشاء براند جديد داخل الشركة' : 'Create New Brand' }}</span>
          </h3>
          <button @click="showNewBrandModal = false" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <div v-if="brandModalError" class="p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs flex items-center gap-2">
          <span>⚠️</span>
          <span>{{ brandModalError }}</span>
        </div>

        <div class="space-y-3">
          <div class="space-y-1.5">
            <label class="text-xs text-slate-300 font-semibold">Brand / Business Name *</label>
            <input v-model="newBrandForm.business_name" type="text" required class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-teal-500 outline-none" placeholder="e.g. Apex Health" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="text-xs text-slate-300 font-semibold">Industry</label>
              <input v-model="newBrandForm.industry" type="text" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-teal-500 outline-none" placeholder="Healthcare" />
            </div>
            <div class="space-y-1.5">
              <label class="text-xs text-slate-300 font-semibold">Business Type</label>
              <select v-model="newBrandForm.business_type" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-teal-500 outline-none">
                <option value="B2B">B2B</option>
                <option value="B2C">B2C</option>
                <option value="D2C">D2C</option>
                <option value="Agency">Agency</option>
              </select>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
          <button @click="showNewBrandModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-xs text-slate-300 hover:bg-slate-700">
            {{ t('common.cancel') }}
          </button>
          <button @click="handleCreateBrand" :disabled="brandModalLoading" class="tactile-btn tactile-btn-primary text-xs px-5 py-2">
            <span v-if="brandModalLoading">⏳</span>
            <span v-else>➕ {{ currentLocale === 'ar' ? 'إنشاء البراند' : 'Create Brand' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { t, currentLocale, setLocale, isRtl } from './i18n';
import PublicWebsite from './components/PublicWebsite.vue';
import OnboardingWizard from './components/OnboardingWizard.vue';
import SuperAdminShell from './components/SuperAdminShell.vue';
import DashboardView from './components/DashboardView.vue';
import BrandBrainHub from './components/BrandBrainHub.vue';
import StrategyHub from './components/StrategyHub.vue';
import ContentStudioView from './components/ContentStudioView.vue';
import CreativeStudioView from './components/CreativeStudioView.vue';
import CalendarView from './components/CalendarView.vue';
import PublishingChannelsView from './components/PublishingChannelsView.vue';
import AnalyticsHubView from './components/AnalyticsHubView.vue';
import BillingView from './components/BillingView.vue';
import TeamView from './components/TeamView.vue';
import SettingsView from './components/SettingsView.vue';
import SuperAdminDashboardView from './components/SuperAdminDashboardView.vue';

const isDark = ref(true);
const currentMode = ref<'website' | 'auth' | 'onboarding' | 'app'>('website');
const activeNav = ref('dashboard');

const authUser = ref<any>(null);
const authToken = ref<string>('');
const userOrgs = ref<any[]>([]);
const currentOrg = ref<any>(null);
const userRole = ref<string>('owner');
const permissionsList = ref<string[]>([]);
const healthStatus = ref<{ status?: string }>({ status: 'healthy' });

const isImpersonating = ref(false);
const originalAdminOrg = ref<any>(null);

// Phase E: Multi-Brand State
const orgBrands = ref<any[]>([]);
const activeBrandId = ref<number | null>(null);
const showNewBrandModal = ref(false);
const brandModalLoading = ref(false);
const brandModalError = ref('');
const newBrandForm = ref({ business_name: '', industry: 'Technology', business_type: 'B2B' });

const activeBrand = computed(() => {
  return orgBrands.value.find(b => b.id === activeBrandId.value) || orgBrands.value[0] || null;
});

const authMode = ref<'login' | 'register' | 'forgot'>('login');
const authLoading = ref(false);
const authForm = ref({ name: '', email: '', password: '', company_name: '', industry: '' });

const showNewOrgModal = ref(false);
const newOrgForm = ref({ name: '' });

const baseNavItems = [
  { id: 'dashboard', icon: '📊', titleKey: 'navigation.dashboard' },
  { id: 'brand_brain', icon: '🧠', titleKey: 'navigation.brandBrain' },
  { id: 'strategy', icon: '🎯', titleKey: 'navigation.strategy' },
  { id: 'content', icon: '✍️', titleKey: 'navigation.content', badge: 'Phase 4' },
  { id: 'creative', icon: '🎨', titleKey: 'navigation.creative', badge: 'Phase 5' },
  { id: 'calendar', icon: '📅', titleKey: 'navigation.calendar', badge: 'Phase 6' },
  { id: 'social', icon: '📡', titleKey: 'navigation.publishing', badge: 'Phase 7' },
  { id: 'analytics', icon: '📈', titleKey: 'navigation.analytics', badge: 'Phase 8' },
  { id: 'team', icon: '👥', titleKey: 'navigation.team' },
  { id: 'billing', icon: '💳', titleKey: 'navigation.billing' },
  { id: 'settings', icon: '⚙️', titleKey: 'navigation.settings' },
];

const navItems = computed(() => baseNavItems);

const toggleLanguage = () => {
  setLocale(currentLocale.value === 'ar' ? 'en' : 'ar');
};

const handleOpenAuth = (mode: 'login' | 'register', planSlug?: string) => {
  authMode.value = mode;
  currentMode.value = 'auth';
};

const fillSuperAdminCreds = () => {
  authForm.value.email = 'admin@marketly.ai';
  authForm.value.password = 'Password123!';
};

const getHeaders = () => ({
  Authorization: `Bearer ${authToken.value}`,
  'X-Organization-Id': String(currentOrg.value?.id || ''),
  ...(activeBrandId.value ? { 'X-Brand-Id': String(activeBrandId.value) } : {}),
});

// Configure Axios Request Interceptor for Tenant Context
axios.interceptors.request.use((config) => {
  if (authToken.value) {
    config.headers.Authorization = `Bearer ${authToken.value}`;
  }
  if (currentOrg.value?.id) {
    config.headers['X-Organization-Id'] = String(currentOrg.value.id);
  }
  if (activeBrandId.value) {
    config.headers['X-Brand-Id'] = String(activeBrandId.value);
  }
  return config;
});

const handleAuthSubmit = async () => {
  authLoading.value = true;
  try {
    if (authMode.value === 'register') {
      const res = await axios.post('/api/v1/auth/register', {
        name: authForm.value.name,
        email: authForm.value.email,
        password: authForm.value.password,
        password_confirmation: authForm.value.password,
        company_name: authForm.value.company_name,
        industry: authForm.value.industry,
      });
      authToken.value = res.data?.data?.token;
      authUser.value = res.data?.data?.user;
      localStorage.setItem('marketly_token', authToken.value);
      await fetchUserData();
      currentMode.value = 'onboarding';
    } else if (authMode.value === 'login') {
      const res = await axios.post('/api/v1/auth/login', {
        email: authForm.value.email,
        password: authForm.value.password,
      });
      authToken.value = res.data?.data?.token;
      authUser.value = res.data?.data?.user;
      localStorage.setItem('marketly_token', authToken.value);
      await fetchUserData();
      currentMode.value = 'app';
      activeNav.value = authUser.value?.is_super_admin ? 'super_admin' : 'dashboard';
    } else {
      await axios.post('/api/v1/auth/forgot-password', { email: authForm.value.email });
      alert('Password reset link sent to your email.');
    }
  } catch (err: any) {
    alert(err.response?.data?.message || 'Authentication error.');
  } finally {
    authLoading.value = false;
  }
};

const fetchBrands = async () => {
  if (!authToken.value || !currentOrg.value?.id) return;
  try {
    const res = await axios.get('/api/v1/brand/brands', {
      headers: {
        Authorization: `Bearer ${authToken.value}`,
        'X-Organization-Id': String(currentOrg.value.id),
      },
    });
    orgBrands.value = res.data?.data?.brands || [];
    
    // Restore or initialize active brand
    const savedBrandId = localStorage.getItem('marketly_brand_id');
    if (savedBrandId && orgBrands.value.some(b => b.id === Number(savedBrandId))) {
      activeBrandId.value = Number(savedBrandId);
    } else if (orgBrands.value.length > 0) {
      activeBrandId.value = orgBrands.value[0].id;
      localStorage.setItem('marketly_brand_id', String(activeBrandId.value));
    } else {
      activeBrandId.value = null;
    }
  } catch (err) {
    console.error('Failed to fetch organization brands', err);
  }
};

const handleBrandSwitch = (brandId: number) => {
  activeBrandId.value = brandId;
  localStorage.setItem('marketly_brand_id', String(brandId));
};

const handleCreateBrand = async () => {
  if (!newBrandForm.value.business_name) return;
  brandModalLoading.value = true;
  brandModalError.value = '';
  try {
    const res = await axios.post('/api/v1/brand', {
      business_name: newBrandForm.value.business_name,
      industry: newBrandForm.value.industry,
      business_type: newBrandForm.value.business_type,
    }, {
      headers: {
        Authorization: `Bearer ${authToken.value}`,
        'X-Organization-Id': String(currentOrg.value.id),
      },
    });
    
    showNewBrandModal.value = false;
    newBrandForm.value.business_name = '';
    await fetchBrands();
    if (res.data?.data?.profile?.id) {
      handleBrandSwitch(res.data.data.profile.id);
    }
  } catch (err: any) {
    if (err.response?.status === 403) {
      brandModalError.value = err.response?.data?.message || 'Your subscription plan limit has been reached. Please upgrade your plan to create more brands.';
    } else {
      brandModalError.value = err.response?.data?.message || 'Failed to create new brand profile.';
    }
  } finally {
    brandModalLoading.value = false;
  }
};

const fetchUserData = async () => {
  if (!authToken.value) return;
  try {
    const meRes = await axios.get('/api/v1/me', { headers: { Authorization: `Bearer ${authToken.value}` } });
    authUser.value = meRes.data?.data?.user;
    userOrgs.value = meRes.data?.data?.organizations || [];
    currentOrg.value = meRes.data?.data?.current_organization || userOrgs.value[0];
    userRole.value = meRes.data?.data?.role || 'owner';
    permissionsList.value = meRes.data?.data?.permissions || [];
    await fetchBrands();
  } catch (err) {
    console.error('Failed to fetch user profile', err);
  }
};

const handleOrgSwitch = async (orgId: number) => {
  try {
    const res = await axios.post(`/api/v1/organizations/${orgId}/switch`, {}, { headers: { Authorization: `Bearer ${authToken.value}` } });
    currentOrg.value = res.data?.data?.organization;
    await fetchUserData();
  } catch (err) {
    console.error('Org switch failed', err);
  }
};

const handleCreateOrg = async () => {
  if (!newOrgForm.value.name) return;
  try {
    const res = await axios.post('/api/v1/organizations', { name: newOrgForm.value.name }, { headers: { Authorization: `Bearer ${authToken.value}` } });
    showNewOrgModal.value = false;
    newOrgForm.value.name = '';
    currentOrg.value = res.data?.data?.organization;
    await fetchUserData();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to create organization.');
  }
};

const handleImpersonateSuccess = async (data: any) => {
  if (!originalAdminOrg.value) {
    originalAdminOrg.value = currentOrg.value;
  }
  isImpersonating.value = true;
  currentOrg.value = data.organization;
  await fetchUserData();
  activeNav.value = 'dashboard';
};

const exitImpersonation = async () => {
  if (originalAdminOrg.value) {
    await handleOrgSwitch(originalAdminOrg.value.id);
  }
  isImpersonating.value = false;
  activeNav.value = 'super_admin';
};

const handleLogout = async () => {
  try {
    await axios.post('/api/v1/auth/logout', {}, { headers: { Authorization: `Bearer ${authToken.value}` } });
  } catch (e) {}
  authToken.value = '';
  authUser.value = null;
  isImpersonating.value = false;
  localStorage.removeItem('marketly_token');
  currentMode.value = 'website';
};

onMounted(async () => {
  const savedToken = localStorage.getItem('marketly_token');
  if (savedToken) {
    authToken.value = savedToken;
    await fetchUserData();
    if (authUser.value) {
      currentMode.value = 'app';
      activeNav.value = authUser.value?.is_super_admin ? 'super_admin' : 'dashboard';
    }
  }
});
</script>
