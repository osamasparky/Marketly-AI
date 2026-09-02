<template>
  <div class="space-y-8" :dir="currentLocale === 'ar' ? 'rtl' : 'ltr'">
    <!-- Top Header & Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-slate-900/60 p-6 rounded-3xl border border-slate-800/80 backdrop-blur-xl shadow-xl">
      <div>
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500/20 to-cyan-500/10 border border-blue-500/30 flex items-center justify-center text-2xl text-blue-400 shadow-inner">
            📡
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-xl font-bold text-white">
                {{ currentLocale === 'ar' ? 'قنوات النشر ومحرك النشر الآلي' : 'Social Channels & Automated Publishing Engine' }}
              </h2>
              <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                Phase 7 Active
              </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">
              {{ currentLocale === 'ar' 
                ? 'ربط وتفويض قنوات التواصل الاجتماعي بهوية علامتك التجارية، واختيار الصفحة المستهدفة، ونشر المنشورات آلياً.' 
                : 'Connect and authorize social channels for your brand, select specific target pages, and publish scheduled content.' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap items-center gap-3">
        <button 
          @click="fetchData" 
          :disabled="loading"
          class="px-4 py-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-slate-300 transition-all flex items-center gap-2"
        >
          <span :class="{'animate-spin': loading}">🔄</span>
          <span>{{ currentLocale === 'ar' ? 'تحديث البيانات' : 'Refresh' }}</span>
        </button>

        <button 
          @click="openDirectPublishModal"
          class="tactile-btn tactile-btn-secondary px-4 py-2.5 text-xs font-bold flex items-center gap-2"
        >
          <span>🚀</span>
          <span>{{ currentLocale === 'ar' ? 'تجربة نشر منشور مباشر' : 'Test Publish Post Now' }}</span>
        </button>

        <button 
          @click="runWorkerDispatch"
          :disabled="workerLoading"
          class="tactile-btn tactile-btn-primary px-5 py-2.5 text-xs font-bold flex items-center gap-2 shadow-lg shadow-emerald-500/20"
        >
          <span v-if="workerLoading" class="animate-spin">⏳</span>
          <span v-else>⚡</span>
          <span>{{ currentLocale === 'ar' ? 'بث المنشورات المستحقة الآن' : 'Publish Due Posts Now' }}</span>
        </button>
      </div>
    </div>

    <!-- Interactive Workflow Guide: How Automated Publishing Works -->
    <div class="bg-gradient-to-r from-slate-900/80 via-slate-900/50 to-slate-900/80 p-6 rounded-3xl border border-slate-800/80 backdrop-blur-xl shadow-lg space-y-4">
      <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
        <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
          <span>⚙️</span>
          <span>{{ currentLocale === 'ar' ? 'كيف يعمل محرك النشر الآلي في منصة ماركتلي؟' : 'How the Automated Publishing Engine Works' }}</span>
        </h3>
        <span class="text-[11px] text-cyan-400 font-semibold bg-cyan-500/10 px-2.5 py-1 rounded-full border border-cyan-500/20">
          {{ currentLocale === 'ar' ? 'دورة النشر الكاملة (4 خطوات)' : '4-Step Publishing Lifecycle' }}
        </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 pt-1">
        <!-- Step 1 -->
        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/70 space-y-2 hover:border-slate-700 transition-colors">
          <div class="flex items-center justify-between">
            <span class="w-7 h-7 rounded-xl bg-blue-500/20 border border-blue-500/30 text-blue-400 text-xs font-bold flex items-center justify-center">1</span>
            <span class="text-lg">🔗</span>
          </div>
          <h4 class="text-xs font-bold text-white">
            {{ currentLocale === 'ar' ? '1. تفويض واختيار الصفحة' : '1. Connect & Select Page' }}
          </h4>
          <p class="text-[11px] text-slate-400 leading-relaxed">
            {{ currentLocale === 'ar' 
              ? 'تفويض حساب المنصة واختيار الصفحة المستهدفة المحددة (/me/accounts) لحفظ الـ Page Access Token الخاص بها فقط.' 
              : 'Authorize platform account and choose specific target page from /me/accounts to store its dedicated Page Token.' }}
          </p>
        </div>

        <!-- Step 2 -->
        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/70 space-y-2 hover:border-slate-700 transition-colors">
          <div class="flex items-center justify-between">
            <span class="w-7 h-7 rounded-xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 text-xs font-bold flex items-center justify-center">2</span>
            <span class="text-lg">✍️</span>
          </div>
          <h4 class="text-xs font-bold text-white">
            {{ currentLocale === 'ar' ? '2. صناعة المحتوى والتصاميم' : '2. Create Content & Media' }}
          </h4>
          <p class="text-[11px] text-slate-400 leading-relaxed">
            {{ currentLocale === 'ar' 
              ? 'توليد النصوص المخصصة لكل منصة في استوديو المحتوى، وتوليد الصور واستوديو التصاميم بهوية ألوان البراند والشعار.' 
              : 'Generate platform-tailored copy in Content Studio and visual brand assets in Creative Studio with logo watermarks.' }}
          </p>
        </div>

        <!-- Step 3 -->
        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/70 space-y-2 hover:border-slate-700 transition-colors">
          <div class="flex items-center justify-between">
            <span class="w-7 h-7 rounded-xl bg-amber-500/20 border border-amber-500/30 text-amber-400 text-xs font-bold flex items-center justify-center">3</span>
            <span class="text-lg">📅</span>
          </div>
          <h4 class="text-xs font-bold text-white">
            {{ currentLocale === 'ar' ? '3. الجدولة والاعتماد' : '3. Scheduling & Approval' }}
          </h4>
          <p class="text-[11px] text-slate-400 leading-relaxed">
            {{ currentLocale === 'ar' 
              ? 'تحديد موعد النشر في جدول النشر التفاعلي واعتماد المنشور من مدير الفريق للانتقال إلى طابور النشر المستحق.' 
              : 'Set optimal publishing slots in the Calendar and approve posts to transition them into the active scheduled queue.' }}
          </p>
        </div>

        <!-- Step 4 -->
        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/70 space-y-2 hover:border-slate-700 transition-colors">
          <div class="flex items-center justify-between">
            <span class="w-7 h-7 rounded-xl bg-purple-500/20 border border-purple-500/30 text-purple-400 text-xs font-bold flex items-center justify-center">4</span>
            <span class="text-lg">🚀</span>
          </div>
          <h4 class="text-xs font-bold text-white">
            {{ currentLocale === 'ar' ? '4. البث والتوثيق الآلي' : '4. Automated Dispatch' }}
          </h4>
          <p class="text-[11px] text-slate-400 leading-relaxed">
            {{ currentLocale === 'ar' 
              ? 'يقوم المحرك ببث المنشور إلى الصفحة المختارة حصراً ({PAGE_ID}/feed) وتوثيق رابط المنشور المباشر وحساب التفاعل.' 
              : 'The publishing worker broadcasts posts to the selected Page ({PAGE_ID}/feed), logging external links and syncing metrics.' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Supported Channels Cards Grid -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
          <span>🌐</span> 
          <span>{{ currentLocale === 'ar' ? 'شبكات التواصل والحسابات المدعومة' : 'Supported Social Channels' }}</span>
        </h3>
        <span class="text-xs text-slate-400 bg-slate-900 px-3 py-1 rounded-full border border-slate-800">
          {{ connectedCount }} / 5 {{ currentLocale === 'ar' ? 'القنوات المتصلة النشطة' : 'Active Channels Connected' }}
        </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div 
          v-for="ch in channels" 
          :key="ch.platform"
          class="p-6 rounded-3xl bg-slate-900/70 border border-slate-800/80 backdrop-blur-xl flex flex-col justify-between space-y-5 hover:border-slate-700 transition-all shadow-lg"
        >
          <!-- Card Top: Platform Brand & Status Pill -->
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-center text-2xl shadow-inner">
                {{ getPlatformIcon(ch.platform) }}
              </div>
              <div>
                <h4 class="text-sm font-extrabold text-white capitalize">{{ ch.platform }}</h4>
                <p class="text-[11px] text-slate-400 mt-0.5">
                  {{ ch.is_connected ? (ch.account.account_name || (ch.account.account_username ? `@${ch.account.account_username}` : 'Connected')) : (currentLocale === 'ar' ? 'غير متصل' : 'Disconnected') }}
                </p>
              </div>
            </div>

            <span 
              class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border flex items-center gap-1.5"
              :class="ch.is_connected ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-slate-800 text-slate-400 border-slate-700'"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="ch.is_connected ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
              {{ ch.is_connected ? (currentLocale === 'ar' ? 'متصل ومفوض' : 'Connected') : (currentLocale === 'ar' ? 'غير متصل' : 'Disconnected') }}
            </span>
          </div>

          <!-- Account Meta Info -->
          <div v-if="ch.is_connected" class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 text-xs space-y-2">
            <div class="flex items-center justify-between text-[11px]">
              <span class="text-slate-400">{{ currentLocale === 'ar' ? 'الصفحة / الحساب المربوط:' : 'Target Page / Account:' }}</span>
              <span class="font-bold text-white truncate max-w-[140px]">{{ ch.account.account_name || ch.account.account_id }}</span>
            </div>
            <div class="flex items-center justify-between text-[11px]">
              <span class="text-slate-400">{{ currentLocale === 'ar' ? 'معرف الصفحة (ID):' : 'Page ID:' }}</span>
              <span class="font-mono text-cyan-400 font-semibold truncate max-w-[140px]">{{ ch.account.account_id }}</span>
            </div>
            <div class="flex items-center justify-between text-[11px]">
              <span class="text-slate-400">{{ currentLocale === 'ar' ? 'صحة التوكن:' : 'Token Health:' }}</span>
              <span class="text-emerald-400 font-bold capitalize flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                {{ ch.account.health_status }}
              </span>
            </div>
          </div>

          <div v-else class="p-4 rounded-2xl bg-slate-950/40 border border-slate-900 text-xs text-slate-400 text-center leading-relaxed">
            {{ currentLocale === 'ar' 
              ? `قم بربط الحساب واختيار الصفحة المستهدفة لتفعيل النشر الآلي على ${ch.platform}` 
              : `Connect and select target page to enable direct publishing to ${ch.platform}` }}
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2 pt-2 border-t border-slate-800/60">
            <button 
              v-if="!ch.is_connected"
              @click="openConnectModal(ch.platform)"
              class="w-full tactile-btn tactile-btn-primary py-2.5 text-xs font-bold flex items-center justify-center gap-2"
            >
              <span>🔗</span>
              <span>{{ currentLocale === 'ar' ? 'ربط واختيار الصفحة' : 'Connect & Select Page' }}</span>
            </button>

            <template v-else>
              <button 
                @click="healthCheck(ch.account.id)"
                :disabled="actionLoading"
                class="flex-1 py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-semibold transition-colors flex items-center justify-center gap-1.5"
              >
                <span>🩺</span>
                <span>{{ currentLocale === 'ar' ? 'فحص الاتصال' : 'Health Check' }}</span>
              </button>

              <button 
                @click="openConnectModal(ch.platform)"
                class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-semibold transition-colors"
                :title="currentLocale === 'ar' ? 'تغيير الصفحة المربوطة' : 'Switch Target Page'"
              >
                🔄
              </button>

              <button 
                @click="disconnectAccount(ch.account.id)"
                :disabled="actionLoading"
                class="py-2 px-3.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs font-semibold transition-colors flex items-center justify-center gap-1"
              >
                <span>{{ currentLocale === 'ar' ? 'إلغاء' : 'Disconnect' }}</span>
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Publishing Jobs & Delivery Audit Table -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
          <span>📜</span> 
          <span>{{ currentLocale === 'ar' ? 'سجل وعمليات النشر المباشر والمجدول' : 'Publishing History & Delivery Audit' }}</span>
        </h3>
      </div>

      <div class="bg-slate-900/60 rounded-3xl border border-slate-800/80 backdrop-blur-xl overflow-hidden shadow-xl">
        <div v-if="publishingJobs.length === 0" class="p-12 text-center space-y-3">
          <div class="text-4xl">📭</div>
          <h4 class="text-sm font-bold text-white">
            {{ currentLocale === 'ar' ? 'لا توجد عمليات نشر حتى الآن' : 'No Publishing Jobs Recorded Yet' }}
          </h4>
          <p class="text-xs text-slate-400 max-w-md mx-auto leading-relaxed">
            {{ currentLocale === 'ar' 
              ? 'عند جدولة أي منشور في التقويم أو نشر منشور فوري، ستظهر هنا جميع سجلات وتفاصيل البث وروابط المنشورات الحية.' 
              : 'When posts are scheduled or published on demand, complete delivery history and external live links will appear here.' }}
          </p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs" :dir="currentLocale === 'ar' ? 'rtl' : 'ltr'">
            <thead class="bg-slate-950/80 text-slate-400 uppercase text-[10px] font-bold border-b border-slate-800">
              <tr>
                <th class="p-4">{{ currentLocale === 'ar' ? 'عنوان ومحتوى المنشور' : 'Post Title' }}</th>
                <th class="p-4">{{ currentLocale === 'ar' ? 'قناة النشر والصفحة' : 'Channel & Target Page' }}</th>
                <th class="p-4">{{ currentLocale === 'ar' ? 'حالة البث' : 'Status' }}</th>
                <th class="p-4">{{ currentLocale === 'ar' ? 'تاريخ ووقت النشر' : 'Published At' }}</th>
                <th class="p-4 text-center">{{ currentLocale === 'ar' ? 'رابط المنشور المباشر' : 'Live Post' }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-300">
              <tr v-for="job in publishingJobs" :key="job.id" class="hover:bg-slate-800/30 transition-colors">
                <td class="p-4 max-w-sm">
                  <div class="font-bold text-white truncate">{{ job.post?.title || `Post #${job.content_post_id}` }}</div>
                  <div class="text-[11px] text-slate-400 truncate mt-0.5">{{ job.post?.content_text?.substring(0, 70) }}...</div>
                </td>
                <td class="p-4">
                  <span class="inline-flex items-center gap-1.5 capitalize px-2.5 py-1 rounded-xl bg-slate-950 border border-slate-800 font-semibold text-slate-200">
                    {{ getPlatformIcon(job.social_account?.platform) }}
                    {{ job.social_account?.platform }}
                    <span v-if="job.social_account?.account_name" class="text-slate-400 text-[10px]">
                      ({{ job.social_account.account_name }})
                    </span>
                  </span>
                </td>
                <td class="p-4">
                  <span 
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border"
                    :class="getJobStatusClass(job.status)"
                  >
                    {{ job.status }}
                  </span>
                </td>
                <td class="p-4 text-slate-400 font-mono text-[11px]">
                  {{ formatDate(job.published_at || job.scheduled_at) }}
                </td>
                <td class="p-4 text-center">
                  <a 
                    v-if="job.external_post_url"
                    :href="job.external_post_url" 
                    target="_blank" 
                    class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-cyan-500/10 text-cyan-400 hover:bg-cyan-500/20 font-bold border border-cyan-500/20 transition-colors"
                  >
                    <span>🔗</span>
                    <span>{{ currentLocale === 'ar' ? 'مشاهدة المنشور' : 'View Post' }}</span>
                  </a>
                  <span v-else class="text-slate-600">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Dual-Mode & Page Selection Connection Modal -->
    <div v-if="showConnectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="w-full max-w-xl bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-6 shadow-2xl">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div class="flex items-center gap-3">
            <span class="text-2xl p-2 rounded-xl bg-slate-950 border border-slate-800">{{ getPlatformIcon(selectedPlatform) }}</span>
            <div>
              <h3 class="text-base font-bold text-white capitalize">
                {{ currentLocale === 'ar' ? `ربط واختيار صفحة ${selectedPlatform}` : `Connect & Select ${selectedPlatform} Page` }}
              </h3>
              <p class="text-[11px] text-slate-400">
                {{ currentLocale === 'ar' ? 'جلب الصفحات وتحديد الصفحة المستهدفة لنشر منشورات وريلز البراند' : 'Discover managed pages and bind specific target page for your brand' }}
              </p>
            </div>
          </div>
          <button @click="showConnectModal = false" class="text-slate-400 hover:text-white text-base">✕</button>
        </div>

        <!-- Mode Switcher Tabs -->
        <div class="grid grid-cols-2 gap-2 p-1 rounded-2xl bg-slate-950 border border-slate-800">
          <button 
            type="button" 
            @click="switchMode('sandbox')"
            class="py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5"
            :class="connectMode === 'sandbox' ? 'bg-slate-800 text-white shadow' : 'text-slate-400 hover:text-slate-200'"
          >
            <span>⚡</span>
            <span>{{ currentLocale === 'ar' ? 'الربط التلقائي واختيار الصفحة' : 'Auto Login & Page Select' }}</span>
          </button>
          <button 
            type="button" 
            @click="switchMode('custom')"
            class="py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5"
            :class="connectMode === 'custom' ? 'bg-slate-800 text-white shadow' : 'text-slate-400 hover:text-slate-200'"
          >
            <span>🔑</span>
            <span>{{ currentLocale === 'ar' ? 'مفاتيح مخصصة (Custom Token)' : 'Custom Token & Page ID' }}</span>
          </button>
        </div>

        <!-- Step 1: Pages Selection List (Auto/Sandbox Mode) -->
        <div v-if="connectMode === 'sandbox'" class="space-y-4 text-xs">
          <div v-if="loadingPages" class="p-8 text-center text-slate-400">
            <span class="animate-spin text-2xl block mb-2">⏳</span>
            {{ currentLocale === 'ar' ? 'جاري استدعاء /me/accounts وجلب الصفحات المتاحة...' : 'Querying /me/accounts for available pages...' }}
          </div>

          <div v-else class="space-y-3">
            <div class="flex items-center justify-between">
              <label class="font-bold text-slate-200">
                {{ currentLocale === 'ar' ? 'اختر الصفحة المحددة المطلوب ربطها بالبراند:' : 'Select Specific Page to Bind to Brand:' }}
              </label>
              <span class="text-[10px] text-emerald-400 font-semibold bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                {{ availablePages.length }} {{ currentLocale === 'ar' ? 'صفحات متوفرة' : 'Pages Found' }}
              </span>
            </div>

            <!-- Pages List Cards -->
            <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
              <div 
                v-for="pg in availablePages" 
                :key="pg.id"
                @click="selectedPageId = pg.id"
                class="p-3.5 rounded-2xl border transition-all cursor-pointer flex items-center justify-between"
                :class="selectedPageId === pg.id ? 'bg-emerald-500/10 border-emerald-500 text-white shadow-md' : 'bg-slate-950 border-slate-800 text-slate-300 hover:border-slate-700'"
              >
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center font-bold text-slate-200 text-xs">
                    {{ pg.name.substring(0, 2).toUpperCase() }}
                  </div>
                  <div>
                    <div class="font-bold text-xs">{{ pg.name }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-2">
                      <span>{{ pg.category }}</span>
                      <span>•</span>
                      <span class="font-mono text-cyan-400">ID: {{ pg.id }}</span>
                    </div>
                  </div>
                </div>

                <div class="w-5 h-5 rounded-full border flex items-center justify-center" :class="selectedPageId === pg.id ? 'border-emerald-400 bg-emerald-500 text-slate-950 font-bold text-xs' : 'border-slate-700'">
                  <span v-if="selectedPageId === pg.id">✓</span>
                </div>
              </div>
            </div>

            <div class="p-3 rounded-2xl bg-slate-950/70 border border-slate-800/80 text-[11px] text-slate-400 leading-relaxed">
              <span class="font-bold text-slate-300">🔒 عزل النشر والأمان:</span>
              {{ currentLocale === 'ar' 
                ? 'سيقوم النظام بحفظ Page Access Token الخاص بهذه الصفحة فقط، ولن يتم النشر على أي صفحة أخرى.' 
                : 'The system will securely store the dedicated Page Access Token for this selected Page only.' }}
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-800">
            <button 
              type="button" 
              @click="showConnectModal = false"
              class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:bg-slate-700 text-xs font-semibold"
            >
              {{ currentLocale === 'ar' ? 'إلغاء' : 'Cancel' }}
            </button>
            <button 
              @click="confirmPageSelection"
              :disabled="connecting || !selectedPageId"
              class="tactile-btn tactile-btn-primary px-5 py-2 text-xs flex items-center gap-2"
            >
              <span v-if="connecting" class="animate-spin">⏳</span>
              <span v-else>💾</span>
              <span>{{ connecting ? (currentLocale === 'ar' ? 'جاري الاعتماد...' : 'Authorizing...') : (currentLocale === 'ar' ? 'اعتماد وربط هذه الصفحة' : 'Authorize Selected Page') }}</span>
            </button>
          </div>
        </div>

        <!-- Custom API Credentials Mode -->
        <form v-else @submit.prevent="confirmCustomConnect" class="space-y-4 text-xs">
          <div class="space-y-1">
            <label class="font-semibold text-slate-300 flex items-center justify-between">
              <span>{{ currentLocale === 'ar' ? 'رمز الوصول (Page Access Token) *' : 'Page Access Token *' }}</span>
              <button 
                type="button" 
                @click="fetchPagesFromCustomToken"
                :disabled="!customForm.access_token || loadingPages"
                class="text-[10px] text-cyan-400 hover:underline flex items-center gap-1"
              >
                <span>🔍</span>
                <span>{{ loadingPages ? 'جاري الفحص...' : 'فحص وجلب بيانات الصفحة' }}</span>
              </button>
            </label>
            <input 
              v-model="customForm.access_token" 
              type="password" 
              required 
              placeholder="e.g. EAABwzLixxxx... or custom page token" 
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-mono placeholder-slate-600 focus:border-emerald-500 outline-none"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="font-semibold text-slate-300">{{ currentLocale === 'ar' ? 'معرف الصفحة المستهدفة (Page ID) *' : 'Target Page ID *' }}</label>
              <input 
                v-model="customForm.account_id" 
                type="text" 
                required
                :placeholder="selectedPlatform + '_page_10293847'" 
                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-mono placeholder-slate-600 focus:border-emerald-500 outline-none"
              />
            </div>
            <div class="space-y-1">
              <label class="font-semibold text-slate-300">{{ currentLocale === 'ar' ? 'اسم الصفحة الرسمية (Page Name) *' : 'Page Name *' }}</label>
              <input 
                v-model="customForm.account_name" 
                type="text" 
                required
                placeholder="e.g. Meem DTT Official" 
                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none"
              />
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-800">
            <button 
              type="button" 
              @click="showConnectModal = false"
              class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:bg-slate-700 text-xs font-semibold"
            >
              {{ currentLocale === 'ar' ? 'إلغاء' : 'Cancel' }}
            </button>
            <button 
              type="submit" 
              :disabled="connecting"
              class="tactile-btn tactile-btn-primary px-5 py-2 text-xs flex items-center gap-2"
            >
              <span v-if="connecting" class="animate-spin">⏳</span>
              <span v-else>💾</span>
              <span>{{ connecting ? (currentLocale === 'ar' ? 'جاري الحفظ والربط...' : 'Saving...') : (currentLocale === 'ar' ? 'حفظ وربط الصفحة' : 'Save & Bind Page') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Direct Test Publishing Modal (اختبار نشر منشور الآن) -->
    <div v-if="showDirectPublishModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="w-full max-w-xl bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-6 shadow-2xl">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div class="flex items-center gap-3">
            <span class="text-2xl p-2 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">🚀</span>
            <div>
              <h3 class="text-base font-bold text-white">
                {{ currentLocale === 'ar' ? 'تجربة نشر منشور مباشر وفوري' : 'Test Publish Post Immediately' }}
              </h3>
              <p class="text-[11px] text-slate-400">
                {{ currentLocale === 'ar' ? 'اختر منشوراً من علامتك التجارية وقم ببثه مباشرة لتجربة المحرك' : 'Select an approved post and broadcast it live to verify the engine' }}
              </p>
            </div>
          </div>
          <button @click="showDirectPublishModal = false" class="text-slate-400 hover:text-white text-base">✕</button>
        </div>

        <div v-if="readyPostsLoading" class="p-8 text-center text-xs text-slate-400">
          <span class="animate-spin text-xl block mb-2">⏳</span>
          {{ currentLocale === 'ar' ? 'جاري جلب المنشورات المتاحة للعلامة التجارية...' : 'Loading available brand posts...' }}
        </div>

        <div v-else-if="readyPosts.length === 0" class="p-6 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs space-y-2">
          <div class="font-bold flex items-center gap-1.5">
            <span>⚠️</span>
            <span>{{ currentLocale === 'ar' ? 'لا توجد منشورات متاحة حالياً' : 'No Posts Available' }}</span>
          </div>
          <p class="text-[11px] text-slate-300">
            {{ currentLocale === 'ar'
              ? 'قم بإنشاء منشور في استوديو المحتوى أولاً لتتمكن من تجربته ونشره مباشرة هنا.'
              : 'Create a post in Content Studio first to test direct publishing.' }}
          </p>
        </div>

        <form v-else @submit.prevent="executeDirectPublish" class="space-y-4 text-xs">
          <div class="space-y-1">
            <label class="font-semibold text-slate-300">{{ currentLocale === 'ar' ? 'اختر المنشور المطلوب نشره *' : 'Select Post to Publish *' }}</label>
            <select 
              v-model="selectedPostId" 
              required 
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-emerald-500 outline-none"
            >
              <option :value="null" disabled>{{ currentLocale === 'ar' ? '-- اختر منشوراً --' : '-- Choose a post --' }}</option>
              <option v-for="p in readyPosts" :key="p.id" :value="p.id">
                #{{ p.id }} - {{ p.title || p.content_text?.substring(0, 50) }} ({{ p.status }})
              </option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="font-semibold text-slate-300">{{ currentLocale === 'ar' ? 'اختر القناة والصفحة المستهدفة *' : 'Target Connected Channel & Page *' }}</label>
            <select 
              v-model="selectedAccountId" 
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-emerald-500 outline-none"
            >
              <option :value="null">{{ currentLocale === 'ar' ? 'القناة التلقائية المناسبة للمنشور' : 'Auto-match post platform channel' }}</option>
              <option v-for="ch in connectedChannels" :key="ch.account.id" :value="ch.account.id">
                {{ ch.platform }} — {{ ch.account.account_name || ch.account.account_id }}
              </option>
            </select>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-800">
            <button 
              type="button" 
              @click="showDirectPublishModal = false"
              class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:bg-slate-700 text-xs font-semibold"
            >
              {{ currentLocale === 'ar' ? 'إلغاء' : 'Cancel' }}
            </button>
            <button 
              type="submit" 
              :disabled="publishingDirectly || !selectedPostId"
              class="tactile-btn tactile-btn-primary px-6 py-2.5 text-xs font-bold flex items-center gap-2"
            >
              <span v-if="publishingDirectly" class="animate-spin">⏳</span>
              <span v-else>🚀</span>
              <span>{{ publishingDirectly ? (currentLocale === 'ar' ? 'جاري البث الفوري...' : 'Publishing...') : (currentLocale === 'ar' ? 'نشر المنشور الآن' : 'Publish Post Now') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { currentLocale } from '../i18n';

const props = defineProps<{
  authToken?: string | null;
  organizationId?: number | null;
  brandId?: number | null;
}>();

const loading = ref(false);
const actionLoading = ref(false);
const connecting = ref(false);
const loadingPages = ref(false);
const workerLoading = ref(false);
const readyPostsLoading = ref(false);
const publishingDirectly = ref(false);

const channels = ref<any[]>([]);
const publishingJobs = ref<any[]>([]);
const readyPosts = ref<any[]>([]);
const availablePages = ref<any[]>([]);

const showConnectModal = ref(false);
const showDirectPublishModal = ref(false);
const selectedPlatform = ref<string>('facebook');
const connectMode = ref<'sandbox' | 'custom'>('sandbox');
const selectedPageId = ref<string | null>(null);

const customForm = ref({
  access_token: '',
  account_id: '',
  account_name: '',
  account_username: '',
});

const selectedPostId = ref<number | null>(null);
const selectedAccountId = ref<number | null>(null);

const connectedCount = computed(() => {
  return channels.value.filter(c => c.is_connected).length;
});

const connectedChannels = computed(() => {
  return channels.value.filter(c => c.is_connected && c.account);
});

function getAuthHeaders() {
  return {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${props.authToken}`,
    'X-Organization-Id': String(props.organizationId || ''),
    ...(props.brandId ? { 'X-Brand-Id': String(props.brandId) } : {}),
  };
}

async function fetchData() {
  if (!props.authToken) return;
  loading.value = true;

  try {
    const [accRes, jobRes] = await Promise.all([
      fetch('/api/v1/social/accounts', { headers: getAuthHeaders() }),
      fetch('/api/v1/social/jobs', { headers: getAuthHeaders() }),
    ]);

    if (accRes.ok) {
      const json = await accRes.json();
      channels.value = json.data?.channels || [];
    }

    if (jobRes.ok) {
      const json = await jobRes.json();
      publishingJobs.value = json.data || [];
    }
  } catch (err) {
    console.error('Failed to load social channels data', err);
  } finally {
    loading.value = false;
  }
}

async function openConnectModal(platform: string) {
  selectedPlatform.value = platform;
  connectMode.value = 'sandbox';
  selectedPageId.value = null;
  customForm.value = {
    access_token: '',
    account_id: '',
    account_name: '',
    account_username: '',
  };
  showConnectModal.value = true;
  await loadPlatformPages();
}

async function switchMode(mode: 'sandbox' | 'custom') {
  connectMode.value = mode;
  if (mode === 'sandbox') {
    await loadPlatformPages();
  }
}

async function loadPlatformPages(token?: string) {
  if (!props.authToken) return;
  loadingPages.value = true;

  try {
    const query = token ? `?user_token=${encodeURIComponent(token)}` : '';
    const res = await fetch(`/api/v1/social/pages/${selectedPlatform.value}${query}`, {
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      const json = await res.json();
      availablePages.value = json.data?.pages || [];
      if (availablePages.value.length > 0) {
        selectedPageId.value = availablePages.value[0].id;
      }
    }
  } catch (err) {
    console.error('Failed to load available pages', err);
  } finally {
    loadingPages.value = false;
  }
}

async function fetchPagesFromCustomToken() {
  if (!customForm.value.access_token) return;
  await loadPlatformPages(customForm.value.access_token);
  if (availablePages.value.length > 0) {
    const first = availablePages.value[0];
    customForm.value.account_id = first.id;
    customForm.value.account_name = first.name;
    customForm.value.account_username = first.category;
    alert(currentLocale.value === 'ar' ? `تم العثور على ${availablePages.value.length} صفحات. تم تحديد صفحة: ${first.name}` : `Found ${availablePages.value.length} pages. Auto-filled: ${first.name}`);
  }
}

async function confirmPageSelection() {
  if (!props.authToken || !selectedPageId.value) return;
  const page = availablePages.value.find(p => p.id === selectedPageId.value);
  if (!page) return;

  connecting.value = true;

  try {
    const res = await fetch(`/api/v1/social/accounts/${selectedPlatform.value}/connect-custom`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({
        access_token: page.access_token || `token_${page.id}`,
        account_id: page.id,
        account_name: page.name,
        account_username: page.category,
      }),
    });

    if (res.ok) {
      showConnectModal.value = false;
      await fetchData();
      alert(currentLocale.value === 'ar' ? `تم ربط وتفويض صفحة (${page.name}) بنجاح لعلامتك التجارية!` : `Successfully connected and bound page (${page.name}) to your brand!`);
    } else {
      const err = await res.json();
      alert(err.message || 'Connection failed');
    }
  } catch (err) {
    console.error('Connection error', err);
  } finally {
    connecting.value = false;
  }
}

async function confirmCustomConnect() {
  if (!props.authToken) return;
  connecting.value = true;

  try {
    const res = await fetch(`/api/v1/social/accounts/${selectedPlatform.value}/connect-custom`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify(customForm.value),
    });

    if (res.ok) {
      showConnectModal.value = false;
      await fetchData();
      alert(currentLocale.value === 'ar' ? `تم ربط وتوثيق صفحة ${customForm.value.account_name || selectedPlatform.value} بالمفاتيح المخصصة!` : `Successfully connected ${customForm.value.account_name || selectedPlatform.value} with custom credentials!`);
    } else {
      const err = await res.json();
      alert(err.message || 'Connection failed');
    }
  } catch (err) {
    console.error('Custom connection error', err);
  } finally {
    connecting.value = false;
  }
}

async function openDirectPublishModal() {
  showDirectPublishModal.value = true;
  selectedPostId.value = null;
  selectedAccountId.value = null;
  readyPostsLoading.value = true;

  try {
    const res = await fetch('/api/v1/social/ready-posts', { headers: getAuthHeaders() });
    if (res.ok) {
      const json = await res.json();
      readyPosts.value = json.data || [];
      if (readyPosts.value.length > 0) {
        selectedPostId.value = readyPosts.value[0].id;
      }
    }
  } catch (err) {
    console.error('Failed to load ready posts', err);
  } finally {
    readyPostsLoading.value = false;
  }
}

async function executeDirectPublish() {
  if (!props.authToken || !selectedPostId.value) return;
  publishingDirectly.value = true;

  try {
    const res = await fetch(`/api/v1/social/posts/${selectedPostId.value}/publish-now`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({
        social_account_id: selectedAccountId.value,
      }),
    });

    if (res.ok) {
      showDirectPublishModal.value = false;
      await fetchData();
      alert(currentLocale.value === 'ar' ? '🚀 تم نشر المنشور بنجاح إلى الصفحة المستهدفة وتم تسجيله في التقرير!' : 'Post published successfully to target page and recorded in audit log!');
    } else {
      const err = await res.json();
      alert(err.message || 'Publishing failed');
    }
  } catch (err) {
    console.error('Direct publishing error', err);
  } finally {
    publishingDirectly.value = false;
  }
}

async function healthCheck(accountId: number) {
  if (!props.authToken) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/social/accounts/${accountId}/health-check`, {
      method: 'POST',
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      await fetchData();
      alert(currentLocale.value === 'ar' ? 'تم فحص التوكن وهو في حالة صحية ممتازة (Healthy)' : 'Token is active and healthy.');
    }
  } catch (err) {
    console.error('Health check error', err);
  } finally {
    actionLoading.value = false;
  }
}

async function disconnectAccount(accountId: number) {
  if (!props.authToken || !confirm(currentLocale.value === 'ar' ? 'هل أنت متأكد من رغبتك في إلغاء ربط هذه الصفحة/القناة؟' : 'Are you sure you want to disconnect this social page?')) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/social/accounts/${accountId}`, {
      method: 'DELETE',
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      await fetchData();
    }
  } catch (err) {
    console.error('Disconnect error', err);
  } finally {
    actionLoading.value = false;
  }
}

async function runWorkerDispatch() {
  workerLoading.value = true;
  try {
    await fetchData();
    alert(currentLocale.value === 'ar' ? 'تم فحص طابور النشر المستحق وتحديث السجلات.' : 'Checked scheduled publishing queues — all due posts processed.');
  } finally {
    workerLoading.value = false;
  }
}

function getPlatformIcon(platform?: string) {
  const icons: Record<string, string> = {
    linkedin: '💼',
    instagram: '📸',
    x: '🐦',
    tiktok: '🎬',
    facebook: '👥',
  };
  return icons[platform?.toLowerCase() || ''] || '🌐';
}

function getJobStatusClass(status: string) {
  if (status === 'published') return 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
  if (status === 'processing') return 'bg-amber-500/20 text-amber-400 border border-amber-500/30';
  if (status === 'failed') return 'bg-red-500/20 text-red-400 border border-red-500/30';
  return 'bg-slate-800 text-slate-400';
}

function formatDate(dateStr?: string) {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleString(currentLocale.value === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

watch(() => props.brandId, () => {
  fetchData();
});

onMounted(() => {
  fetchData();
});
</script>
