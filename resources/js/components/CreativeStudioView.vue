<template>
  <div class="space-y-6">
    <!-- Top Header & Metrics -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900/60 p-6 rounded-3xl border border-slate-800/80 backdrop-blur-xl">
      <div>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-xl text-purple-400">
            🎨
          </div>
          <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
              {{ t('creativeStudio.title') }}
              <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                Phase 5 Complete
              </span>
            </h2>
            <p class="text-xs text-slate-400 max-w-2xl mt-0.5">{{ t('creativeStudio.subtitle') }}</p>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap items-center gap-3">
        <button 
          @click="fetchAssets" 
          :disabled="loading"
          class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-slate-300 transition-colors flex items-center gap-1.5"
        >
          <span :class="{'animate-spin': loading}">🔄</span>
          {{ t('common.refresh') }}
        </button>

        <button 
          @click="openReelWizard"
          class="px-3.5 py-2 rounded-xl bg-purple-600/20 hover:bg-purple-600/30 border border-purple-500/40 text-xs font-semibold text-purple-300 transition-colors flex items-center gap-1.5"
        >
          <span>🎬</span>
          {{ t('creativeStudio.generateReelBtn') }}
        </button>

        <button 
          @click="openVisualWizard"
          class="tactile-btn tactile-btn-primary px-4 py-2 text-xs flex items-center gap-2"
        >
          <span>✨</span>
          {{ t('creativeStudio.generateVisualBtn') }}
        </button>
      </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-900/40 p-3 rounded-2xl border border-slate-800/60">
      <!-- Aspect Ratio & Type Tabs -->
      <div class="flex items-center gap-1.5 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
        <button 
          v-for="filter in aspectFilters" 
          :key="filter.key"
          @click="currentFilter = filter.key; fetchAssets()"
          :class="[
            currentFilter === filter.key
              ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 font-semibold'
              : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-300 border-transparent',
            'px-3 py-1.5 rounded-xl text-xs border transition-all whitespace-nowrap flex items-center gap-1.5'
          ]"
        >
          <span>{{ filter.icon }}</span>
          <span>{{ filter.label }}</span>
          <span class="text-[10px] opacity-70">({{ getFilterCount(filter.key) }})</span>
        </button>
      </div>

      <!-- Search Input -->
      <div class="w-full sm:w-64">
        <input 
          v-model="searchQuery" 
          @input="debounceSearch"
          type="text" 
          placeholder="Search by title, hook, or style..." 
          class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-200 placeholder-slate-600 focus:outline-none focus:border-emerald-500/50"
        />
      </div>
    </div>

    <!-- Main Workspace Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      
      <!-- Left Column: Media Gallery List -->
      <div class="lg:col-span-5 space-y-3">
        <!-- Empty State -->
        <div v-if="!loading && assets.length === 0" class="p-8 rounded-3xl bg-slate-900/40 border border-slate-800/60 text-center space-y-3">
          <div class="w-12 h-12 rounded-2xl bg-slate-800 flex items-center justify-center text-xl mx-auto text-slate-400">
            🖼️
          </div>
          <h4 class="text-sm font-bold text-white">{{ t('creativeStudio.noAssetsTitle') }}</h4>
          <p class="text-xs text-slate-400 max-w-xs mx-auto">{{ t('creativeStudio.noAssetsDesc') }}</p>
          <div class="flex items-center justify-center gap-2 pt-2">
            <button 
              @click="openVisualWizard"
              class="tactile-btn tactile-btn-primary px-4 py-2 text-xs inline-flex items-center gap-1.5"
            >
              <span>✨</span> {{ t('creativeStudio.generateVisualBtn') }}
            </button>
          </div>
        </div>

        <!-- Asset Cards -->
        <div 
          v-for="asset in assets" 
          :key="asset.id"
          @click="selectAsset(asset)"
          :class="[
            selectedAsset?.id === asset.id 
              ? 'bg-slate-800/90 border-emerald-500/50 shadow-lg shadow-emerald-950/20' 
              : 'bg-slate-900/60 border-slate-800/80 hover:bg-slate-850 hover:border-slate-700',
            'p-4 rounded-2xl border transition-all cursor-pointer space-y-2.5 relative group'
          ]"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="text-sm">{{ getAssetIcon(asset) }}</span>
              <span class="text-[11px] font-bold text-slate-300 uppercase tracking-wide">
                {{ asset.aspect_ratio }} • {{ asset.visual_style || asset.file_type }}
              </span>
            </div>

            <span 
              class="px-2 py-0.5 text-[9px] font-bold rounded-full border"
              :class="asset.content_post_id ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-slate-800 text-slate-400 border-slate-700'"
            >
              {{ asset.content_post_id ? `${t('creativeStudio.attachedTo')}${asset.content_post_id}` : 'Standalone' }}
            </span>
          </div>

          <!-- Title & Hook preview -->
          <div>
            <h4 class="text-xs font-bold text-white line-clamp-1">{{ asset.title }}</h4>
            <p class="text-[11px] text-slate-400 line-clamp-2 mt-1">{{ asset.text_overlay || asset.prompt_used }}</p>
          </div>

          <!-- Card Footer -->
          <div class="flex items-center justify-between text-[10px] text-slate-500 pt-1 border-t border-slate-800/50">
            <span>{{ asset.width }} × {{ asset.height }}px</span>
            <span>{{ formatDate(asset.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Right Column: Asset Preview & Inspector -->
      <div class="lg:col-span-7 space-y-6">
        <div v-if="selectedAsset" class="bg-slate-900/60 p-6 rounded-3xl border border-slate-800/80 space-y-6 backdrop-blur-xl">
          
          <!-- Top Inspector Bar -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-lg">{{ getAssetIcon(selectedAsset) }}</span>
                <h3 class="text-base font-bold text-white">{{ selectedAsset.title }}</h3>
              </div>
              <p class="text-xs text-slate-400 mt-0.5">
                {{ selectedAsset.aspect_ratio }} ({{ selectedAsset.width }} × {{ selectedAsset.height }}px) • {{ selectedAsset.mime_type }}
              </p>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
              <button 
                @click="handleRegenerateVariation(selectedAsset)"
                :disabled="generating || actionLoading"
                class="px-3 py-1.5 rounded-xl bg-purple-600/20 hover:bg-purple-600/30 border border-purple-500/40 text-purple-300 text-xs font-semibold transition-colors flex items-center gap-1.5"
              >
                <span>🔄</span>
                <span>{{ currentLocale === 'ar' ? 'توليد نسخة مختلفة' : 'Regenerate Variation' }}</span>
              </button>

              <a 
                v-if="selectedAsset.public_url"
                :href="selectedAsset.public_url"
                download
                target="_blank"
                class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-slate-200 transition-colors flex items-center gap-1.5"
              >
                <span>⬇️</span>
                <span>{{ t('creativeStudio.downloadSvg') }}</span>
              </a>
              <button 
                v-else-if="selectedAsset.file_type === 'graphic_card' && selectedAsset.metadata?.svg_markup"
                @click="downloadSvg(selectedAsset)"
                class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-slate-200 transition-colors flex items-center gap-1.5"
              >
                <span>⬇️</span>
                {{ t('creativeStudio.downloadSvg') }}
              </button>

              <button 
                @click="openAttachModal(selectedAsset)"
                class="px-3 py-1.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/40 text-emerald-300 text-xs font-semibold transition-colors flex items-center gap-1.5"
              >
                <span>📎</span>
                {{ t('creativeStudio.attachToPost') }}
              </button>

              <button 
                @click="deleteAsset(selectedAsset.id)"
                :disabled="actionLoading"
                class="p-1.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs transition-colors"
                :title="t('creativeStudio.deleteAsset')"
              >
                🗑️
              </button>
            </div>
          </div>

          <!-- Preview Display: AI Image or SVG Graphic -->
          <div v-if="selectedAsset.file_type === 'image' || selectedAsset.file_type === 'graphic_card'" class="space-y-4">
            <!-- Mode Indicator Badge -->
            <div class="flex items-center justify-between">
              <div 
                v-if="selectedAsset.metadata?.mode === 'ai_generated'"
                class="px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[11px] font-bold flex items-center gap-1.5"
              >
                <span>✨</span>
                <span>{{ currentLocale === 'ar' ? 'صورة حقيقية مولدة بالذكاء الاصطناعي (Imagen AI)' : 'AI-Generated Image (Imagen)' }}</span>
              </div>
              <div 
                v-else
                class="px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-[11px] font-bold flex items-center gap-1.5"
              >
                <span>⚠️</span>
                <span>{{ currentLocale === 'ar' ? 'قالب بديل عالي التباين (Fallback Mode)' : 'High-Contrast Fallback Template' }}</span>
              </div>

              <span class="text-[11px] text-slate-400 font-mono">
                Ratio: {{ selectedAsset.aspect_ratio }} • {{ selectedAsset.visual_style }}
              </span>
            </div>

            <!-- Main Image Render Card -->
            <div class="p-4 rounded-3xl bg-slate-950/90 border border-slate-800/80 flex items-center justify-center overflow-hidden min-h-[360px]">
              <!-- Real AI Image -->
              <img 
                v-if="selectedAsset.public_url && (selectedAsset.file_type === 'image' || selectedAsset.mime_type === 'image/jpeg' || selectedAsset.mime_type === 'image/png')"
                :src="selectedAsset.public_url" 
                :alt="selectedAsset.title"
                class="max-w-xl max-h-[520px] w-auto h-auto rounded-2xl shadow-2xl object-contain border border-slate-800"
              />
              <!-- SVG Fallback Render -->
              <div 
                v-else-if="selectedAsset.metadata?.svg_markup"
                v-html="selectedAsset.metadata.svg_markup" 
                class="max-w-md w-full shadow-2xl rounded-2xl overflow-hidden"
              ></div>
            </div>

            <!-- Visual Brief / AI Prompt Explanation -->
            <div v-if="selectedAsset.prompt_used" class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/60 text-xs space-y-1.5">
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-400">{{ currentLocale === 'ar' ? 'برومبت التوليد والهوية المدمجة:' : 'Visual Synthesis & Identity Directives:' }}</span>
                <span v-if="selectedAsset.metadata?.latency_ms" class="text-[10px] text-slate-500 font-mono">
                  Latency: {{ selectedAsset.metadata.latency_ms }}ms
                </span>
              </div>
              <p class="text-slate-300 leading-relaxed whitespace-pre-line">{{ selectedAsset.prompt_used }}</p>
            </div>
          </div>

          <!-- Preview Display: Video Reel Script -->
          <div v-else-if="selectedAsset.file_type === 'video_script' && selectedAsset.metadata?.scenes" class="space-y-4">
            <div class="flex items-center justify-between p-3 rounded-xl bg-purple-950/30 border border-purple-500/20 text-xs text-purple-300">
              <span class="font-bold">🎬 {{ t('creativeStudio.reelScriptTitle') }}</span>
              <span>{{ selectedAsset.metadata.scenes.length }} {{ t('creativeStudio.scenesCount') }} • ~{{ selectedAsset.metadata.target_duration_seconds || 35 }}s</span>
            </div>

            <!-- Scenes Cards -->
            <div class="space-y-3">
              <div 
                v-for="scene in selectedAsset.metadata.scenes" 
                :key="scene.scene_number"
                class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800/80 space-y-2.5"
              >
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-emerald-400">
                    Scene {{ scene.scene_number }}: {{ scene.role }}
                  </span>
                  <span class="px-2 py-0.5 rounded-full bg-slate-800 text-[10px] font-mono text-slate-300">
                    ⏱️ {{ scene.timecode }}
                  </span>
                </div>

                <!-- Spoken Dialogue -->
                <div class="space-y-1 text-xs">
                  <span class="text-slate-400 font-semibold text-[11px]">{{ t('creativeStudio.spokenAudio') }}:</span>
                  <p class="text-white font-medium bg-slate-900/60 p-2.5 rounded-xl border border-slate-800/60 leading-relaxed">
                    "{{ scene.spoken_audio }}"
                  </p>
                </div>

                <!-- Directions & Sound Effect -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                  <div class="p-2 rounded-xl bg-slate-900/40 border border-slate-800/40">
                    <span class="text-slate-400 font-bold">📹 {{ t('creativeStudio.cameraDirection') }}:</span>
                    <p class="text-slate-300 mt-0.5">{{ scene.camera_direction }}</p>
                  </div>
                  <div class="p-2 rounded-xl bg-slate-900/40 border border-slate-800/40">
                    <span class="text-amber-400 font-bold">🎵 {{ t('creativeStudio.soundEffect') }}:</span>
                    <p class="text-slate-300 mt-0.5">{{ scene.sound_effect }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <div v-else class="p-12 rounded-3xl bg-slate-900/40 border border-slate-800/60 text-center space-y-2">
          <p class="text-xs text-slate-500">Select a media asset on the left to preview vector graphics or video scripts.</p>
        </div>
      </div>
    </div>

    <!-- Visual Card Generator Modal -->
    <div v-if="showVisualWizard" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="w-full max-w-xl bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-6 shadow-2xl overflow-y-auto max-h-[90vh]">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div class="flex items-center gap-2.5">
            <span class="text-xl">✨</span>
            <h3 class="text-base font-bold text-white">{{ t('creativeStudio.wizard.title') }}</h3>
          </div>
          <button @click="showVisualWizard = false" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form @submit.prevent="handleGenerateVisual" class="space-y-4 text-xs">
          <!-- Optional Post Grounding -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-300">{{ t('creativeStudio.wizard.selectPost') }}</label>
            <select 
              v-model="visualForm.content_post_id"
              @change="onPostSelected"
              class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-emerald-500/50"
            >
              <option :value="null">-- Standalone Graphic (No Post) --</option>
              <option v-for="post in existingPosts" :key="post.id" :value="post.id">
                #{{ post.id }} • {{ post.title }} ({{ post.primary_platform }})
              </option>
            </select>
          </div>

          <!-- Headline & Hook -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-300">{{ t('creativeStudio.wizard.customTitle') }}</label>
            <input 
              v-model="visualForm.title" 
              type="text"
              placeholder="e.g. 3 Growth Strategies for B2B SaaS"
              class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-emerald-500/50"
            />
          </div>

          <div class="space-y-1.5">
            <label class="font-bold text-slate-300">{{ t('creativeStudio.wizard.hookText') }}</label>
            <textarea 
              v-model="visualForm.hook" 
              rows="3"
              placeholder="e.g. Stop relying on manual drafting — build systematic marketing workflows that compound."
              class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-slate-200 focus:outline-none focus:border-emerald-500/50 resize-none font-sans"
            ></textarea>
          </div>

          <!-- Aspect Ratio & Visual Style -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="font-bold text-slate-300">{{ t('creativeStudio.wizard.selectAspect') }}</label>
              <select 
                v-model="visualForm.aspect_ratio"
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-emerald-500/50"
              >
                <option value="1:1">Square 1:1 (1080 × 1080px)</option>
                <option value="4:5">Instagram Portrait 4:5 (1080 × 1350px)</option>
                <option value="9:16">Story / Reel 9:16 (1080 × 1920px)</option>
                <option value="16:9">Twitter / LinkedIn Banner 16:9 (1200 × 675px)</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label class="font-bold text-slate-300">{{ t('creativeStudio.wizard.selectStyle') }}</label>
              <select 
                v-model="visualForm.visual_style"
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-emerald-500/50"
              >
                <option value="product_showcase">🛍️ Product Showcase (Commercial Studio)</option>
                <option value="lifestyle_scene">☕ Lifestyle & Audience Environment</option>
                <option value="promotional_banner">🏷️ Bold Promotional Campaign Banner</option>
                <option value="quote_card">💬 Editorial Quote / Authority Framework Card</option>
                <option value="infographic_style">📊 Modern Glassmorphism Infographic</option>
              </select>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
            <button 
              type="button" 
              @click="showVisualWizard = false"
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
              <span v-else>🎨</span>
              {{ generating ? t('common.processing') : t('creativeStudio.wizard.generateAction') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Video Reel Generator Modal -->
    <div v-if="showReelWizard" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="w-full max-w-xl bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-6 shadow-2xl overflow-y-auto max-h-[90vh]">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div class="flex items-center gap-2.5">
            <span class="text-xl">🎬</span>
            <h3 class="text-base font-bold text-white">{{ t('creativeStudio.wizard.reelWizardTitle') }}</h3>
          </div>
          <button @click="showReelWizard = false" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form @submit.prevent="handleGenerateReel" class="space-y-4 text-xs">
          <!-- Optional Post Grounding -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-300">{{ t('creativeStudio.wizard.selectPost') }}</label>
            <select 
              v-model="reelForm.content_post_id"
              class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-emerald-500/50"
            >
              <option :value="null">-- Standalone Reel Script --</option>
              <option v-for="post in existingPosts" :key="post.id" :value="post.id">
                #{{ post.id }} • {{ post.title }}
              </option>
            </select>
          </div>

          <!-- Dialect & Tone -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-300">{{ t('creativeStudio.wizard.dialect') }}</label>
            <select 
              v-model="reelForm.dialect"
              class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-emerald-500/50"
            >
              <option value="saudi">Saudi / Gulf Dialect (اللهجة السعودية والخليجية)</option>
              <option value="msa">Modern Standard Arabic (الفصحى المعاصرة)</option>
              <option value="egyptian">Egyptian Dialect (اللهجة المصرية)</option>
              <option value="uae">UAE Business (أسلوب الأعمال الإماراتي)</option>
              <option value="english">English (US/UK Short-Form Viral)</option>
            </select>
          </div>

          <!-- Custom Directions -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-300">Custom Angles / Notes (Optional)</label>
            <textarea 
              v-model="reelForm.prompt" 
              rows="3"
              placeholder="e.g. Focus on fast pacing, emphasize the 14-day free trial offer, and keep hook under 3 seconds."
              class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-slate-200 focus:outline-none focus:border-emerald-500/50 resize-none font-sans"
            ></textarea>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
            <button 
              type="button" 
              @click="showReelWizard = false"
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
              <span v-else>🎬</span>
              {{ generating ? t('common.processing') : t('creativeStudio.generateReelBtn') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Attach to Post Modal -->
    <div v-if="showAttachModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-5 shadow-2xl">
        <h3 class="text-base font-bold text-white flex items-center gap-2">
          <span>📎</span> {{ t('creativeStudio.attachToPost') }}
        </h3>

        <div class="space-y-2 text-xs">
          <label class="text-slate-300 font-bold">Select Target Content Post:</label>
          <select 
            v-model="selectedPostToAttach"
            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-emerald-500/50"
          >
            <option v-for="post in existingPosts" :key="post.id" :value="post.id">
              #{{ post.id }} • {{ post.title }} ({{ post.primary_platform }})
            </option>
          </select>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button @click="showAttachModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs">{{ t('common.cancel') }}</button>
          <button @click="confirmAttach" class="tactile-btn tactile-btn-primary text-xs px-5 py-2">{{ t('common.save') }}</button>
        </div>
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
const assets = ref<any[]>([]);
const selectedAsset = ref<any | null>(null);
const currentFilter = ref<string>('all');
const searchQuery = ref<string>('');
const existingPosts = ref<any[]>([]);

const showVisualWizard = ref(false);
const showReelWizard = ref(false);
const showAttachModal = ref(false);
const assetToAttach = ref<any | null>(null);
const selectedPostToAttach = ref<number | null>(null);

const visualForm = ref({
  content_post_id: null as number | null,
  title: '',
  hook: '',
  aspect_ratio: '1:1',
  visual_style: 'branded_quote',
});

const reelForm = ref({
  content_post_id: null as number | null,
  dialect: 'saudi',
  prompt: '',
});

const aspectFilters = computed(() => [
  { key: 'all', label: t('creativeStudio.filterAll'), icon: '🖼️' },
  { key: '1:1', label: t('creativeStudio.filterSquare'), icon: '⏹️' },
  { key: '4:5', label: t('creativeStudio.filterPortrait'), icon: '📱' },
  { key: '9:16', label: t('creativeStudio.filterStory'), icon: '📲' },
  { key: '16:9', label: t('creativeStudio.filterLandscape'), icon: '🖥️' },
  { key: 'video_script', label: t('creativeStudio.filterReels'), icon: '🎬' },
]);

function getAuthHeaders() {
  return {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${props.authToken}`,
    'X-Organization-Id': String(props.organizationId || ''),
    ...(props.brandId ? { 'X-Brand-Id': String(props.brandId) } : {}),
  };
}

async function fetchAssets() {
  if (!props.authToken) return;
  loading.value = true;

  try {
    const params = new URLSearchParams();
    if (currentFilter.value === 'video_script') {
      params.append('file_type', 'video_script');
    } else if (currentFilter.value !== 'all') {
      params.append('aspect_ratio', currentFilter.value);
    }
    if (searchQuery.value) {
      params.append('search', searchQuery.value);
    }

    const res = await fetch(`/api/v1/creative/assets?${params.toString()}`, {
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      const json = await res.json();
      assets.value = json.data || [];
      if (assets.value.length > 0 && !selectedAsset.value) {
        selectAsset(assets.value[0]);
      }
    }
  } catch (err) {
    console.error('Failed to load creative assets', err);
  } finally {
    loading.value = false;
  }
}

async function fetchContentPosts() {
  if (!props.authToken) return;
  try {
    const res = await fetch('/api/v1/content', { headers: getAuthHeaders() });
    if (res.ok) {
      const json = await res.json();
      existingPosts.value = json.data || [];
    }
  } catch (err) {
    console.error('Failed to load posts', err);
  }
}

function selectAsset(asset: any) {
  selectedAsset.value = asset;
}

function getAssetIcon(asset: any) {
  if (asset.file_type === 'video_script') return '🎬';
  if (asset.aspect_ratio === '9:16') return '📲';
  if (asset.aspect_ratio === '16:9') return '🖥️';
  return '🖼️';
}

function getFilterCount(key: string) {
  if (key === 'all') return assets.value.length;
  if (key === 'video_script') return assets.value.filter(a => a.file_type === 'video_script').length;
  return assets.value.filter(a => a.aspect_ratio === key).length;
}

function formatDate(dateStr?: string) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString(currentLocale.value === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'short',
    day: 'numeric',
  });
}

function openVisualWizard() {
  showVisualWizard.value = true;
  fetchContentPosts();
}

function openReelWizard() {
  showReelWizard.value = true;
  fetchContentPosts();
}

function onPostSelected() {
  if (visualForm.value.content_post_id) {
    const post = existingPosts.value.find(p => p.id === visualForm.value.content_post_id);
    if (post) {
      visualForm.value.title = post.title;
      visualForm.value.hook = post.hook || post.caption?.substring(0, 90) || '';
    }
  }
}

async function handleGenerateVisual() {
  if (!props.authToken) return;
  generating.value = true;

  try {
    const res = await fetch('/api/v1/creative/generate', {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({
        content_post_id: visualForm.value.content_post_id,
        title: visualForm.value.title || undefined,
        hook: visualForm.value.hook || undefined,
        aspect_ratio: visualForm.value.aspect_ratio,
        visual_style: visualForm.value.visual_style,
      }),
    });

    if (res.ok) {
      const json = await res.json();
      showVisualWizard.value = false;
      await fetchAssets();
      if (json.data) {
        selectAsset(json.data);
      }
    } else {
      const err = await res.json();
      alert(err.message || 'Generation failed');
    }
  } catch (err) {
    console.error('Visual generation error', err);
  } finally {
    generating.value = false;
  }
}

async function handleRegenerateVariation(asset: any) {
  if (!props.authToken || !asset) return;
  generating.value = true;

  try {
    const res = await fetch('/api/v1/creative/generate', {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({
        content_post_id: asset.content_post_id,
        title: asset.title,
        hook: asset.text_overlay,
        aspect_ratio: asset.aspect_ratio,
        visual_style: asset.visual_style || 'product_showcase',
        is_regeneration: true,
        avoid_prompt: asset.prompt_used ? asset.prompt_used.substring(0, 1000) : undefined,
      }),
    });

    if (res.ok) {
      const json = await res.json();
      await fetchAssets();
      if (json.data) {
        selectAsset(json.data);
      }
      alert(currentLocale.value === 'ar' ? 'تم توليد نسخة إبداعية جديدة بنجاح!' : 'New creative variation generated successfully!');
    } else {
      const err = await res.json();
      alert(err.message || 'Regeneration failed');
    }
  } catch (err) {
    console.error('Regeneration error', err);
  } finally {
    generating.value = false;
  }
}

async function handleGenerateReel() {
  if (!props.authToken) return;
  generating.value = true;

  try {
    const res = await fetch('/api/v1/creative/generate-reel', {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({
        content_post_id: reelForm.value.content_post_id,
        dialect: reelForm.value.dialect,
        prompt: reelForm.value.prompt || undefined,
      }),
    });

    if (res.ok) {
      const json = await res.json();
      showReelWizard.value = false;
      await fetchAssets();
      if (json.data) {
        selectAsset(json.data);
      }
    } else {
      const err = await res.json();
      alert(err.message || 'Reel generation failed');
    }
  } catch (err) {
    console.error('Reel generation error', err);
  } finally {
    generating.value = false;
  }
}

function openAttachModal(asset: any) {
  assetToAttach.value = asset;
  selectedPostToAttach.value = asset.content_post_id || (existingPosts.value[0]?.id ?? null);
  showAttachModal.value = true;
  fetchContentPosts();
}

async function confirmAttach() {
  if (!assetToAttach.value || !selectedPostToAttach.value || !props.authToken) return;

  try {
    const res = await fetch(`/api/v1/creative/assets/${assetToAttach.value.id}/attach`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify({ content_post_id: selectedPostToAttach.value }),
    });

    if (res.ok) {
      const json = await res.json();
      showAttachModal.value = false;
      selectAsset(json.data);
      await fetchAssets();
    }
  } catch (err) {
    console.error('Failed to attach asset', err);
  }
}

function downloadSvg(asset: any) {
  if (!asset.metadata?.svg_markup) return;
  const blob = new Blob([asset.metadata.svg_markup], { type: 'image/svg+xml' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = asset.file_name || 'marketly_creative.svg';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

async function deleteAsset(assetId: number) {
  if (!props.authToken || !confirm('Are you sure you want to delete this media asset?')) return;
  actionLoading.value = true;

  try {
    const res = await fetch(`/api/v1/creative/assets/${assetId}`, {
      method: 'DELETE',
      headers: getAuthHeaders(),
    });

    if (res.ok) {
      selectedAsset.value = null;
      await fetchAssets();
    }
  } catch (err) {
    console.error('Failed to delete asset', err);
  } finally {
    actionLoading.value = false;
  }
}

let searchTimeout: any = null;
function debounceSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchAssets();
  }, 350);
}

watch(() => props.brandId, () => {
  selectedAsset.value = null;
  fetchAssets();
  fetchContentPosts();
});

onMounted(() => {
  fetchAssets();
});
</script>
