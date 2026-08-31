<template>
  <div class="space-y-6">
    <!-- Top Header & Metrics -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900/60 p-6 rounded-3xl border border-slate-800/80 backdrop-blur-xl">
      <div>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-xl text-amber-400">
            ✍️
          </div>
          <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
              {{ t('contentStudio.title') }}
              <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                Phase 4 Complete
              </span>
            </h2>
            <p class="text-xs text-slate-400 max-w-2xl mt-0.5">{{ t('contentStudio.subtitle') }}</p>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-3">
        <button 
          @click="fetchPosts" 
          :disabled="loading"
          class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-slate-300 transition-colors flex items-center gap-1.5"
        >
          <span :class="{'animate-spin': loading}">🔄</span>
          {{ t('common.refresh') }}
        </button>

        <button 
          @click="openGeneratorWizard"
          class="tactile-btn tactile-btn-primary px-4 py-2 text-xs flex items-center gap-2"
        >
          <span>✨</span>
          {{ t('contentStudio.generateBtn') }}
        </button>
      </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-900/40 p-3 rounded-2xl border border-slate-800/60">
      <!-- Status Filter Tabs -->
      <div class="flex items-center gap-1.5 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
        <button 
          v-for="filter in statusFilters" 
          :key="filter.key"
          @click="currentFilter = filter.key; fetchPosts()"
          :class="[
            currentFilter === filter.key
              ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 font-semibold'
              : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-300 border-transparent',
            'px-3 py-1.5 rounded-xl text-xs border transition-all whitespace-nowrap'
          ]"
        >
          {{ filter.label }}
          <span class="ml-1 text-[10px] opacity-70">({{ getFilterCount(filter.key) }})</span>
        </button>
      </div>

      <!-- Search Input -->
      <div class="w-full sm:w-64">
        <input 
          v-model="searchQuery" 
          @input="debounceSearch"
          type="text" 
          :placeholder="t('contentStudio.quickPromptPlaceholder')" 
          class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-200 placeholder-slate-600 focus:outline-none focus:border-emerald-500/50"
        />
      </div>
    </div>

    <!-- Main Workspace: Posts Grid + Active Editor -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      
      <!-- Left Column: Posts List -->
      <div class="lg:col-span-5 space-y-3">
        <!-- Empty State -->
        <div v-if="!loading && posts.length === 0" class="p-8 rounded-3xl bg-slate-900/40 border border-slate-800/60 text-center space-y-3">
          <div class="w-12 h-12 rounded-2xl bg-slate-800 flex items-center justify-center text-xl mx-auto text-slate-400">
            📄
          </div>
          <h4 class="text-sm font-bold text-white">{{ t('contentStudio.noPostsTitle') }}</h4>
          <p class="text-xs text-slate-400 max-w-xs mx-auto">{{ t('contentStudio.noPostsDesc') }}</p>
          <button 
            @click="openGeneratorWizard"
            class="tactile-btn tactile-btn-primary px-4 py-2 text-xs inline-flex items-center gap-2"
          >
            <span>✨</span> {{ t('contentStudio.generateBtn') }}
          </button>
        </div>

        <!-- Post Cards -->
        <div 
          v-for="post in posts" 
          :key="post.id"
          @click="selectPost(post)"
          :class="[
            selectedPost?.id === post.id 
              ? 'bg-slate-800/90 border-emerald-500/50 shadow-lg shadow-emerald-950/20' 
              : 'bg-slate-900/60 border-slate-800/80 hover:bg-slate-850 hover:border-slate-700',
            'p-4 rounded-2xl border transition-all cursor-pointer space-y-2.5 relative group'
          ]"
        >
          <!-- Card Header: Platform, Pillar, Quality Score -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="text-base" :title="post.primary_platform">{{ getPlatformIcon(post.primary_platform) }}</span>
              <span class="text-[11px] font-bold text-slate-300 uppercase tracking-wide">{{ post.primary_platform }}</span>
              <span v-if="post.pillar" class="px-2 py-0.5 text-[9px] rounded-md bg-slate-800 text-slate-400 border border-slate-700 truncate max-w-[130px]">
                {{ post.pillar.name }}
              </span>
            </div>

            <!-- Quality Score Pill -->
            <div 
              v-if="post.latest_audit" 
              class="flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
              :class="getScoreBadgeClass(post.latest_audit.score)"
            >
              <span>🛡️</span>
              <span>{{ post.latest_audit.score }}%</span>
            </div>
          </div>

          <!-- Card Content: Hook & Title -->
          <div>
            <h4 class="text-xs font-bold text-white line-clamp-1">{{ post.title }}</h4>
            <p class="text-[11px] text-slate-400 line-clamp-2 mt-1 leading-relaxed">{{ post.hook || post.caption }}</p>
          </div>

          <!-- Card Footer: Status & Variations Count -->
          <div class="flex items-center justify-between text-[10px] text-slate-500 pt-1 border-t border-slate-800/50">
            <div class="flex items-center gap-2">
              <span 
                class="px-2 py-0.5 rounded font-bold uppercase tracking-wider text-[9px]"
                :class="getStatusBadgeClass(post.status)"
              >
                {{ post.status }}
              </span>
              <span>{{ post.variations?.length || 0 }} {{ t('contentStudio.editor.variations') }}</span>
            </div>

            <span class="text-[10px] text-slate-400">{{ formatDate(post.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Right Column: Detail & Multi-Platform Editor -->
      <div class="lg:col-span-7 space-y-6">
        <div v-if="selectedPost" class="bg-slate-900/60 p-6 rounded-3xl border border-slate-800/80 space-y-6 backdrop-blur-xl">
          
          <!-- Post Details Top Bar -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-lg">{{ getPlatformIcon(activeVariationPlatform) }}</span>
                <h3 class="text-base font-bold text-white">{{ selectedPost.title }}</h3>
              </div>
              <p class="text-xs text-slate-400 mt-0.5">
                {{ selectedPost.dialect }} • {{ selectedPost.tone }} • {{ selectedPost.language === 'ar' ? 'العربية' : 'English' }}
              </p>
            </div>

            <!-- Lifecycle Actions -->
            <div class="flex items-center gap-2">
              <button 
                v-if="selectedPost.status !== 'approved'"
                @click="approvePost(selectedPost.id)"
                :disabled="actionLoading"
                class="px-3 py-1.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/40 text-emerald-300 text-xs font-semibold transition-colors flex items-center gap-1"
              >
                <span>✅</span>
                {{ t('contentStudio.editor.approveAction') }}
              </button>

              <button 
                @click="deletePost(selectedPost.id)"
                :disabled="actionLoading"
                class="p-1.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs transition-colors"
                :title="t('contentStudio.editor.deleteAction')"
              >
                🗑️
              </button>
            </div>
          </div>

          <!-- Platform Tabs Selector -->
          <div class="flex items-center gap-2 overflow-x-auto pb-1 border-b border-slate-800/60">
            <button 
              v-for="platform in availablePlatforms" 
              :key="platform.key"
              @click="activeVariationPlatform = platform.key"
              :class="[
                activeVariationPlatform === platform.key
                  ? 'bg-slate-800 text-white border-emerald-500/40 font-bold'
                  : 'text-slate-400 hover:text-slate-200 border-transparent',
                'px-3 py-1.5 rounded-xl text-xs border flex items-center gap-2 transition-all'
              ]"
            >
              <span>{{ platform.icon }}</span>
              <span>{{ platform.label }}</span>
              <span v-if="hasVariationFor(platform.key)" class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            </button>
          </div>

          <!-- Editable Fields for Active Platform -->
          <div class="space-y-4">
            <!-- Hook Field -->
            <div class="space-y-1.5">
              <div class="flex items-center justify-between text-xs">
                <label class="font-bold text-slate-300 flex items-center gap-1.5">
                  <span>⚡</span> {{ t('contentStudio.editor.hookLabel') }}
                </label>
                <button 
                  @click="regenerateComponent('hook')"
                  :disabled="actionLoading"
                  class="text-[11px] text-emerald-400 hover:text-emerald-300 flex items-center gap-1"
                >
                  <span>🔄</span> {{ t('contentStudio.editor.regenerateHook') }}
                </button>
              </div>
              <textarea 
                v-model="currentVariationHook" 
                rows="2"
                class="w-full bg-slate-950/80 border border-slate-800 rounded-xl p-3 text-xs text-slate-200 focus:outline-none focus:border-emerald-500/50 resize-none font-sans"
              ></textarea>
            </div>

            <!-- Body / Caption Field -->
            <div class="space-y-1.5">
              <div class="flex items-center justify-between text-xs">
                <label class="font-bold text-slate-300 flex items-center gap-1.5">
                  <span>📝</span> {{ t('contentStudio.editor.captionLabel') }}
                </label>
                <span class="text-[10px] text-slate-400">
                  {{ currentVariationBody.length }} {{ t('contentStudio.editor.charCount') }}
                </span>
              </div>
              <textarea 
                v-model="currentVariationBody" 
                rows="6"
                class="w-full bg-slate-950/80 border border-slate-800 rounded-xl p-3 text-xs text-slate-200 focus:outline-none focus:border-emerald-500/50 resize-y font-sans leading-relaxed"
              ></textarea>
            </div>

            <!-- CTA Field -->
            <div class="space-y-1.5">
              <div class="flex items-center justify-between text-xs">
                <label class="font-bold text-slate-300 flex items-center gap-1.5">
                  <span>🎯</span> {{ t('contentStudio.editor.ctaLabel') }}
                </label>
                <button 
                  @click="regenerateComponent('cta')"
                  :disabled="actionLoading"
                  class="text-[11px] text-emerald-400 hover:text-emerald-300 flex items-center gap-1"
                >
                  <span>🔄</span> {{ t('contentStudio.editor.regenerateCta') }}
                </button>
              </div>
              <input 
                v-model="currentVariationCta" 
                type="text"
                class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500/50"
              />
            </div>

            <!-- Hashtags Field -->
            <div class="space-y-1.5">
              <label class="font-bold text-slate-300 text-xs flex items-center gap-1.5">
                <span>🏷️</span> {{ t('contentStudio.editor.hashtagsLabel') }}
              </label>
              <div class="flex flex-wrap gap-1.5 p-2 bg-slate-950/60 rounded-xl border border-slate-800/80">
                <span 
                  v-for="(tag, idx) in currentVariationHashtags" 
                  :key="idx"
                  class="px-2.5 py-1 rounded-lg bg-emerald-950/40 border border-emerald-500/30 text-[11px] text-emerald-400 font-medium"
                >
                  {{ tag }}
                </span>
              </div>
            </div>

            <!-- Visual Brief & Direct AI Image Generator (Phase I) -->
            <div v-if="selectedPost.visual_brief" class="p-4 rounded-2xl bg-gradient-to-br from-slate-950 to-slate-900 border border-slate-800 space-y-3">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="text-base">🎨</span>
                  <span class="text-xs font-bold text-white">{{ currentLocale === 'ar' ? 'الموجه البصري المقترح (Visual Brief)' : 'AI Visual Brief' }}</span>
                  <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-purple-500/10 text-purple-400 border border-purple-500/20">
                    {{ selectedPost.visual_brief.type || 'product_showcase' }}
                  </span>
                </div>
                
                <div class="flex items-center gap-2">
                  <button 
                    @click="regenerateComponent('visual_brief')"
                    :disabled="actionLoading"
                    class="text-[11px] text-slate-400 hover:text-slate-200 flex items-center gap-1"
                  >
                    <span>🔄</span>
                  </button>
                  <button 
                    @click="handleGenerateVisualForPost(selectedPost)"
                    :disabled="generatingVisualPostId === selectedPost.id"
                    class="tactile-btn tactile-btn-primary px-3 py-1.5 text-xs flex items-center gap-1.5 shadow-lg shadow-emerald-950/40"
                  >
                    <span v-if="generatingVisualPostId === selectedPost.id" class="animate-spin">⏳</span>
                    <span v-else>✨</span>
                    <span>{{ generatingVisualPostId === selectedPost.id ? (currentLocale === 'ar' ? 'جاري التوليد...' : 'Generating...') : (currentLocale === 'ar' ? 'ولّد الصورة المقترحة' : 'Generate Proposed Image') }}</span>
                  </button>
                </div>
              </div>

              <p class="text-xs text-slate-300 leading-relaxed bg-slate-950/60 p-3 rounded-xl border border-slate-800/60">
                {{ selectedPost.visual_brief.description }}
              </p>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                <div class="p-2.5 rounded-xl bg-slate-950/40 border border-slate-800/40 space-y-0.5">
                  <span class="text-slate-400 font-bold">Text Overlay:</span>
                  <p class="text-slate-200 font-medium truncate">{{ selectedPost.visual_brief.suggested_text_overlay || selectedPost.title }}</p>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-950/40 border border-slate-800/40 space-y-0.5">
                  <span class="text-slate-400 font-bold">Color Notes:</span>
                  <p class="text-slate-200 font-medium truncate">{{ selectedPost.visual_brief.color_notes || 'Brand Palette' }}</p>
                </div>
              </div>

              <!-- Attached Visual Preview -->
              <div v-if="postVisualAssets[selectedPost.id]" class="pt-3 border-t border-slate-800/80 flex items-center gap-3 bg-slate-950/40 p-3 rounded-xl">
                <div class="w-16 h-16 rounded-xl overflow-hidden border border-slate-700 bg-slate-900 flex-shrink-0 flex items-center justify-center">
                  <img v-if="postVisualAssets[selectedPost.id].public_url" :src="postVisualAssets[selectedPost.id].public_url" class="w-full h-full object-cover" />
                  <div v-else-if="postVisualAssets[selectedPost.id].metadata?.svg_markup" v-html="postVisualAssets[selectedPost.id].metadata.svg_markup" class="w-full h-full scale-50"></div>
                </div>
                <div class="space-y-1 flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-emerald-400">✓ {{ currentLocale === 'ar' ? 'تم توليد الصورة وربطها' : 'Visual Asset Attached' }}</span>
                    <span class="px-1.5 py-0.2 rounded text-[9px] font-mono bg-slate-800 text-slate-300">{{ postVisualAssets[selectedPost.id].aspect_ratio }}</span>
                  </div>
                  <p class="text-[10px] text-slate-400 truncate">{{ postVisualAssets[selectedPost.id].title }}</p>
                </div>
              </div>
            </div>

            <!-- Save & Repurpose Actions Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-800">
              <button 
                @click="saveCurrentVariation" 
                :disabled="actionLoading"
                class="tactile-btn tactile-btn-primary px-4 py-2 text-xs flex items-center gap-1.5"
              >
                <span>💾</span>
                {{ t('common.save') }}
              </button>

              <div class="flex items-center gap-2">
                <button 
                  @click="repurposeAllChannels"
                  :disabled="actionLoading"
                  class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors flex items-center gap-1.5"
                >
                  <span>🌐</span>
                  {{ t('contentStudio.editor.repurposeAction') }}
                </button>

                <button 
                  @click="reAuditContent"
                  :disabled="actionLoading"
                  class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors flex items-center gap-1.5"
                >
                  <span>🛡️</span>
                  {{ t('contentStudio.quality.auditAction') }}
                </button>
              </div>
            </div>
          </div>

          <!-- Live Quality & Brand Compliance Widget -->
          <div v-if="selectedPost.latest_audit" class="p-5 rounded-2xl bg-slate-950/90 border border-slate-800/80 space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-lg">🛡️</span>
                <div>
                  <h4 class="text-xs font-bold text-white">{{ t('contentStudio.quality.title') }}</h4>
                  <p class="text-[10px] text-slate-400">Automated brand compliance & conversion evaluation</p>
                </div>
              </div>

              <div 
                class="px-3 py-1 rounded-xl text-xs font-black"
                :class="getScoreBadgeClass(selectedPost.latest_audit.score)"
              >
                {{ selectedPost.latest_audit.score }} / 100
              </div>
            </div>

            <!-- Score Metrics Bars -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 space-y-1">
                <div class="text-[10px] text-slate-400">{{ t('contentStudio.quality.brandAlignment') }}</div>
                <div class="text-sm font-black text-emerald-400">{{ selectedPost.latest_audit.brand_alignment_score }}%</div>
              </div>

              <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 space-y-1">
                <div class="text-[10px] text-slate-400">{{ t('contentStudio.quality.hookStrength') }}</div>
                <div class="text-sm font-black text-emerald-400">{{ selectedPost.latest_audit.hook_strength_score }}%</div>
              </div>

              <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 space-y-1">
                <div class="text-[10px] text-slate-400">{{ t('contentStudio.quality.clarity') }}</div>
                <div class="text-sm font-black text-emerald-400">{{ selectedPost.latest_audit.clarity_score }}%</div>
              </div>

              <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 space-y-1">
                <div class="text-[10px] text-slate-400">{{ t('contentStudio.quality.safety') }}</div>
                <div class="text-sm font-black text-emerald-400">{{ selectedPost.latest_audit.safety_compliance_score }}%</div>
              </div>
            </div>

            <!-- Strengths & Suggestions -->
            <div class="space-y-2 text-xs">
              <div v-if="selectedPost.latest_audit.strengths?.length" class="space-y-1">
                <div class="text-[11px] font-bold text-emerald-400">✨ {{ t('contentStudio.quality.strengths') }}</div>
                <ul class="list-disc list-inside text-slate-300 text-[11px] space-y-0.5">
                  <li v-for="(str, i) in selectedPost.latest_audit.strengths" :key="i">{{ str }}</li>
                </ul>
              </div>

              <div v-if="selectedPost.latest_audit.warnings?.length" class="space-y-1">
                <div class="text-[11px] font-bold text-amber-400">⚠️ {{ t('contentStudio.quality.warnings') }}</div>
                <ul class="list-disc list-inside text-amber-200/90 text-[11px] space-y-0.5">
                  <li v-for="(warn, i) in selectedPost.latest_audit.warnings" :key="i">{{ warn }}</li>
                </ul>
              </div>

              <div v-if="selectedPost.latest_audit.suggestions?.length" class="space-y-1">
                <div class="text-[11px] font-bold text-cyan-400">💡 {{ t('contentStudio.quality.suggestions') }}</div>
                <ul class="list-disc list-inside text-slate-300 text-[11px] space-y-0.5">
                  <li v-for="(sug, i) in selectedPost.latest_audit.suggestions" :key="i">{{ sug }}</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="p-12 rounded-3xl bg-slate-900/40 border border-slate-800/60 text-center space-y-2">
          <p class="text-xs text-slate-500">Select a content post on the left to preview and edit multi-platform copy.</p>
        </div>
      </div>
    </div>

    <!-- AI Generation Wizard Modal -->
    <div v-if="showWizard" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="w-full max-w-xl bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-6 shadow-2xl overflow-y-auto max-h-[90vh]">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div class="flex items-center gap-2.5">
            <span class="text-xl">✨</span>
            <h3 class="text-base font-bold text-white">{{ t('contentStudio.wizard.title') }}</h3>
          </div>
          <button @click="showWizard = false" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form @submit.prevent="handleGeneratePost" class="space-y-4 text-xs">
          <!-- Pillar Selection -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-300">{{ t('contentStudio.wizard.selectPillar') }}</label>
            <select 
              v-model="wizardForm.pillar_id"
              class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-emerald-500/50"
            >
              <option :value="null">-- Auto-Select from Active Strategy --</option>
              <option v-for="pillar in activePillars" :key="pillar.id" :value="pillar.id">
                {{ pillar.name }} ({{ pillar.objective }})
              </option>
            </select>
          </div>

          <!-- Tone & Dialect Pickers -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="font-bold text-slate-300">{{ t('contentStudio.wizard.selectTone') }}</label>
              <select 
                v-model="wizardForm.tone"
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-emerald-500/50"
              >
                <option value="professional">Professional & Authority</option>
                <option value="conversational">Conversational & Friendly</option>
                <option value="witty">Witty & Creative</option>
                <option value="educational">Educational & Actionable</option>
                <option value="direct_response">High-Urgency & Direct Response</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label class="font-bold text-slate-300">{{ t('contentStudio.wizard.selectDialect') }}</label>
              <select 
                v-model="wizardForm.dialect"
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-emerald-500/50"
              >
                <option value="saudi">Saudi / Gulf Dialect (اللهجة السعودية والخليجية)</option>
                <option value="msa">Modern Standard Arabic (الفصحى المعاصرة)</option>
                <option value="egyptian">Egyptian Dialect (اللهجة المصرية)</option>
                <option value="uae">UAE Business (أسلوب الأعمال الإماراتي)</option>
              </select>
            </div>
          </div>

          <!-- Primary Platform -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-300">{{ t('contentStudio.wizard.primaryPlatform') }}</label>
            <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
              <button 
                v-for="platform in availablePlatforms" 
                :key="platform.key"
                type="button"
                @click="wizardForm.primary_platform = platform.key"
                :class="[
                  wizardForm.primary_platform === platform.key
                    ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/50 font-bold'
                    : 'bg-slate-950 text-slate-400 border-slate-800 hover:bg-slate-850',
                  'p-2 rounded-xl text-xs border flex flex-col items-center gap-1 transition-all'
                ]"
              >
                <span class="text-base">{{ platform.icon }}</span>
                <span>{{ platform.label }}</span>
              </button>
            </div>
          </div>

          <!-- Custom Instructions / Angle -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-300">{{ t('contentStudio.wizard.customPrompt') }}</label>
            <textarea 
              v-model="wizardForm.prompt" 
              rows="3"
              placeholder="e.g. Focus on ROI, customer transformation story, or highlight our free trial offer..."
              class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-slate-200 focus:outline-none focus:border-emerald-500/50 resize-none font-sans"
            ></textarea>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
            <button 
              type="button" 
              @click="showWizard = false"
              class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:bg-slate-700 text-xs font-semibold"
            >
              {{ t('common.cancel') }}
            </button>
            <button 
              type="submit" 
              :disabled="generating"
              class="tactile-btn tactile-btn-primary px-5 py-2 text-xs flex items-center gap-2"
            >
              <span v-if="generating" class="animate-spin">⏳</span>
              <span v-else>✨</span>
              {{ generating ? t('common.processing') : t('contentStudio.wizard.generateAction') }}
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

// State
const loading = ref(false);
const actionLoading = ref(false);
const generating = ref(false);
const posts = ref<any[]>([]);
const selectedPost = ref<any | null>(null);
const currentFilter = ref<string>('all');
const searchQuery = ref<string>('');
const showWizard = ref(false);
const activeVariationPlatform = ref<string>('linkedin');
const activePillars = ref<any[]>([]);

// Wizard form state
const wizardForm = ref({
  pillar_id: null as number | null,
  tone: 'professional',
  dialect: 'saudi',
  language: 'ar',
  primary_platform: 'linkedin',
  prompt: '',
});

// Platform definitions
const availablePlatforms = [
  { key: 'linkedin', label: 'LinkedIn', icon: '💼' },
  { key: 'instagram', label: 'Instagram', icon: '📸' },
  { key: 'x', label: 'X / Twitter', icon: '🐦' },
  { key: 'tiktok', label: 'TikTok', icon: '🎬' },
  { key: 'facebook', label: 'Facebook', icon: '👥' },
];

const statusFilters = computed(() => [
  { key: 'all', label: t('contentStudio.filterAll') },
  { key: 'draft', label: t('contentStudio.filterDraft') },
  { key: 'approved', label: t('contentStudio.filterApproved') },
  { key: 'scheduled', label: t('contentStudio.filterScheduled') },
]);

// Computed active variation fields
const currentVariation = computed(() => {
  if (!selectedPost.value || !selectedPost.value.variations) return null;
  return selectedPost.value.variations.find((v: any) => v.platform === activeVariationPlatform.value);
});

const currentVariationHook = computed({
  get: () => currentVariation.value?.hook || selectedPost.value?.hook || '',
  set: (val: string) => {
    if (currentVariation.value) currentVariation.value.hook = val;
  }
});

const currentVariationBody = computed({
  get: () => currentVariation.value?.body || selectedPost.value?.caption || '',
  set: (val: string) => {
    if (currentVariation.value) currentVariation.value.body = val;
  }
});

const currentVariationCta = computed({
  get: () => currentVariation.value?.cta || selectedPost.value?.cta || '',
  set: (val: string) => {
    if (currentVariation.value) currentVariation.value.cta = val;
  }
});

const currentVariationHashtags = computed(() => {
  return currentVariation.value?.hashtags || selectedPost.value?.hashtags || [];
});

// Methods
function getAuthHeaders() {
  return {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${props.authToken}`,
    'X-Organization-Id': String(props.organizationId || ''),
    ...(props.brandId ? { 'X-Brand-Id': String(props.brandId) } : {}),
    'X-Locale': currentLocale.value,
  };
}

async function fetchPosts() {
  if (!props.authToken) return;
  loading.value = true;

  try {
    const params = new URLSearchParams();
    if (currentFilter.value !== 'all') {
      params.append('status', currentFilter.value);
    }
    if (searchQuery.value) {
      params.append('search', searchQuery.value);
    }

    const res = await fetch(`/api/v1/content?${params.toString()}`, {
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      const json = await res.json();
      posts.value = json.data || [];
      if (posts.value.length > 0 && !selectedPost.value) {
        selectPost(posts.value[0]);
      }
    }
  } catch (err) {
    console.error('Failed to load posts', err);
  } finally {
    loading.value = false;
  }
}

async function fetchActiveStrategyPillars() {
  if (!props.authToken) return;
  try {
    const res = await fetch('/api/v1/strategy', {
      headers: getAuthHeaders(),
    });
    if (res.ok) {
      const json = await res.json();
      activePillars.value = json.data?.strategy?.pillars || [];
    }
  } catch (err) {
    console.error('Failed to fetch pillars', err);
  }
}

function selectPost(post: any) {
  selectedPost.value = post;
  activeVariationPlatform.value = post.primary_platform || 'linkedin';
}

function hasVariationFor(platformKey: string) {
  return selectedPost.value?.variations?.some((v: any) => v.platform === platformKey);
}

function getPlatformIcon(platform: string) {
  const match = availablePlatforms.find(p => p.key === platform);
  return match?.icon || '📱';
}

function getFilterCount(key: string) {
  if (key === 'all') return posts.value.length;
  return posts.value.filter(p => p.status === key).length;
}

function getScoreBadgeClass(score: number) {
  if (score >= 80) return 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
  if (score >= 60) return 'bg-amber-500/20 text-amber-400 border border-amber-500/30';
  return 'bg-red-500/20 text-red-400 border border-red-500/30';
}

function getStatusBadgeClass(status: string) {
  if (status === 'approved') return 'bg-emerald-500/20 text-emerald-400';
  if (status === 'scheduled') return 'bg-cyan-500/20 text-cyan-400';
  return 'bg-amber-500/20 text-amber-400';
}

function formatDate(dateStr?: string) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString(currentLocale.value === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'short',
    day: 'numeric',
  });
}

function openGeneratorWizard() {
  showWizard.value = true;
  fetchActiveStrategyPillars();
}

async function handleGeneratePost() {
  if (!props.authToken) return;
  generating.value = true;

  try {
    const res = await fetch('/api/v1/content/generate', {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({
        pillar_id: wizardForm.value.pillar_id,
        tone: wizardForm.value.tone,
        dialect: wizardForm.value.dialect,
        language: wizardForm.value.language,
        primary_platform: wizardForm.value.primary_platform,
        prompt: wizardForm.value.prompt || undefined,
      }),
    });

    if (res.ok) {
      const json = await res.json();
      showWizard.value = false;
      await fetchPosts();
      if (json.data) {
        selectPost(json.data);
      }
    } else {
      const err = await res.json();
      alert(err.message || 'Generation failed');
    }
  } catch (err) {
    console.error('Generation error', err);
  } finally {
    generating.value = false;
  }
}

async function saveCurrentVariation() {
  if (!selectedPost.value || !props.authToken) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/content/${selectedPost.value.id}/variations/${activeVariationPlatform.value}`, {
      method: 'PATCH',
      headers: getAuthHeaders(),
      body: JSON.stringify({
        body: currentVariationBody.value,
        hook: currentVariationHook.value,
        cta: currentVariationCta.value,
      }),
    });

    if (res.ok) {
      await fetchPostDetails(selectedPost.value.id);
    }
  } catch (err) {
    console.error('Failed to save variation', err);
  } finally {
    actionLoading.value = false;
  }
}

async function regenerateComponent(component: 'hook' | 'cta' | 'visual_brief') {
  if (!selectedPost.value || !props.authToken) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/content/${selectedPost.value.id}/regenerate`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({ component }),
    });

    if (res.ok) {
      const json = await res.json();
      selectPost(json.data);
    }
  } catch (err) {
    console.error('Failed to regenerate component', err);
  } finally {
    actionLoading.value = false;
  }
}

async function repurposeAllChannels() {
  if (!selectedPost.value || !props.authToken) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/content/${selectedPost.value.id}/repurpose`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({
        platforms: ['linkedin', 'instagram', 'x', 'facebook', 'tiktok'],
      }),
    });

    if (res.ok) {
      const json = await res.json();
      selectPost(json.data);
    }
  } catch (err) {
    console.error('Failed to repurpose', err);
  } finally {
    actionLoading.value = false;
  }
}

async function reAuditContent() {
  if (!selectedPost.value || !props.authToken) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/content/${selectedPost.value.id}/quality-check`, {
      method: 'POST',
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      const json = await res.json();
      selectedPost.value.latest_audit = json.data;
    }
  } catch (err) {
    console.error('Failed to audit', err);
  } finally {
    actionLoading.value = false;
  }
}

async function approvePost(postId: number) {
  if (!props.authToken) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/content/${postId}/approve`, {
      method: 'POST',
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      const json = await res.json();
      selectPost(json.data);
      await fetchPosts();
    }
  } catch (err) {
    console.error('Failed to approve', err);
  } finally {
    actionLoading.value = false;
  }
}

async function deletePost(postId: number) {
  if (!props.authToken || !confirm('Are you sure you want to delete this content post?')) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/content/${postId}`, {
      method: 'DELETE',
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      selectedPost.value = null;
      await fetchPosts();
    }
  } catch (err) {
    console.error('Failed to delete post', err);
  } finally {
    actionLoading.value = false;
  }
}

async function fetchPostDetails(postId: number) {
  const res = await fetch(`/api/v1/content/${postId}`, {
    headers: getAuthHeaders(),
  });
  if (res.ok) {
    const json = await res.json();
    selectPost(json.data);
  }
}

const generatingVisualPostId = ref<number | null>(null);
const postVisualAssets = ref<Record<number, any>>({});

async function handleGenerateVisualForPost(post: any) {
  if (!props.authToken || !post) return;
  generatingVisualPostId.value = post.id;

  try {
    const res = await fetch('/api/v1/creative/generate', {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({
        content_post_id: post.id,
        title: post.title,
        hook: post.hook,
        visual_style: post.visual_brief?.type || 'product_showcase',
        aspect_ratio: post.primary_platform === 'instagram' ? '1:1' : (post.primary_platform === 'tiktok' ? '9:16' : '16:9'),
        visual_brief: post.visual_brief,
      }),
    });

    if (res.ok) {
      const json = await res.json();
      postVisualAssets.value[post.id] = json.data;
      alert(currentLocale.value === 'ar' ? 'تم توليد وتصميم الصورة المقترحة وربطها بالمنشور بنجاح!' : 'Proposed visual asset generated and attached successfully!');
    } else {
      const err = await res.json();
      alert(err.message || 'Failed to generate visual asset.');
    }
  } catch (err: any) {
    alert(err.message || 'Error communicating with visual generator.');
  } finally {
    generatingVisualPostId.value = null;
  }
}

let searchTimeout: any = null;
function debounceSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchPosts();
  }, 350);
}

watch(() => props.brandId, () => {
  selectedPost.value = null;
  fetchPosts();
  fetchActiveStrategyPillars();
});

onMounted(() => {
  fetchPosts();
  fetchActiveStrategyPillars();
});
</script>
