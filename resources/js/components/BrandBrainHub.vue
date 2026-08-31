<template>
  <div class="space-y-6">
    <!-- Brand Brain Header Banner -->
    <div class="p-6 md:p-8 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800 pb-6">
        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-xl font-extrabold text-white flex items-center gap-2.5">
              <span>🧠</span>
              <span>{{ t('brandBrain.title') }}</span>
            </h2>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
              {{ completeness.status }}
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-1 max-w-2xl leading-relaxed">
            {{ t('brandBrain.subtitle') }}
          </p>
        </div>

        <!-- Completeness Health Meter -->
        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 min-w-[220px]">
          <div class="flex items-center justify-between text-xs mb-2">
            <span class="font-bold text-slate-300">{{ t('brandBrain.completeness') }}</span>
            <span class="font-extrabold font-mono text-emerald-400">{{ completeness.total_score }}%</span>
          </div>
          <div class="w-full h-2.5 rounded-full bg-slate-800 overflow-hidden">
            <div 
              class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-500 rounded-full" 
              :style="{ width: `${completeness.total_score}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Pillar Breakdown Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
        <div 
          v-for="(pillar, key) in completeness.pillars" 
          :key="key"
          class="p-3 rounded-xl border bg-slate-950/40 text-xs space-y-1.5"
          :class="pillar.is_complete ? 'border-emerald-500/30' : 'border-slate-800'"
        >
          <div class="flex items-center justify-between text-[11px]">
            <span class="text-slate-400 truncate">{{ pillar.name }}</span>
            <span :class="pillar.is_complete ? 'text-emerald-400' : 'text-slate-500'">
              {{ pillar.is_complete ? '✓' : '⚠' }}
            </span>
          </div>
          <div class="text-sm font-extrabold font-mono text-white">
            {{ pillar.score }} <span class="text-[10px] text-slate-500 font-normal">/ {{ pillar.max }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabbed Navigation -->
    <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 pb-2">
      <button 
        v-for="tab in tabs" 
        :key="tab.id"
        @click="activeTab = tab.id"
        :class="[
          activeTab === tab.id 
            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/40 font-bold' 
            : 'text-slate-400 hover:text-slate-200 border-transparent',
          'px-4 py-2 rounded-xl text-xs border transition-colors duration-150 flex items-center gap-2'
        ]"
      >
        <span>{{ tab.icon }}</span>
        <span>{{ t(tab.titleKey) }}</span>
      </button>
    </div>

    <!-- Tab 1: Profile & Identity -->
    <div v-if="activeTab === 'identity'" class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
      <h3 class="text-sm font-bold text-white flex items-center gap-2">
        <span>🏢</span>
        <span>{{ t('brandBrain.tabs.identity') }}</span>
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.businessName') }}</label>
          <input v-model="profileForm.business_name" type="text" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="Acme Inc." />
        </div>
        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.industry') }}</label>
          <input v-model="profileForm.industry" type="text" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="Technology / SaaS" />
        </div>
        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.businessType') }}</label>
          <select v-model="profileForm.business_type" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
            <option value="B2B">B2B (Business to Business)</option>
            <option value="B2C">B2C (Business to Consumer)</option>
            <option value="D2C">D2C (Direct to Consumer)</option>
            <option value="Agency">Agency / Service</option>
          </select>
        </div>
        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.website') }}</label>
          <input v-model="profileForm.website" type="text" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="https://example.com" />
        </div>
        <div class="space-y-1.5 md:col-span-2">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.tagline') }}</label>
          <input v-model="profileForm.tagline" type="text" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="Autonomous Marketing for High-Growth Brands" />
        </div>
        <div class="space-y-1.5 md:col-span-2">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.positioning') }}</label>
          <textarea v-model="profileForm.positioning" rows="2" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="Core market positioning and brand pillars..."></textarea>
        </div>
        <div class="space-y-1.5 md:col-span-2">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.description') }}</label>
          <textarea v-model="profileForm.description" rows="3" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" placeholder="Detailed business background and service offering overview..."></textarea>
        </div>
      </div>

      <div class="flex justify-end pt-2">
        <button @click="saveProfile" :disabled="saving" class="tactile-btn tactile-btn-primary text-xs px-5 py-2.5">
          {{ saving ? t('common.processing') : t('brandBrain.buttons.saveProfile') }}
        </button>
      </div>
    </div>

    <!-- Tab 2: Products & Services -->
    <div v-if="activeTab === 'products'" class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
      <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <div>
          <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <span>📦</span>
            <span>{{ t('brandBrain.tabs.products') }}</span>
          </h3>
          <p class="text-xs text-slate-400 mt-0.5">Manage your offerings and pricing so AI creates accurate content.</p>
        </div>
        <button @click="openNewProductModal" class="tactile-btn tactile-btn-primary text-xs px-4 py-2 flex items-center gap-1.5">
          <span>➕</span>
          <span>{{ t('brandBrain.buttons.addProduct') }}</span>
        </button>
      </div>

      <!-- Empty State -->
      <div v-if="products.length === 0" class="p-12 rounded-2xl bg-slate-950/40 border border-dashed border-slate-800 text-center space-y-3">
        <div class="text-4xl">📦</div>
        <h4 class="text-sm font-bold text-white">No Products or Services Added</h4>
        <p class="text-xs text-slate-400 max-w-sm mx-auto">Add your products and services to empower the AI engine with accurate offering details.</p>
        <button @click="openNewProductModal" class="tactile-btn tactile-btn-primary text-xs px-4 py-2">
          + Add First Product
        </button>
      </div>

      <!-- Products Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="prod in products" :key="prod.id" class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 flex items-start justify-between gap-4 hover:border-emerald-500/30 transition-all">
          <div class="space-y-1.5 flex-1">
            <div class="flex items-center gap-2">
              <h4 class="font-bold text-xs text-white">{{ prod.name }}</h4>
              <span class="px-2 py-0.5 text-[9px] font-bold rounded uppercase" :class="prod.type === 'service' ? 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'">
                {{ prod.type }}
              </span>
              <span v-if="prod.category" class="text-[10px] text-slate-500 font-mono">• {{ prod.category }}</span>
            </div>
            <p class="text-[11px] text-slate-400 leading-relaxed">{{ prod.description || 'No description provided.' }}</p>
            <div v-if="prod.price" class="mt-2 text-xs font-mono text-emerald-400 font-bold">
              {{ prod.price }} {{ prod.currency || 'SAR' }}
            </div>
          </div>
          <button @click="deleteProduct(prod.id)" class="text-slate-500 hover:text-red-400 text-xs p-1 rounded-lg hover:bg-slate-900 transition-colors" title="Delete">
            🗑️
          </button>
        </div>
      </div>
    </div>

    <!-- Tab 3: Target Audience -->
    <div v-if="activeTab === 'audience'" class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
      <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <div>
          <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <span>🎯</span>
            <span>{{ t('brandBrain.tabs.audience') }}</span>
          </h3>
          <p class="text-xs text-slate-400 mt-0.5">Define your ideal customer personas for targeted copy and hooks.</p>
        </div>
        <button @click="openNewAudienceModal" class="tactile-btn tactile-btn-primary text-xs px-4 py-2 flex items-center gap-1.5">
          <span>➕</span>
          <span>{{ t('brandBrain.buttons.addAudience') }}</span>
        </button>
      </div>

      <!-- Empty State -->
      <div v-if="audiences.length === 0" class="p-12 rounded-2xl bg-slate-950/40 border border-dashed border-slate-800 text-center space-y-3">
        <div class="text-4xl">🎯</div>
        <h4 class="text-sm font-bold text-white">No Target Audiences Defined</h4>
        <p class="text-xs text-slate-400 max-w-sm mx-auto">Define customer personas so the AI generates tailored hooks, pain points, and CTAs.</p>
        <button @click="openNewAudienceModal" class="tactile-btn tactile-btn-primary text-xs px-4 py-2">
          + Add First Audience Persona
        </button>
      </div>

      <!-- Audiences Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="aud in audiences" :key="aud.id" class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 flex items-start justify-between gap-4 hover:border-emerald-500/30 transition-all">
          <div class="space-y-1.5 flex-1">
            <div class="flex items-center gap-2">
              <h4 class="font-bold text-xs text-white">{{ aud.name }}</h4>
              <span class="px-2 py-0.5 text-[9px] font-bold rounded uppercase bg-purple-500/10 text-purple-300 border border-purple-500/20">
                {{ aud.type }}
              </span>
              <span v-if="aud.age_range" class="text-[10px] text-slate-500 font-mono">• Age: {{ aud.age_range }}</span>
            </div>
            <p class="text-[11px] text-slate-400 leading-relaxed">{{ aud.description || 'No description provided.' }}</p>
            <div v-if="aud.pain_points && aud.pain_points.length" class="flex flex-wrap gap-1 mt-2">
              <span v-for="(p, i) in aud.pain_points" :key="i" class="px-2 py-0.5 rounded-md bg-slate-900 border border-slate-800 text-[10px] text-amber-300">
                ⚡ {{ p }}
              </span>
            </div>
          </div>
          <button @click="deleteAudience(aud.id)" class="text-slate-500 hover:text-red-400 text-xs p-1 rounded-lg hover:bg-slate-900 transition-colors" title="Delete">
            🗑️
          </button>
        </div>
      </div>
    </div>

    <!-- Tab 4: Brand Voice & Tone -->
    <div v-if="activeTab === 'voice'" class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-6">
      <h3 class="text-sm font-bold text-white flex items-center gap-2">
        <span>🎙️</span>
        <span>{{ t('brandBrain.tabs.voice') }}</span>
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.dialect') }}</label>
          <select v-model="voiceForm.dialect" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
            <option value="saudi">Saudi Arabic (اللهجة السعودية الحجازية / النجدية)</option>
            <option value="gulf">Gulf Arabic (اللهجة الخليجية العامة)</option>
            <option value="egyptian">Egyptian Arabic (اللهجة المصرية)</option>
            <option value="msa">Modern Standard Arabic (الفصحى المعاصرة)</option>
            <option value="english_us">American English (US Professional)</option>
            <option value="english_uk">British English (UK Refined)</option>
          </select>
        </div>

        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.emojiStyle') }}</label>
          <select v-model="voiceForm.emoji_style" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
            <option value="none">None (No Emojis)</option>
            <option value="minimal">Minimal (1-2 per post)</option>
            <option value="moderate">Moderate (Balanced)</option>
            <option value="expressive">Expressive (High engagement)</option>
          </select>
        </div>

        <div class="space-y-1.5">
          <label class="text-[11px] font-medium text-slate-400">{{ t('brandBrain.fields.formality') }}: {{ voiceForm.formality_scale }} / 5</label>
          <input v-model.number="voiceForm.formality_scale" type="range" min="1" max="5" class="w-full accent-emerald-400" />
        </div>
      </div>

      <div class="flex justify-end pt-2">
        <button @click="saveVoice" :disabled="saving" class="tactile-btn tactile-btn-primary text-xs px-5 py-2.5">
          {{ saving ? t('common.processing') : t('brandBrain.buttons.saveVoice') }}
        </button>
      </div>
    </div>

    <!-- Tab 5: AI Context Inspector -->
    <div v-if="activeTab === 'aiContext'" class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-xl space-y-4">
      <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <div>
          <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <span>🛡️</span>
            <span>{{ t('brandBrain.aiContextBadge') }}</span>
          </h3>
          <p class="text-xs text-slate-400 mt-1">{{ t('brandBrain.aiContextDesc') }}</p>
        </div>
        <button @click="fetchAiContext" class="tactile-btn tactile-btn-primary text-xs px-3 py-1.5">
          {{ t('brandBrain.buttons.previewAiContext') }}
        </button>
      </div>

      <pre class="w-full h-80 p-4 rounded-2xl bg-slate-950 border border-slate-800 text-xs font-mono text-emerald-400 overflow-y-auto">{{ aiContextData || '// Click Preview to load sanitized AI context...' }}</pre>
    </div>

    <!-- Add Product Modal -->
    <div v-if="showProductModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-5 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <h3 class="text-base font-bold text-white flex items-center gap-2">
            <span>📦</span>
            <span>Add Product or Service</span>
          </h3>
          <button @click="showProductModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>

        <form @submit.prevent="createProduct" class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-xs text-slate-300 font-semibold">Name / Title *</label>
              <input v-model="productForm.name" type="text" required placeholder="e.g. Cloud Marketing Suite" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none" />
            </div>
            <div class="space-y-1">
              <label class="text-xs text-slate-300 font-semibold">Type *</label>
              <select v-model="productForm.type" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-emerald-500 outline-none">
                <option value="product">Product (منتج)</option>
                <option value="service">Service (خدمة)</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div class="space-y-1">
              <label class="text-xs text-slate-300 font-semibold">Category</label>
              <input v-model="productForm.category" type="text" placeholder="e.g. SaaS, Software" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none" />
            </div>
            <div class="space-y-1">
              <label class="text-xs text-slate-300 font-semibold">Price</label>
              <input v-model.number="productForm.price" type="number" step="any" placeholder="299" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none" />
            </div>
            <div class="space-y-1">
              <label class="text-xs text-slate-300 font-semibold">Currency</label>
              <select v-model="productForm.currency" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-emerald-500 outline-none">
                <option value="SAR">SAR (ريال)</option>
                <option value="USD">USD ($)</option>
                <option value="AED">AED (درهم)</option>
                <option value="EGP">EGP (جنيه)</option>
              </select>
            </div>
          </div>

          <div class="space-y-1">
            <label class="text-xs text-slate-300 font-semibold">Description</label>
            <textarea v-model="productForm.description" rows="2" placeholder="Brief overview of product features and value proposition..." class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none"></textarea>
          </div>

          <div class="space-y-1">
            <label class="text-xs text-slate-300 font-semibold">Key Features / Benefits (comma separated)</label>
            <input v-model="productForm.features_text" type="text" placeholder="e.g. AI Content, Multi-Channel, 24/7 Autopilot" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none" />
          </div>

          <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
            <button type="button" @click="showProductModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-700">
              {{ t('common.cancel') }}
            </button>
            <button type="submit" :disabled="modalSaving" class="tactile-btn tactile-btn-primary px-5 py-2 text-xs font-bold">
              <span v-if="modalSaving" class="animate-spin">⏳</span>
              <span v-else>💾 Save Product</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Audience Persona Modal -->
    <div v-if="showAudienceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-5 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <h3 class="text-base font-bold text-white flex items-center gap-2">
            <span>🎯</span>
            <span>Add Target Audience Persona</span>
          </h3>
          <button @click="showAudienceModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>

        <form @submit.prevent="createAudience" class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-xs text-slate-300 font-semibold">Persona Name *</label>
              <input v-model="audienceForm.name" type="text" required placeholder="e.g. Tech Founders & CMOs" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none" />
            </div>
            <div class="space-y-1">
              <label class="text-xs text-slate-300 font-semibold">Audience Type *</label>
              <select v-model="audienceForm.type" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-emerald-500 outline-none">
                <option value="b2b">B2B (Business / Decision Makers)</option>
                <option value="b2c">B2C (Individual Consumers)</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-xs text-slate-300 font-semibold">Age Range</label>
              <input v-model="audienceForm.age_range" type="text" placeholder="e.g. 25-45" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none" />
            </div>
            <div class="space-y-1">
              <label class="text-xs text-slate-300 font-semibold">Gender</label>
              <select v-model="audienceForm.gender" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-emerald-500 outline-none">
                <option value="all">All (الجميع)</option>
                <option value="male">Male (رجال)</option>
                <option value="female">Female (نساء)</option>
              </select>
            </div>
          </div>

          <div class="space-y-1">
            <label class="text-xs text-slate-300 font-semibold">Key Pain Points (comma separated)</label>
            <input v-model="audienceForm.pain_points_text" type="text" placeholder="e.g. Slow content execution, High agency retainer, Inconsistent branding" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none" />
          </div>

          <div class="space-y-1">
            <label class="text-xs text-slate-300 font-semibold">Description / Goals</label>
            <textarea v-model="audienceForm.description" rows="2" placeholder="Summary of this audience segment, their aspirations, and priorities..." class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:border-emerald-500 outline-none"></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
            <button type="button" @click="showAudienceModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-700">
              {{ t('common.cancel') }}
            </button>
            <button type="submit" :disabled="modalSaving" class="tactile-btn tactile-btn-primary px-5 py-2 text-xs font-bold">
              <span v-if="modalSaving" class="animate-spin">⏳</span>
              <span v-else>🎯 Save Audience</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { t, currentLocale } from '../i18n';

const props = defineProps<{
  authToken: string;
  organizationId?: number;
}>();

const activeTab = ref('identity');
const saving = ref(false);
const modalSaving = ref(false);
const completeness = ref<any>({ total_score: 0, status: 'empty', pillars: {} });
const products = ref<any[]>([]);
const audiences = ref<any[]>([]);
const aiContextData = ref<string>('');

const showProductModal = ref(false);
const showAudienceModal = ref(false);

const profileForm = ref({
  business_name: '',
  industry: 'Technology',
  business_type: 'B2B',
  description: '',
  website: '',
  tagline: '',
  positioning: '',
});

const voiceForm = ref({
  dialect: 'saudi',
  emoji_style: 'moderate',
  formality_scale: 3,
});

const productForm = ref({
  name: '',
  type: 'product',
  category: '',
  price: null as number | null,
  currency: 'SAR',
  description: '',
  features_text: '',
});

const audienceForm = ref({
  name: '',
  type: 'b2b',
  age_range: '',
  gender: 'all',
  description: '',
  pain_points_text: '',
});

const tabs = [
  { id: 'identity', titleKey: 'brandBrain.tabs.identity', icon: '🏢' },
  { id: 'products', titleKey: 'brandBrain.tabs.products', icon: '📦' },
  { id: 'audience', titleKey: 'brandBrain.tabs.audience', icon: '🎯' },
  { id: 'voice', titleKey: 'brandBrain.tabs.voice', icon: '🎙️' },
  { id: 'aiContext', titleKey: 'brandBrain.tabs.aiContext', icon: '🛡️' },
];

const getHeaders = () => ({
  Authorization: `Bearer ${props.authToken}`,
  'X-Locale': currentLocale.value,
  ...(props.organizationId ? { 'X-Organization-Id': String(props.organizationId) } : {}),
});

const openNewProductModal = () => {
  productForm.value = {
    name: '',
    type: 'product',
    category: '',
    price: null,
    currency: 'SAR',
    description: '',
    features_text: '',
  };
  showProductModal.value = true;
};

const openNewAudienceModal = () => {
  audienceForm.value = {
    name: '',
    type: 'b2b',
    age_range: '',
    gender: 'all',
    description: '',
    pain_points_text: '',
  };
  showAudienceModal.value = true;
};

const fetchBrandBrain = async () => {
  if (!props.authToken) return;
  try {
    const res = await axios.get('/api/v1/brand', { headers: getHeaders() });
    const data = res.data?.data;
    if (data) {
      completeness.value = data.completeness || { total_score: 0, status: 'empty', pillars: {} };
      if (data.profile) {
        profileForm.value = {
          business_name: data.profile.business_name || '',
          industry: data.profile.industry || 'Technology',
          business_type: data.profile.business_type || 'B2B',
          description: data.profile.description || '',
          website: data.profile.website || '',
          tagline: data.profile.tagline || '',
          positioning: data.profile.positioning || '',
        };
        products.value = data.profile.products_services || [];
        audiences.value = data.profile.audiences || [];
        if (data.profile.voice) {
          voiceForm.value = {
            dialect: data.profile.voice.dialect || 'saudi',
            emoji_style: data.profile.voice.emoji_style || 'moderate',
            formality_scale: data.profile.voice.formality_scale || 3,
          };
        }
      }
    }
  } catch (err) {
    console.error('Failed to load Brand Brain', err);
  }
};

const saveProfile = async () => {
  saving.value = true;
  try {
    await axios.post('/api/v1/brand', profileForm.value, { headers: getHeaders() });
    alert('Brand profile saved successfully.');
    await fetchBrandBrain();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to save brand profile.');
  } finally {
    saving.value = false;
  }
};

const saveVoice = async () => {
  saving.value = true;
  try {
    await axios.patch('/api/v1/brand/voice', voiceForm.value, { headers: getHeaders() });
    alert('Brand voice and tone saved.');
    await fetchBrandBrain();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to save voice.');
  } finally {
    saving.value = false;
  }
};

const createProduct = async () => {
  modalSaving.value = true;
  try {
    const features = productForm.value.features_text
      ? productForm.value.features_text.split(',').map(s => s.trim()).filter(Boolean)
      : [];

    await axios.post('/api/v1/brand/products', {
      name: productForm.value.name,
      type: productForm.value.type,
      category: productForm.value.category || null,
      price: productForm.value.price || null,
      currency: productForm.value.currency || 'SAR',
      description: productForm.value.description || null,
      features: features.length ? features : null,
    }, { headers: getHeaders() });

    showProductModal.value = false;
    await fetchBrandBrain();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to create product.');
  } finally {
    modalSaving.value = false;
  }
};

const createAudience = async () => {
  modalSaving.value = true;
  try {
    const painPoints = audienceForm.value.pain_points_text
      ? audienceForm.value.pain_points_text.split(',').map(s => s.trim()).filter(Boolean)
      : [];

    await axios.post('/api/v1/brand/audiences', {
      name: audienceForm.value.name,
      type: audienceForm.value.type,
      age_range: audienceForm.value.age_range || null,
      gender: audienceForm.value.gender || 'all',
      description: audienceForm.value.description || null,
      pain_points: painPoints.length ? painPoints : null,
    }, { headers: getHeaders() });

    showAudienceModal.value = false;
    await fetchBrandBrain();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to create audience persona.');
  } finally {
    modalSaving.value = false;
  }
};

const deleteProduct = async (id: number) => {
  if (!confirm('Are you sure you want to delete this product?')) return;
  try {
    await axios.delete(`/api/v1/brand/products/${id}`, { headers: getHeaders() });
    await fetchBrandBrain();
  } catch (err) {
    console.error('Failed to delete product', err);
  }
};

const deleteAudience = async (id: number) => {
  if (!confirm('Are you sure you want to delete this audience persona?')) return;
  try {
    await axios.delete(`/api/v1/brand/audiences/${id}`, { headers: getHeaders() });
    await fetchBrandBrain();
  } catch (err) {
    console.error('Failed to delete audience', err);
  }
};

const fetchAiContext = async () => {
  try {
    const res = await axios.get('/api/v1/brand/ai-context?task=content_generation', { headers: getHeaders() });
    aiContextData.value = JSON.stringify(res.data?.data, null, 2);
  } catch (err: any) {
    aiContextData.value = JSON.stringify(err.response?.data || { error: err.message }, null, 2);
  }
};

onMounted(() => {
  fetchBrandBrain();
});
</script>
