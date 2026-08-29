import { ref, computed } from 'vue';
import { en } from './locales/en';
import { ar } from './locales/ar';
import { SupportedLocale } from './types';

const messages: Record<SupportedLocale, any> = { en, ar };

const savedLocale = (localStorage.getItem('marketly_locale') as SupportedLocale) || 'ar';
export const currentLocale = ref<SupportedLocale>(savedLocale);

export const isRtl = computed(() => currentLocale.value === 'ar');

/**
 * Retrieve translated string using dot notation path (e.g. 'dashboard.welcome').
 */
export function t(path: string): string {
  const keys = path.split('.');
  
  // 1. Try active locale
  let result = keys.reduce((acc, key) => acc?.[key], messages[currentLocale.value]);
  
  // 2. Fallback to English
  if (result === undefined && currentLocale.value !== 'en') {
    result = keys.reduce((acc, key) => acc?.[key], messages.en);
  }

  return (typeof result === 'string' ? result : path);
}

/**
 * Switch active locale and update document direction.
 */
export function setLocale(locale: SupportedLocale): void {
  if (messages[locale]) {
    currentLocale.value = locale;
    localStorage.setItem('marketly_locale', locale);
    updateDocumentDirection(locale);
  }
}

/**
 * Update document root HTML attributes dynamically.
 */
export function updateDocumentDirection(locale: SupportedLocale): void {
  document.documentElement.lang = locale;
  document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';
}

/**
 * Locale-aware number formatting.
 */
export function formatNumber(value: number, options?: Intl.NumberFormatOptions): string {
  return new Intl.NumberFormat(currentLocale.value === 'ar' ? 'ar-SA' : 'en-US', options).format(value);
}

/**
 * Locale-aware currency formatting.
 */
export function formatCurrency(value: number, currency: string = 'SAR'): string {
  return new Intl.NumberFormat(currentLocale.value === 'ar' ? 'ar-SA' : 'en-US', {
    style: 'currency',
    currency,
  }).format(value);
}

/**
 * Locale-aware date formatting.
 */
export function formatDate(date: Date | string, options?: Intl.DateTimeFormatOptions): string {
  const d = typeof date === 'string' ? new Date(date) : date;
  return new Intl.DateTimeFormat(currentLocale.value === 'ar' ? 'ar-SA' : 'en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    ...options,
  }).format(d);
}

// Initial sync on module load
if (typeof document !== 'undefined') {
  updateDocumentDirection(currentLocale.value);
}
