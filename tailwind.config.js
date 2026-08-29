/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.ts",
    "./resources/**/*.vue",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#ecfdf5',
          100: '#d1fae5',
          200: '#a7f3d0',
          300: '#6ee7b7',
          400: '#34d399',
          500: '#10b981',
          600: '#059669',
          700: '#047857',
          800: '#065f46',
          900: '#064e3b',
          950: '#022c22',
        },
        obsidian: {
          800: '#131f1a',
          850: '#0e1814',
          900: '#0a120e',
          950: '#060a08',
        },
        cyber: {
          700: '#1e293b',
          800: '#0f172a',
          900: '#020617',
        },
        gold: {
          400: '#fbbf24',
          500: '#d97706',
          600: '#b45309',
        }
      },
      fontFamily: {
        sans: ['Cairo', 'Inter', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'monospace'],
      },
      boxShadow: {
        'soft-3d': '0 4px 14px rgba(16, 185, 129, 0.08), 0 1px 3px rgba(0, 0, 0, 0.1)',
        'card-glow': '0 0 25px -5px rgba(16, 185, 129, 0.15)',
        'btn-tactile': '0 3px 0 #047857, 0 6px 14px rgba(16, 185, 129, 0.25)',
      },
    },
  },
  plugins: [],
}
