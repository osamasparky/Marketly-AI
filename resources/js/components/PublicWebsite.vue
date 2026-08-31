<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 font-sans selection:bg-emerald-500 selection:text-white">
    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-50 bg-slate-950/80 backdrop-blur-xl border-b border-slate-800/80">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3 cursor-pointer" @click="activeSection = 'hero'">
          <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-emerald-600 via-emerald-500 to-teal-400 p-0.5 shadow-lg shadow-emerald-500/20 flex items-center justify-center">
            <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center">
              <span class="text-emerald-400 font-black text-lg tracking-tighter">M</span>
            </div>
          </div>
          <div>
            <span class="font-extrabold text-white text-base tracking-tight">{{ t('common.appName') }}</span>
            <span class="text-[10px] text-emerald-400 font-mono block leading-none">AUTONOMOUS MARKETING</span>
          </div>
        </div>

        <!-- Desktop Navigation Links -->
        <nav class="hidden md:flex items-center gap-8 text-xs font-medium text-slate-300">
          <button @click="scrollTo('features')" class="hover:text-emerald-400 transition-colors">{{ t('navigation.features') }}</button>
          <button @click="scrollTo('how-it-works')" class="hover:text-emerald-400 transition-colors">{{ t('navigation.howItWorks') }}</button>
          <button @click="scrollTo('pricing')" class="hover:text-emerald-400 transition-colors">{{ t('navigation.pricing') }}</button>
          <button @click="scrollTo('faq')" class="hover:text-emerald-400 transition-colors">{{ t('navigation.faq') }}</button>
          <button @click="showContactModal = true" class="hover:text-emerald-400 transition-colors">{{ t('navigation.contact') }}</button>
        </nav>

        <!-- Right CTA Actions -->
        <div class="flex items-center gap-3">
          <!-- Language Toggle -->
          <button 
            @click="toggleLang" 
            class="px-2.5 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-[11px] font-bold text-slate-300 hover:text-emerald-400 hover:border-slate-700 transition-all flex items-center gap-1.5"
          >
            <span>🌐</span>
            <span>{{ currentLocale === 'ar' ? 'English' : 'العربية' }}</span>
          </button>

          <button 
            @click="$emit('open-auth', 'login')" 
            class="hidden sm:inline-flex px-3.5 py-1.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-white transition-colors"
          >
            {{ t('dashboard.loginMode') }}
          </button>

          <button 
            @click="$emit('open-auth', 'register')" 
            class="tactile-btn tactile-btn-primary text-xs px-4 py-2"
          >
            {{ t('landing.ctaPrimary') }}
          </button>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main>
      <!-- Hero Section -->
      <section class="relative pt-20 pb-24 overflow-hidden">
        <!-- Glow Orbs -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/3 right-10 w-72 h-72 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10 space-y-8">
          <div v-if="siteSettings.announcement_banner" class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold">
            <span>🚀</span>
            <span>{{ siteSettings.announcement_banner }}</span>
          </div>

          <h1 class="text-4xl sm:text-6xl font-black text-white tracking-tight leading-[1.15] max-w-4xl mx-auto">
            {{ heroTitle }}
          </h1>

          <p class="text-base sm:text-lg text-slate-400 max-w-2xl mx-auto leading-relaxed">
            {{ heroSubtitle }}
          </p>

          <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
            <button 
              @click="$emit('open-auth', 'register')" 
              class="tactile-btn tactile-btn-primary text-sm px-7 py-3 w-full sm:w-auto shadow-xl shadow-emerald-500/25"
            >
              {{ t('landing.ctaPrimary') }}
            </button>
            <button 
              @click="scrollTo('how-it-works')" 
              class="px-6 py-3 rounded-2xl bg-slate-900 border border-slate-800 text-sm font-semibold text-slate-300 hover:text-white hover:border-slate-700 transition-all w-full sm:w-auto flex items-center justify-center gap-2"
            >
              <span>{{ t('landing.ctaSecondary') }}</span>
              <span>↓</span>
            </button>
          </div>

          <!-- Proof Metrics / Visual Badges -->
          <div class="pt-10 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto">
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80">
              <div class="text-xl font-black text-emerald-400">100%</div>
              <div class="text-[11px] text-slate-400 mt-0.5">Brand Consistency</div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80">
              <div class="text-xl font-black text-emerald-400">10x</div>
              <div class="text-[11px] text-slate-400 mt-0.5">Faster Strategy Formulation</div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80">
              <div class="text-xl font-black text-emerald-400">EN & AR</div>
              <div class="text-[11px] text-slate-400 mt-0.5">Native Dialect Support</div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80">
              <div class="text-xl font-black text-emerald-400">Zero-Trust</div>
              <div class="text-[11px] text-slate-400 mt-0.5">Multi-Tenant Isolation</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Problem vs Solution Section -->
      <section class="py-20 bg-slate-900/40 border-y border-slate-800/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          <div class="text-center max-w-2xl mx-auto space-y-3">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">{{ t('landing.problemTitle') }}</h2>
            <p class="text-xs sm:text-sm text-slate-400">{{ t('landing.problemSubtitle') }}</p>
          </div>

          <div class="grid md:grid-cols-2 gap-8">
            <!-- Without Marketly AI -->
            <div class="p-6 sm:p-8 rounded-3xl bg-red-950/20 border border-red-500/20 space-y-4">
              <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-500/10 text-red-400 text-xs font-bold">
                <span>❌</span>
                <span>Generic AI & Manual Hassle</span>
              </div>
              <ul class="space-y-3 text-xs sm:text-sm text-slate-300">
                <li class="flex items-start gap-2.5">
                  <span class="text-red-400">✕</span>
                  <span>AI forgets your business voice and target audience in every new prompt.</span>
                </li>
                <li class="flex items-start gap-2.5">
                  <span class="text-red-400">✕</span>
                  <span>Random posts generated without cohesive strategy or conversion goals.</span>
                </li>
                <li class="flex items-start gap-2.5">
                  <span class="text-red-400">✕</span>
                  <span>High monthly agency retainers with slow turnaround and misaligned tone.</span>
                </li>
              </ul>
            </div>

            <!-- With Marketly AI -->
            <div class="p-6 sm:p-8 rounded-3xl bg-emerald-950/30 border border-emerald-500/30 space-y-4 shadow-xl shadow-emerald-500/10">
              <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-bold">
                <span>✨</span>
                <span>Marketly AI Autonomous Engine</span>
              </div>
              <ul class="space-y-3 text-xs sm:text-sm text-slate-300">
                <li class="flex items-start gap-2.5">
                  <span class="text-emerald-400">✓</span>
                  <span>Persistent **Brand Brain** that retains your identity, products, and forbidden phrases.</span>
                </li>
                <li class="flex items-start gap-2.5">
                  <span class="text-emerald-400">✓</span>
                  <span>Structured **AI Marketing Strategist** defining quarterly pillars, themes, and channels.</span>
                </li>
                <li class="flex items-start gap-2.5">
                  <span class="text-emerald-400">✓</span>
                  <span>Enterprise multi-tenancy with 14-day full trial and team collaboration.</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <!-- Autonomous Product Pipeline -->
      <section id="how-it-works" class="py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
          <div class="text-center max-w-2xl mx-auto space-y-3">
            <div class="text-xs font-bold text-emerald-400 uppercase tracking-wider">HOW IT WORKS</div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">{{ t('landing.flowTitle') }}</h2>
            <p class="text-xs sm:text-sm text-slate-400">{{ t('landing.flowSubtitle') }}</p>
          </div>

          <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Step 1 -->
            <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4 hover:border-emerald-500/40 transition-all group">
              <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-black text-sm flex items-center justify-center">01</div>
              <h3 class="text-base font-bold text-white group-hover:text-emerald-400 transition-colors">Build Brand Brain</h3>
              <p class="text-xs text-slate-400 leading-relaxed">Input your products, audience personas, localized voice, and goals into our persistent intelligence hub.</p>
              <div class="inline-flex items-center gap-1 text-[11px] text-emerald-400 font-semibold">Live in Phase 2 ✓</div>
            </div>

            <!-- Step 2 -->
            <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4 hover:border-emerald-500/40 transition-all group">
              <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-black text-sm flex items-center justify-center">02</div>
              <h3 class="text-base font-bold text-white group-hover:text-emerald-400 transition-colors">Synthesize AI Strategy</h3>
              <p class="text-xs text-slate-400 leading-relaxed">The AI Strategist calculates health scores, sets percentage content mix, and plans campaign themes with explainable rationales.</p>
              <div class="inline-flex items-center gap-1 text-[11px] text-emerald-400 font-semibold">Live in Phase 3 ✓</div>
            </div>

            <!-- Step 3 -->
            <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4 hover:border-emerald-500/40 transition-all group">
              <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-black text-sm flex items-center justify-center">03</div>
              <h3 class="text-base font-bold text-white group-hover:text-emerald-400 transition-colors">Generate Content</h3>
              <p class="text-xs text-slate-400 leading-relaxed">Turn strategic pillars into high-converting posts, carousels, and video scripts optimized for every platform.</p>
              <div class="inline-flex items-center gap-1 text-[11px] text-amber-400 font-semibold">Phase 4 (Coming Soon) ⏳</div>
            </div>

            <!-- Step 4 -->
            <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4 hover:border-emerald-500/40 transition-all group">
              <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-black text-sm flex items-center justify-center">04</div>
              <h3 class="text-base font-bold text-white group-hover:text-emerald-400 transition-colors">Social Auto-Publishing</h3>
              <p class="text-xs text-slate-400 leading-relaxed">Publish directly to LinkedIn, Instagram, X, and TikTok on your schedule with human approval gates.</p>
              <div class="inline-flex items-center gap-1 text-[11px] text-slate-500 font-semibold">Phase 5 (Roadmap)</div>
            </div>

            <!-- Step 5 -->
            <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4 hover:border-emerald-500/40 transition-all group">
              <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-black text-sm flex items-center justify-center">05</div>
              <h3 class="text-base font-bold text-white group-hover:text-emerald-400 transition-colors">ROI & Analytics Engine</h3>
              <p class="text-xs text-slate-400 leading-relaxed">Track engagement, audience conversion, and pillar performance with closed-loop feedback.</p>
              <div class="inline-flex items-center gap-1 text-[11px] text-slate-500 font-semibold">Phase 6 (Roadmap)</div>
            </div>

            <!-- Step 6 -->
            <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4 hover:border-emerald-500/40 transition-all group">
              <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-black text-sm flex items-center justify-center">06</div>
              <h3 class="text-base font-bold text-white group-hover:text-emerald-400 transition-colors">Team & Agency Workspaces</h3>
              <p class="text-xs text-slate-400 leading-relaxed">Invite team members with role-based permissions (Owner, Admin, Manager, Editor, Viewer).</p>
              <div class="inline-flex items-center gap-1 text-[11px] text-emerald-400 font-semibold">Live in Phase 1 ✓</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Data-Driven Pricing Section -->
      <section id="pricing" class="py-24 bg-slate-900/40 border-y border-slate-800/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          <div class="text-center max-w-2xl mx-auto space-y-4">
            <div class="text-xs font-bold text-emerald-400 uppercase tracking-wider">PLANS & PRICING</div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">{{ t('landing.pricingTitle') }}</h2>
            <p class="text-xs sm:text-sm text-slate-400">{{ t('landing.pricingSubtitle') }}</p>

            <!-- Billing Toggle -->
            <div class="inline-flex items-center p-1 rounded-2xl bg-slate-900 border border-slate-800">
              <button 
                @click="isAnnual = false" 
                :class="[!isAnnual ? 'bg-emerald-500 text-white shadow-md' : 'text-slate-400 hover:text-white']"
                class="px-4 py-1.5 rounded-xl text-xs font-semibold transition-all"
              >
                {{ t('landing.billingMonthly') }}
              </button>
              <button 
                @click="isAnnual = true" 
                :class="[isAnnual ? 'bg-emerald-500 text-white shadow-md' : 'text-slate-400 hover:text-white']"
                class="px-4 py-1.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5"
              >
                <span>{{ t('landing.billingAnnual') }}</span>
                <span class="px-1.5 py-0.5 rounded-md bg-emerald-400/20 text-emerald-300 text-[10px]">{{ t('landing.savePercent') }}</span>
              </button>
            </div>
          </div>

          <!-- Pricing Cards -->
          <div class="grid md:grid-cols-3 gap-8 items-stretch">
            <div 
              v-for="plan in plans" 
              :key="plan.id"
              :class="[plan.slug === 'growth' ? 'border-emerald-500 shadow-2xl shadow-emerald-500/10 ring-1 ring-emerald-500/50 bg-slate-900' : 'border-slate-800 bg-slate-900/60']"
              class="rounded-3xl border p-8 flex flex-col justify-between space-y-6 relative"
            >
              <div v-if="plan.slug === 'growth'" class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-emerald-500 text-white text-[10px] font-bold uppercase tracking-wider shadow-lg">
                Most Popular
              </div>

              <div class="space-y-4">
                <h3 class="text-xl font-bold text-white">{{ plan.name }}</h3>
                <p class="text-xs text-slate-400 leading-relaxed min-h-[36px]">{{ plan.description }}</p>

                <div class="pt-2 flex items-baseline gap-1">
                  <span class="text-3xl sm:text-4xl font-extrabold text-white">
                    {{ isAnnual ? Math.round(plan.price_annual / 12) : plan.price_monthly }}
                  </span>
                  <span class="text-xs text-slate-400 font-semibold">{{ plan.currency }} / mo</span>
                </div>

                <div class="pt-4 border-t border-slate-800 space-y-3">
                  <div class="text-[11px] font-bold text-slate-300 uppercase tracking-wider">Features Included:</div>
                  <ul class="space-y-2 text-xs text-slate-300">
                    <li v-for="ent in plan.entitlements" :key="ent.id" class="flex items-center gap-2">
                      <span :class="ent.is_enabled ? 'text-emerald-400' : 'text-slate-600'">{{ ent.is_enabled ? '✓' : '✕' }}</span>
                      <span :class="ent.is_enabled ? 'text-slate-300' : 'text-slate-600 line-through'">
                        {{ formatFeatureLabel(ent.feature_key, ent.limit_count) }}
                      </span>
                    </li>
                  </ul>
                </div>
              </div>

              <button 
                @click="$emit('open-auth', 'register', plan.slug)"
                :class="[plan.slug === 'growth' ? 'tactile-btn tactile-btn-primary' : 'bg-slate-800 hover:bg-slate-700 text-white rounded-2xl py-3 text-xs font-bold transition-all']"
                class="w-full text-center"
              >
                {{ t('common.startTrial') }}
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Trust & Security -->
      <section class="py-20 bg-slate-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="p-8 sm:p-12 rounded-3xl bg-gradient-to-r from-emerald-950/30 via-slate-900 to-slate-900 border border-emerald-500/20 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="space-y-2 max-w-xl">
              <h3 class="text-xl sm:text-2xl font-bold text-white">{{ t('landing.trustTitle') }}</h3>
              <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">{{ t('landing.trustSubtitle') }}</p>
            </div>
            <div class="flex items-center gap-4">
              <div class="flex items-center gap-2 px-4 py-2 rounded-2xl bg-slate-950 border border-slate-800 text-xs text-slate-300">
                <span class="text-emerald-400">🛡️</span>
                <span>Tenant Scoped Data</span>
              </div>
              <div class="flex items-center gap-2 px-4 py-2 rounded-2xl bg-slate-950 border border-slate-800 text-xs text-slate-300">
                <span class="text-emerald-400">🔒</span>
                <span>Sanitized AI Prompts</span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-950 border-t border-slate-800/80 py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6 text-xs text-slate-500">
        <div>
          © 2026 Marketly AI Inc. All rights reserved. Built for autonomous marketing excellence.
        </div>
        <div class="flex items-center gap-6">
          <button @click="showPrivacyModal = true" class="hover:text-slate-300 transition-colors">{{ t('navigation.privacy') }}</button>
          <button @click="showTermsModal = true" class="hover:text-slate-300 transition-colors">{{ t('navigation.terms') }}</button>
          <button @click="showContactModal = true" class="hover:text-slate-300 transition-colors">{{ t('navigation.contact') }}</button>
        </div>
      </div>
    </footer>

    <!-- Contact Modal -->
    <div v-if="showContactModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
        <h3 class="text-base font-bold text-white">{{ t('landing.contactTitle') }}</h3>
        <p class="text-xs text-slate-400">{{ t('landing.contactSubtitle') }}</p>
        <div class="space-y-3">
          <input type="text" placeholder="Your Name" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" />
          <input type="email" placeholder="Work Email" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" />
          <textarea rows="3" placeholder="How can we help?" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white"></textarea>
        </div>
        <div class="flex items-center justify-end gap-2 pt-2">
          <button @click="showContactModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-xs text-slate-300">{{ t('common.cancel') }}</button>
          <button @click="handleSendContact" class="tactile-btn tactile-btn-primary text-xs px-4 py-2">{{ t('landing.sendMsg') }}</button>
        </div>
      </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div v-if="showPrivacyModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full space-y-4 shadow-2xl max-h-[80vh] overflow-y-auto">
        <h3 class="text-base font-bold text-white">{{ t('navigation.privacy') }}</h3>
        <div class="text-xs text-slate-400 space-y-3 leading-relaxed">
          <p>Marketly AI is built with zero-trust architecture. We do not use your proprietary brand knowledge or customer data to train public foundation models.</p>
          <p>All tenant data is strictly partitioned by organization ID, and tokens/credentials are securely hashed in accordance with industry best practices.</p>
        </div>
        <div class="flex justify-end pt-2">
          <button @click="showPrivacyModal = false" class="tactile-btn tactile-btn-primary text-xs px-4 py-2">Close</button>
        </div>
      </div>
    </div>

    <!-- Terms Modal -->
    <div v-if="showTermsModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full space-y-4 shadow-2xl max-h-[80vh] overflow-y-auto">
        <h3 class="text-base font-bold text-white">{{ t('navigation.terms') }}</h3>
        <div class="text-xs text-slate-400 space-y-3 leading-relaxed">
          <p>By using Marketly AI, you agree to formulate strategic marketing plans and content in compliance with applicable platform rules and advertising guidelines.</p>
          <p>AI recommendations are strategic suggestions requiring human review and approval prior to publication.</p>
        </div>
        <div class="flex justify-end pt-2">
          <button @click="showTermsModal = false" class="tactile-btn tactile-btn-primary text-xs px-4 py-2">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { t, currentLocale, setLocale } from '../i18n';

defineEmits(['open-auth']);

const showContactModal = ref(false);
const showPrivacyModal = ref(false);
const showTermsModal = ref(false);
const billingInterval = ref<'monthly' | 'annual'>('monthly');

const siteSettings = ref<any>({
  hero_title_ar: '',
  hero_title_en: '',
  hero_subtitle_ar: '',
  hero_subtitle_en: '',
  announcement_banner: '',
  contact_email: 'contact@marketly.ai',
  contact_phone: '+966 50 000 0000',
});

const heroTitle = computed(() => {
  if (currentLocale.value === 'ar' && siteSettings.value.hero_title_ar) {
    return siteSettings.value.hero_title_ar;
  }
  if (currentLocale.value === 'en' && siteSettings.value.hero_title_en) {
    return siteSettings.value.hero_title_en;
  }
  return t('landing.heroTitle');
});

const heroSubtitle = computed(() => {
  if (currentLocale.value === 'ar' && siteSettings.value.hero_subtitle_ar) {
    return siteSettings.value.hero_subtitle_ar;
  }
  if (currentLocale.value === 'en' && siteSettings.value.hero_subtitle_en) {
    return siteSettings.value.hero_subtitle_en;
  }
  return t('landing.heroSubtitle');
});

const fetchSiteSettings = async () => {
  try {
    const res = await axios.get('/api/v1/site-settings');
    if (res.data?.data?.settings) {
      siteSettings.value = { ...siteSettings.value, ...res.data.data.settings };
    }
  } catch (e) {}
};

const plans = ref<any[]>([
  {
    id: 1,
    name: 'Starter',
    description: 'Perfect for solopreneurs & creators exploring AI marketing autonomy.',
    price_monthly: 0,
    price_annual: 0,
    currency: 'SAR',
    entitlements: [
      { id: 1, feature_key: 'brand_brain', is_enabled: true, limit_count: -1 },
      { id: 2, feature_key: 'ai_strategy', is_enabled: true, limit_count: 5 },
      { id: 3, feature_key: 'ai_content', is_enabled: true, limit_count: 30 },
      { id: 4, feature_key: 'team_members', is_enabled: true, limit_count: 2 },
      { id: 5, feature_key: 'social_accounts', is_enabled: false, limit_count: 0 },
    ]
  },
  {
    id: 2,
    name: 'Growth',
    description: 'For growing teams requiring continuous strategic generation and multi-channel publishing.',
    price_monthly: 299,
    price_annual: 2870,
    currency: 'SAR',
    entitlements: [
      { id: 5, feature_key: 'brand_brain', is_enabled: true, limit_count: -1 },
      { id: 6, feature_key: 'ai_strategy', is_enabled: true, limit_count: 20 },
      { id: 7, feature_key: 'ai_content', is_enabled: true, limit_count: 150 },
      { id: 8, feature_key: 'team_members', is_enabled: true, limit_count: 5 },
      { id: 9, feature_key: 'social_accounts', is_enabled: true, limit_count: 5 },
      { id: 10, feature_key: 'analytics', is_enabled: true, limit_count: -1 },
    ]
  },
  {
    id: 3,
    slug: 'pro',
    name: 'Pro',
    description: 'Unlimited autonomous marketing engine with dedicated enterprise agency features.',
    price_monthly: 699,
    price_annual: 6710,
    currency: 'SAR',
    entitlements: [
      { id: 10, feature_key: 'brand_brain', is_enabled: true, limit_count: -1 },
      { id: 11, feature_key: 'ai_strategy', is_enabled: true, limit_count: -1 },
      { id: 12, feature_key: 'ai_content', is_enabled: true, limit_count: -1 },
      { id: 13, feature_key: 'team_members', is_enabled: true, limit_count: -1 },
      { id: 14, feature_key: 'analytics', is_enabled: true, limit_count: -1 },
      { id: 15, feature_key: 'automation', is_enabled: true, limit_count: -1 },
    ]
  }
]);

const fetchPlans = async () => {
  try {
    const res = await axios.get('/api/v1/billing/plans');
    if (res.data?.data?.plans?.length) {
      plans.value = res.data.data.plans;
    }
  } catch (err) {
    // Keep fallback plans if unauthenticated or offline
  }
};

const formatFeatureLabel = (key: string, limit: number) => {
  const labels: Record<string, string> = {
    brand_brain: 'Brand Brain Intelligence Core',
    ai_strategy: limit === -1 ? 'Unlimited AI Strategies' : `${limit} AI Strategies / mo`,
    ai_content: limit === -1 ? 'Unlimited Content Generations' : `${limit} Posts / mo`,
    team_members: limit === -1 ? 'Unlimited Team Members' : `Up to ${limit} Team Members`,
    social_accounts: limit === -1 ? 'Unlimited Social Channels' : `${limit} Connected Accounts`,
    analytics: 'ROI & Conversion Analytics',
    automation: 'Autonomous Marketing Autopilot',
  };
  return labels[key] || key;
};

const toggleLang = () => {
  setLocale(currentLocale.value === 'ar' ? 'en' : 'ar');
};

const scrollTo = (id: string) => {
  const el = document.getElementById(id);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' });
  }
};

const handleSendContact = () => {
  alert(t('landing.msgSent'));
  showContactModal.value = false;
};

onMounted(() => {
  fetchPlans();
  fetchSiteSettings();
});
</script>
