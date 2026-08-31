<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans">
    <!-- Top Master Header -->
    <header class="h-16 border-b border-amber-500/20 bg-slate-900/80 backdrop-blur-xl px-6 flex items-center justify-between sticky top-0 z-50 shadow-lg">
      <div class="flex items-center gap-4">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-amber-500 via-yellow-400 to-amber-600 flex items-center justify-center font-black text-slate-950 shadow-lg shadow-amber-500/20 text-lg">
            👑
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-black text-base tracking-tight text-white">Marketly-AI</span>
              <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase tracking-wider">
                SUPER ADMIN CONSOLE
              </span>
            </div>
            <p class="text-[10px] text-slate-400 font-mono">Platform Governance & Infrastructure Control</p>
          </div>
        </div>
      </div>

      <!-- Header Controls -->
      <div class="flex items-center gap-3">
        <!-- Switch to Public Site Preview -->
        <button 
          @click="$emit('view-website')" 
          class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-slate-300 hover:text-white transition-colors flex items-center gap-1.5"
        >
          <span>🌐</span>
          <span>{{ currentLocale === 'ar' ? 'الموقع العام' : 'Public Site' }}</span>
        </button>

        <!-- Language Toggle -->
        <button 
          @click="$emit('toggle-lang')" 
          class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-bold text-amber-300 transition-colors"
        >
          {{ currentLocale === 'ar' ? 'English (LTR)' : 'العربية (RTL)' }}
        </button>

        <!-- Admin Profile & Logout -->
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs">
          <div class="w-5 h-5 rounded-full bg-amber-400 text-slate-950 font-black flex items-center justify-center text-[10px]">
            A
          </div>
          <div class="flex flex-col">
            <span class="text-amber-200 font-bold leading-none">{{ authUser?.name || 'Super Administrator' }}</span>
            <span class="text-[9px] text-amber-400/80 font-mono">ROOT_ADMIN</span>
          </div>
          <button @click="$emit('logout')" class="text-slate-400 hover:text-red-400 text-[11px] font-bold mx-1">
            ({{ currentLocale === 'ar' ? 'خروج' : 'Logout' }})
          </button>
        </div>
      </div>
    </header>

    <!-- Main Super Admin Layout -->
    <div class="flex flex-1 overflow-hidden">
      <!-- Admin Sidebar Navigation -->
      <aside class="w-64 border-r border-slate-800/80 bg-slate-900/60 p-4 flex flex-col justify-between hidden md:flex">
        <div class="space-y-1.5">
          <div class="px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-amber-400/80 font-mono">
            {{ currentLocale === 'ar' ? 'قائمة إدارة المنصة' : 'PLATFORM CONTROLS' }}
          </div>

          <button 
            v-for="tab in adminTabs" 
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id 
                ? 'bg-amber-500/15 text-amber-300 border-amber-500/40 font-bold shadow-md shadow-amber-500/5' 
                : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200 border-transparent',
              'w-full flex items-center justify-between px-3.5 py-3 rounded-xl text-xs border transition-all'
            ]"
          >
            <div class="flex items-center gap-3">
              <span class="text-base">{{ tab.icon }}</span>
              <span>{{ currentLocale === 'ar' ? tab.nameAr : tab.nameEn }}</span>
            </div>
            <span v-if="tab.badge" class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">
              {{ tab.badge }}
            </span>
          </button>
        </div>

        <!-- System Architecture Card -->
        <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-amber-500/20 text-xs space-y-1.5 font-mono">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-amber-300">SYSTEM HEALTH</span>
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          </div>
          <p class="text-[10px] text-slate-400">Environment: <span class="text-slate-200">Production Ready</span></p>
          <p class="text-[10px] text-slate-400">Tenancy: <span class="text-emerald-400">Strict Isolated</span></p>
        </div>
      </aside>

      <!-- Main Admin Content Area -->
      <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-8">
        
        <!-- Tab 1: Overview & Dashboard -->
        <div v-if="activeTab === 'overview'" class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-extrabold text-white flex items-center gap-2">
                <span>📊</span> {{ currentLocale === 'ar' ? 'نظرة عامة ومؤشرات أداء المنصة' : 'Global Platform KPIs & Metrics' }}
              </h2>
              <p class="text-xs text-slate-400 mt-0.5">Real-time revenue, active organizations, and system throughput</p>
            </div>
            <button @click="fetchKpis" class="px-3.5 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-300 hover:text-white transition-colors flex items-center gap-1.5">
              <span>🔄</span> {{ currentLocale === 'ar' ? 'تحديث' : 'Refresh' }}
            </button>
          </div>

          <!-- KPI Cards Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-3xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-2">
              <div class="flex items-center justify-between text-slate-400 text-xs">
                <span>{{ currentLocale === 'ar' ? 'إجمالي الشركات' : 'Total Organizations' }}</span>
                <span class="text-lg">🏢</span>
              </div>
              <div class="text-2xl font-black text-white font-mono">{{ kpis.total_organizations || 0 }}</div>
              <div class="text-[11px] text-emerald-400 flex items-center gap-1">
                <span>●</span> {{ kpis.active_organizations || 0 }} {{ currentLocale === 'ar' ? 'نشطة' : 'Active' }}
              </div>
            </div>

            <div class="p-5 rounded-3xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-2">
              <div class="flex items-center justify-between text-slate-400 text-xs">
                <span>{{ currentLocale === 'ar' ? 'الإيراد الشهري المتوقع (MRR)' : 'Estimated MRR' }}</span>
                <span class="text-lg">💰</span>
              </div>
              <div class="text-2xl font-black text-emerald-400 font-mono">${{ kpis.estimated_mrr || 0 }}</div>
              <div class="text-[11px] text-slate-400">{{ kpis.active_subscriptions || 0 }} {{ currentLocale === 'ar' ? 'اشتراك نشط' : 'Active Subscriptions' }}</div>
            </div>

            <div class="p-5 rounded-3xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-2">
              <div class="flex items-center justify-between text-slate-400 text-xs">
                <span>{{ currentLocale === 'ar' ? 'المنشورات المولّدة' : 'Total Posts Created' }}</span>
                <span class="text-lg">✍️</span>
              </div>
              <div class="text-2xl font-black text-cyan-400 font-mono">{{ kpis.total_posts || 0 }}</div>
              <div class="text-[11px] text-slate-400">{{ kpis.published_posts || 0 }} {{ currentLocale === 'ar' ? 'تم نشره على المنصات' : 'Published live' }}</div>
            </div>

            <div class="p-5 rounded-3xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-2">
              <div class="flex items-center justify-between text-slate-400 text-xs">
                <span>{{ currentLocale === 'ar' ? 'استدعاءات الذكاء الاصطناعي' : 'AI Engine Invocations' }}</span>
                <span class="text-lg">🧠</span>
              </div>
              <div class="text-2xl font-black text-amber-400 font-mono">{{ kpis.ai_generations_count || 0 }}</div>
              <div class="text-[11px] text-emerald-400">Gemini 2.0 Flash Active</div>
            </div>
          </div>

          <!-- Plan Distribution -->
          <div class="space-y-3">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
              <span>💳</span> {{ currentLocale === 'ar' ? 'توزيع الاشتراكات على الخطط' : 'Subscription Distribution by Plan' }}
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <div 
                v-for="plan in planDistribution" 
                :key="plan.plan_id"
                class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-2"
              >
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-white capitalize">{{ plan.name }}</span>
                  <span class="text-xs font-mono text-cyan-400 font-bold">${{ plan.price_monthly }}/mo</span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-400 pt-2 border-t border-slate-800">
                  <span>{{ plan.subscribers_count }} {{ currentLocale === 'ar' ? 'مشترك' : 'subs' }}</span>
                  <span class="font-bold text-emerald-400 font-mono">${{ plan.revenue_contribution }}/mo</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 2: Organizations Management -->
        <div v-else-if="activeTab === 'organizations'" class="space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h2 class="text-xl font-extrabold text-white flex items-center gap-2">
                <span>🏢</span> {{ currentLocale === 'ar' ? 'إدارة الشركات والمستأجرين' : 'Companies & Tenants Management' }}
              </h2>
              <p class="text-xs text-slate-400 mt-0.5">Control organizations, view resource limits, or login as company</p>
            </div>

            <!-- Search & Filters -->
            <div class="flex items-center gap-2">
              <input 
                v-model="searchQuery" 
                @input="debounceFetchOrgs"
                type="text" 
                :placeholder="currentLocale === 'ar' ? 'بحث بالاسم أو النطاق...' : 'Search organization or slug...'" 
                class="px-3.5 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-amber-500 outline-none w-56 sm:w-64"
              />
              <select 
                v-model="statusFilter" 
                @change="fetchOrganizations"
                class="px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-amber-500 outline-none"
              >
                <option value="all">{{ currentLocale === 'ar' ? 'كل الحالات' : 'All Status' }}</option>
                <option value="active">{{ currentLocale === 'ar' ? 'النشطة فقط' : 'Active Only' }}</option>
                <option value="suspended">{{ currentLocale === 'ar' ? 'المعلقة فقط' : 'Suspended Only' }}</option>
              </select>
            </div>
          </div>

          <!-- Organizations Table -->
          <div class="bg-slate-900/70 rounded-3xl border border-slate-800 overflow-hidden shadow-2xl">
            <table class="w-full text-left text-xs" :dir="currentLocale === 'ar' ? 'rtl' : 'ltr'">
              <thead class="bg-slate-950/80 text-slate-400 uppercase text-[10px] font-bold border-b border-slate-800">
                <tr>
                  <th class="p-4">{{ currentLocale === 'ar' ? 'الشركة' : 'Company' }}</th>
                  <th class="p-4">{{ currentLocale === 'ar' ? 'الخطة الحالية' : 'Plan' }}</th>
                  <th class="p-4">{{ currentLocale === 'ar' ? 'قنوات السوشيال' : 'Channels' }}</th>
                  <th class="p-4">{{ currentLocale === 'ar' ? 'حصة الذكاء الاصطناعي (شهر)' : 'AI Quota (Mo)' }}</th>
                  <th class="p-4">{{ currentLocale === 'ar' ? 'المنشورات' : 'Posts' }}</th>
                  <th class="p-4">{{ currentLocale === 'ar' ? 'مفاتيح AI' : 'AI Keys' }}</th>
                  <th class="p-4">{{ currentLocale === 'ar' ? 'الحالة' : 'Status' }}</th>
                  <th class="p-4 text-right">{{ currentLocale === 'ar' ? 'الإجراءات' : 'Actions' }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/60 text-slate-300">
                <tr v-for="org in organizations" :key="org.id" class="hover:bg-slate-800/40 transition-colors">
                  <td class="p-4">
                    <div class="font-bold text-white flex items-center gap-1.5">
                      {{ org.name }}
                      <span class="text-[10px] text-slate-500 font-mono">#{{ org.id }}</span>
                    </div>
                    <div class="text-[11px] text-slate-400 truncate max-w-xs mt-0.5">
                      {{ org.slug }} • {{ org.industry || 'General' }}
                    </div>
                  </td>

                  <td class="p-4">
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold capitalize" :class="getPlanBadgeClass(org.current_plan?.slug)">
                      {{ org.current_plan?.name || 'Starter' }}
                    </span>
                  </td>

                  <!-- Connected Social Accounts vs Limit -->
                  <td class="p-4 font-mono text-xs">
                    <div class="flex items-center gap-1.5">
                      <span :class="org.connected_social_accounts_count > 0 ? 'text-cyan-400 font-bold' : 'text-slate-500'">
                        {{ org.connected_social_accounts_count ?? 0 }}
                      </span>
                      <span class="text-slate-600">/</span>
                      <span class="text-slate-400">
                        {{ org.social_accounts_limit === -1 ? '∞' : (org.social_accounts_limit ?? 0) }}
                      </span>
                    </div>
                  </td>

                  <!-- Monthly AI Quota vs Limit -->
                  <td class="p-4 font-mono text-xs">
                    <div class="flex items-center gap-1.5">
                      <span :class="org.ai_content_used_this_month > 0 ? 'text-amber-400 font-bold' : 'text-slate-400'">
                        {{ org.ai_content_used_this_month ?? 0 }}
                      </span>
                      <span class="text-slate-600">/</span>
                      <span class="text-slate-400">
                        {{ org.ai_content_limit === -1 ? '∞' : (org.ai_content_limit ?? 30) }}
                      </span>
                    </div>
                  </td>

                  <td class="p-4 font-mono text-xs">{{ org.published_posts_count || 0 }} / {{ org.posts_count || 0 }}</td>

                  <td class="p-4">
                    <span v-if="org.has_custom_ai_keys" class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold">
                      BYOK
                    </span>
                    <span v-else class="text-slate-500 text-[10px]">Default</span>
                  </td>

                  <td class="p-4">
                    <button 
                      @click="toggleOrgStatus(org)" 
                      class="px-2.5 py-1 rounded-full text-[10px] font-bold capitalize transition-all"
                      :class="org.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20'"
                    >
                      {{ org.status }} ⚡
                    </button>
                  </td>

                  <td class="p-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <!-- 1-Click Login as Company (Impersonate) -->
                      <button 
                        @click="$emit('impersonate', org)"
                        class="px-3 py-1.5 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/30 text-amber-300 text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm"
                        title="Login as Company"
                      >
                        <span>🏢</span>
                        <span>{{ currentLocale === 'ar' ? 'دخول كشركة' : 'Login as Company' }}</span>
                      </button>

                      <!-- Switch Plan Modal Trigger -->
                      <button 
                        @click="openOrgPlanModal(org)"
                        class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-semibold transition-colors"
                        title="Change Subscription Plan"
                      >
                        ⚡
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tab 3: Plans & Subscriptions CRUD (Phase B) -->
        <div v-else-if="activeTab === 'plans'" class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-extrabold text-white flex items-center gap-2">
                <span>💳</span> {{ currentLocale === 'ar' ? 'إدارة الباقات والاشتراكات وميزات الخطط' : 'Subscription Plans & Entitlements CRUD' }}
              </h2>
              <p class="text-xs text-slate-400 mt-0.5">Create, edit, pricing, and configure resource limits for all plans</p>
            </div>
            <button 
              @click="openCreatePlanModal" 
              class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs shadow-lg shadow-amber-500/20 flex items-center gap-1.5"
            >
              <span>➕</span>
              <span>{{ currentLocale === 'ar' ? 'إنشاء باقة جديدة' : 'Create New Plan' }}</span>
            </button>
          </div>

          <!-- Plans Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div 
              v-for="plan in adminPlans" 
              :key="plan.id"
              class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-4 relative flex flex-col justify-between shadow-xl"
              :class="{'border-amber-500/40 ring-1 ring-amber-500/30': plan.is_active, 'opacity-70': !plan.is_active}"
            >
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <div>
                    <h3 class="text-base font-black text-white capitalize">{{ plan.name }}</h3>
                    <span class="text-[10px] font-mono text-slate-400">{{ plan.slug }}</span>
                  </div>
                  <span 
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                    :class="plan.is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'"
                  >
                    {{ plan.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>

                <p class="text-xs text-slate-400 min-h-[32px]">{{ plan.description || 'No description provided.' }}</p>

                <!-- Pricing -->
                <div class="p-3 rounded-2xl bg-slate-950/80 border border-slate-800/80 flex items-baseline justify-between">
                  <div>
                    <span class="text-xl font-black text-amber-300 font-mono">${{ plan.price_monthly }}</span>
                    <span class="text-xs text-slate-400">/mo</span>
                  </div>
                  <div class="text-right">
                    <span class="text-xs font-mono text-slate-300 font-bold">${{ plan.price_annual }}</span>
                    <span class="text-[10px] text-slate-500 block">/year</span>
                  </div>
                </div>

                <!-- Entitlements List -->
                <div class="space-y-1.5 pt-2 border-t border-slate-800 text-xs">
                  <div class="text-[10px] font-bold text-slate-400 uppercase font-mono">Plan Entitlements:</div>
                  <div 
                    v-for="ent in plan.entitlements" 
                    :key="ent.id || ent.feature_key"
                    class="flex items-center justify-between text-[11px] py-0.5 text-slate-300"
                  >
                    <span class="capitalize">{{ ent.feature_key.replace('_', ' ') }}:</span>
                    <span 
                      class="font-mono font-bold"
                      :class="ent.is_enabled ? (ent.limit_count === -1 ? 'text-emerald-400' : 'text-cyan-400') : 'text-red-400'"
                    >
                      {{ !ent.is_enabled ? 'Disabled (0)' : (ent.limit_count === -1 ? 'Unlimited (∞)' : ent.limit_count) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Plan Actions -->
              <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-2">
                <button 
                  @click="openEditPlanModal(plan)" 
                  class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs text-slate-200 font-bold transition-colors"
                >
                  ✏️ {{ currentLocale === 'ar' ? 'تعديل' : 'Edit' }}
                </button>
                <button 
                  @click="deletePlan(plan)" 
                  class="px-3 py-1.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs font-bold transition-colors"
                >
                  🗑️ {{ currentLocale === 'ar' ? 'حذف / تعطيل' : 'Delete' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 4: Public Website Settings (Phase C) -->
        <div v-else-if="activeTab === 'website'" class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-extrabold text-white flex items-center gap-2">
                <span>🌐</span> {{ currentLocale === 'ar' ? 'إدارة محتوى الموقع العام (Landing Page)' : 'Public Landing Page Content & Settings' }}
              </h2>
              <p class="text-xs text-slate-400 mt-0.5">Customize Hero headlines, descriptions, contact information, and banners dynamically without deployment</p>
            </div>
            <button 
              @click="saveSiteSettings" 
              :disabled="savingSettings"
              class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/20 flex items-center gap-1.5 transition-all"
            >
              <span>💾</span>
              <span>{{ savingSettings ? (currentLocale === 'ar' ? 'جاري الحفظ...' : 'Saving...') : (currentLocale === 'ar' ? 'حفظ التعديلات فوراً' : 'Save Changes Live') }}</span>
            </button>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Hero Section Settings (Arabic & English) -->
            <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-4 shadow-xl">
              <h3 class="text-sm font-black text-amber-300 flex items-center gap-2">
                <span>✨</span> {{ currentLocale === 'ar' ? 'نصوص قسم الترحيب (Hero Section)' : 'Hero Banner Copy' }}
              </h3>

              <div class="space-y-3">
                <div class="space-y-1">
                  <label class="text-xs font-semibold text-slate-300">Hero Headline (Arabic / العربية)</label>
                  <input v-model="siteSettings.hero_title_ar" type="text" dir="rtl" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-amber-500 outline-none" />
                </div>

                <div class="space-y-1">
                  <label class="text-xs font-semibold text-slate-300">Hero Headline (English)</label>
                  <input v-model="siteSettings.hero_title_en" type="text" dir="ltr" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-amber-500 outline-none" />
                </div>

                <div class="space-y-1">
                  <label class="text-xs font-semibold text-slate-300">Hero Subtitle (Arabic / العربية)</label>
                  <textarea v-model="siteSettings.hero_subtitle_ar" dir="rtl" rows="3" class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-amber-500 outline-none"></textarea>
                </div>

                <div class="space-y-1">
                  <label class="text-xs font-semibold text-slate-300">Hero Subtitle (English)</label>
                  <textarea v-model="siteSettings.hero_subtitle_en" dir="ltr" rows="3" class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-amber-500 outline-none"></textarea>
                </div>
              </div>
            </div>

            <!-- Contact & Announcement Info -->
            <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-4 shadow-xl flex flex-col justify-between">
              <div class="space-y-4">
                <h3 class="text-sm font-black text-cyan-400 flex items-center gap-2">
                  <span>📢</span> {{ currentLocale === 'ar' ? 'بيانات التواصل والشريط الإعلاني' : 'Contact & Announcement Settings' }}
                </h3>

                <div class="space-y-3">
                  <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-300">Announcement Banner Text</label>
                    <input v-model="siteSettings.announcement_banner" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-amber-500 outline-none" />
                  </div>

                  <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-300">Support / Contact Email</label>
                    <input v-model="siteSettings.contact_email" type="email" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-amber-500 outline-none" />
                  </div>

                  <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-300">Phone / WhatsApp Number</label>
                    <input v-model="siteSettings.contact_phone" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-amber-500 outline-none" />
                  </div>
                </div>
              </div>

              <!-- Live Preview Card -->
              <div class="p-4 rounded-2xl bg-amber-500/5 border border-amber-500/20 space-y-1.5 text-xs">
                <span class="text-amber-300 font-bold flex items-center gap-1">⚡ Live Sync:</span>
                <p class="text-[11px] text-slate-400">Updates saved here take effect immediately on the public landing page for visitors without any build step.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 5: Reports & Audit Logs -->
        <div v-else-if="activeTab === 'reports'" class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-extrabold text-white flex items-center gap-2">
                <span>📋</span> {{ currentLocale === 'ar' ? 'التقارير وسجلات الأمان والتدقيق' : 'System Reports & Audit Trail' }}
              </h2>
              <p class="text-xs text-slate-400 mt-0.5">Immutable audit logs and global subscription reports</p>
            </div>
            <button @click="fetchReports" class="px-3.5 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-300 hover:text-white transition-colors">
              🔄 {{ currentLocale === 'ar' ? 'تحديث' : 'Refresh' }}
            </button>
          </div>

          <!-- Audit Logs Table -->
          <div class="bg-slate-900/70 rounded-3xl border border-slate-800 overflow-hidden shadow-2xl">
            <table class="w-full text-left text-xs" :dir="currentLocale === 'ar' ? 'rtl' : 'ltr'">
              <thead class="bg-slate-950/80 text-slate-400 uppercase text-[10px] font-bold border-b border-slate-800">
                <tr>
                  <th class="p-4">Action</th>
                  <th class="p-4">Entity Type</th>
                  <th class="p-4">User / Admin</th>
                  <th class="p-4">Organization</th>
                  <th class="p-4 text-right">Timestamp</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/60 text-slate-300">
                <tr v-for="log in auditLogs" :key="log.id" class="hover:bg-slate-800/30 transition-colors">
                  <td class="p-4 font-mono font-bold text-amber-300">{{ log.action }}</td>
                  <td class="p-4 font-mono text-slate-400">{{ log.entity_type }} #{{ log.entity_id }}</td>
                  <td class="p-4">User #{{ log.user_id }}</td>
                  <td class="p-4">Org #{{ log.organization_id || 'System' }}</td>
                  <td class="p-4 text-right font-mono text-slate-500">{{ log.created_at ? new Date(log.created_at).toLocaleString() : 'N/A' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </main>
    </div>

    <!-- Create / Edit Plan Modal (Phase B) -->
    <div v-if="showPlanModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-xl w-full space-y-5 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <h3 class="text-base font-bold text-white flex items-center gap-2">
            <span>💳</span>
            <span>{{ isEditingPlan ? 'Edit Subscription Plan' : 'Create New Subscription Plan' }}</span>
          </h3>
          <button @click="showPlanModal = false" class="text-slate-400 hover:text-white text-base">✕</button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-300">Plan Name</label>
            <input v-model="planForm.name" type="text" placeholder="Agency Scale" class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" />
          </div>

          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-300">Plan Slug</label>
            <input v-model="planForm.slug" :disabled="isEditingPlan" type="text" placeholder="agency-scale" class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white disabled:opacity-50" />
          </div>

          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-300">Monthly Price ($)</label>
            <input v-model.number="planForm.price_monthly" type="number" step="0.01" class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-mono" />
          </div>

          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-300">Annual Price ($)</label>
            <input v-model.number="planForm.price_annual" type="number" step="0.01" class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-mono" />
          </div>

          <div class="sm:col-span-2 space-y-1">
            <label class="text-xs font-semibold text-slate-300">Description</label>
            <textarea v-model="planForm.description" rows="2" class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white"></textarea>
          </div>
        </div>

        <!-- Plan Entitlements Configuration -->
        <div class="space-y-3 pt-3 border-t border-slate-800">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-bold text-amber-300 uppercase font-mono">Resource Entitlements & Limits:</h4>
            <span class="text-[10px] text-slate-400">(-1 = Unlimited)</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div 
              v-for="feature in planForm.entitlements" 
              :key="feature.feature_key"
              class="p-3 rounded-2xl bg-slate-950 border border-slate-800 space-y-2"
            >
              <div class="flex items-center justify-between">
                <label class="text-xs font-semibold text-slate-200 capitalize">
                  {{ feature.feature_key.replace('_', ' ') }}
                </label>
                <input v-model="feature.is_enabled" type="checkbox" class="rounded text-amber-500" />
              </div>
              <div v-if="feature.is_enabled" class="flex items-center gap-2">
                <span class="text-[10px] text-slate-400">Limit:</span>
                <input v-model.number="feature.limit_count" type="number" class="w-full px-2 py-1 rounded-lg bg-slate-900 border border-slate-700 text-xs text-white font-mono" />
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-800">
          <button @click="showPlanModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-xs text-slate-300 font-semibold">Cancel</button>
          <button @click="submitPlanForm" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold shadow-lg shadow-amber-500/20">
            {{ isEditingPlan ? 'Update Plan' : 'Create Plan' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Assign Plan to Organization Modal -->
    <div v-if="showOrgPlanModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
        <h3 class="text-base font-bold text-white">Change Plan for {{ selectedOrgForPlan?.name }}</h3>
        <div class="space-y-1.5">
          <label class="text-xs text-slate-400">Select Plan</label>
          <select v-model="selectedPlanSlug" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
            <option v-for="plan in adminPlans" :key="plan.id" :value="plan.slug">
              {{ plan.name }} (${{ plan.price_monthly }}/mo)
            </option>
          </select>
        </div>
        <div class="flex items-center justify-end gap-2 pt-2">
          <button @click="showOrgPlanModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-xs text-slate-300">Cancel</button>
          <button @click="submitOrgPlanChange" class="px-5 py-2 rounded-xl bg-amber-500 text-slate-950 font-bold text-xs">Confirm Switch</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { currentLocale } from '../i18n';

const props = defineProps<{
  authToken: string;
  authUser: any;
}>();

const emit = defineEmits<{
  (e: 'impersonate', org: any): void;
  (e: 'logout'): void;
  (e: 'view-website'): void;
  (e: 'toggle-lang'): void;
}>();

const activeTab = ref<'overview' | 'organizations' | 'plans' | 'website' | 'reports'>('overview');

const adminTabs = [
  { id: 'overview', icon: '📊', nameAr: 'نظرة عامة ومؤشرات', nameEn: 'Global Overview' },
  { id: 'organizations', icon: '🏢', nameAr: 'الشركات والمستأجرين', nameEn: 'Companies' },
  { id: 'plans', icon: '💳', nameAr: 'الباقات والاشتراكات', nameEn: 'Plans & Entitlements', badge: 'CRUD' },
  { id: 'website', icon: '🌐', nameAr: 'إدارة محتوى الموقع', nameEn: 'Website Content', badge: 'CMS' },
  { id: 'reports', icon: '📋', nameAr: 'سجلات وتدقيق النظام', nameEn: 'Audit & Reports' },
];

const kpis = ref<any>({});
const planDistribution = ref<any[]>([]);
const organizations = ref<any[]>([]);
const adminPlans = ref<any[]>([]);
const auditLogs = ref<any[]>([]);

const searchQuery = ref('');
const statusFilter = ref('all');
let debounceTimer: any = null;

const siteSettings = ref<any>({
  hero_title_ar: '',
  hero_title_en: '',
  hero_subtitle_ar: '',
  hero_subtitle_en: '',
  contact_email: '',
  contact_phone: '',
  announcement_banner: '',
});
const savingSettings = ref(false);

const showPlanModal = ref(false);
const isEditingPlan = ref(false);
const planForm = ref<any>({
  id: null,
  name: '',
  slug: '',
  description: '',
  price_monthly: 0,
  price_annual: 0,
  is_active: true,
  entitlements: [
    { feature_key: 'brand_brain', is_enabled: true, limit_count: -1 },
    { feature_key: 'ai_strategy', is_enabled: true, limit_count: 5 },
    { feature_key: 'ai_content', is_enabled: true, limit_count: 30 },
    { feature_key: 'social_accounts', is_enabled: true, limit_count: 5 },
    { feature_key: 'team_members', is_enabled: true, limit_count: 2 },
    { feature_key: 'analytics', is_enabled: true, limit_count: -1 },
    { feature_key: 'automation', is_enabled: false, limit_count: 0 },
  ],
});

const showOrgPlanModal = ref(false);
const selectedOrgForPlan = ref<any>(null);
const selectedPlanSlug = ref('growth');

const getHeaders = () => ({
  Authorization: `Bearer ${props.authToken}`,
});

const fetchKpis = async () => {
  try {
    const res = await axios.get('/api/v1/super-admin/kpis', { headers: getHeaders() });
    kpis.value = res.data?.data?.kpis || {};
    planDistribution.value = res.data?.data?.plan_distribution || [];
  } catch (err) {
    console.error('Failed to fetch KPIs', err);
  }
};

const fetchOrganizations = async () => {
  try {
    const params: any = {};
    if (searchQuery.value) params.search = searchQuery.value;
    if (statusFilter.value !== 'all') params.status = statusFilter.value;

    const res = await axios.get('/api/v1/super-admin/organizations', {
      headers: getHeaders(),
      params,
    });
    organizations.value = res.data?.data?.organizations || [];
  } catch (err) {
    console.error('Failed to fetch organizations', err);
  }
};

const debounceFetchOrgs = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchOrganizations, 300);
};

const toggleOrgStatus = async (org: any) => {
  const newStatus = org.status === 'active' ? 'suspended' : 'active';
  try {
    await axios.patch(`/api/v1/super-admin/organizations/${org.id}/status`, { status: newStatus }, { headers: getHeaders() });
    org.status = newStatus;
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to update status');
  }
};

const fetchPlans = async () => {
  try {
    const res = await axios.get('/api/v1/super-admin/plans', { headers: getHeaders() });
    adminPlans.value = res.data?.data?.plans || [];
  } catch (err) {
    console.error('Failed to fetch plans', err);
  }
};

const openCreatePlanModal = () => {
  isEditingPlan.value = false;
  planForm.value = {
    id: null,
    name: '',
    slug: '',
    description: '',
    price_monthly: 99,
    price_annual: 950,
    is_active: true,
    entitlements: [
      { feature_key: 'brand_brain', is_enabled: true, limit_count: -1 },
      { feature_key: 'ai_strategy', is_enabled: true, limit_count: 10 },
      { feature_key: 'ai_content', is_enabled: true, limit_count: 50 },
      { feature_key: 'social_accounts', is_enabled: true, limit_count: 5 },
      { feature_key: 'team_members', is_enabled: true, limit_count: 3 },
      { feature_key: 'analytics', is_enabled: true, limit_count: -1 },
      { feature_key: 'automation', is_enabled: false, limit_count: 0 },
    ],
  };
  showPlanModal.value = true;
};

const openEditPlanModal = (plan: any) => {
  isEditingPlan.value = true;
  planForm.value = {
    id: plan.id,
    name: plan.name,
    slug: plan.slug,
    description: plan.description,
    price_monthly: plan.price_monthly,
    price_annual: plan.price_annual,
    is_active: plan.is_active,
    entitlements: plan.entitlements.map((e: any) => ({
      feature_key: e.feature_key,
      is_enabled: e.is_enabled,
      limit_count: e.limit_count,
    })),
  };
  showPlanModal.value = true;
};

const submitPlanForm = async () => {
  try {
    if (isEditingPlan.value) {
      await axios.patch(`/api/v1/super-admin/plans/${planForm.value.id}`, planForm.value, { headers: getHeaders() });
    } else {
      await axios.post('/api/v1/super-admin/plans', planForm.value, { headers: getHeaders() });
    }
    showPlanModal.value = false;
    await fetchPlans();
    await fetchKpis();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to save plan');
  }
};

const deletePlan = async (plan: any) => {
  if (!confirm(`Are you sure you want to delete/deactivate plan: "${plan.name}"?`)) return;
  try {
    const res = await axios.delete(`/api/v1/super-admin/plans/${plan.id}`, { headers: getHeaders() });
    alert(res.data?.meta?.message || 'Plan processed.');
    await fetchPlans();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to delete plan');
  }
};

const fetchSiteSettings = async () => {
  try {
    const res = await axios.get('/api/v1/site-settings');
    siteSettings.value = { ...siteSettings.value, ...(res.data?.data?.settings || {}) };
  } catch (err) {
    console.error('Failed to fetch site settings', err);
  }
};

const saveSiteSettings = async () => {
  savingSettings.value = true;
  try {
    await axios.patch('/api/v1/super-admin/site-settings', { settings: siteSettings.value }, { headers: getHeaders() });
    alert(currentLocale.value === 'ar' ? 'تم حفظ إعدادات الموقع بنجاح وتم تطبيقها فوراً!' : 'Site settings updated live successfully!');
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to save site settings');
  } finally {
    savingSettings.value = false;
  }
};

const fetchReports = async () => {
  try {
    const res = await axios.get('/api/v1/super-admin/kpis', { headers: getHeaders() });
    auditLogs.value = res.data?.data?.recent_activity || [];
  } catch (err) {
    console.error('Failed to fetch reports', err);
  }
};

const openOrgPlanModal = (org: any) => {
  selectedOrgForPlan.value = org;
  selectedPlanSlug.value = org.current_plan?.slug || 'growth';
  showOrgPlanModal.value = true;
};

const submitOrgPlanChange = async () => {
  if (!selectedOrgForPlan.value) return;
  try {
    await axios.patch(`/api/v1/super-admin/organizations/${selectedOrgForPlan.value.id}/plan`, {
      plan_slug: selectedPlanSlug.value,
    }, { headers: getHeaders() });
    showOrgPlanModal.value = false;
    await fetchOrganizations();
    await fetchKpis();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to change plan');
  }
};

const getPlanBadgeClass = (slug?: string) => {
  switch (slug) {
    case 'pro':
    case 'enterprise':
      return 'bg-purple-500/10 text-purple-400 border border-purple-500/20';
    case 'growth':
      return 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20';
    default:
      return 'bg-slate-800 text-slate-400 border border-slate-700';
  }
};

onMounted(async () => {
  await Promise.all([
    fetchKpis(),
    fetchOrganizations(),
    fetchPlans(),
    fetchSiteSettings(),
    fetchReports(),
  ]);
});
</script>
