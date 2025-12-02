/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/Filament/**/*.php",
    "./app/View/Components/**/*.php",
  ],
  
  theme: {
    extend: {
      fontFamily: {
        display: ['Inter', 'system-ui', 'sans-serif'],
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      
      colors: {
        // Custom brand colors (if needed beyond daisyUI themes)
        brand: {
          50: '#f0f9ff',
          100: '#e0f2fe',
          200: '#bae6fd',
          300: '#7dd3fc',
          400: '#38bdf8',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
          800: '#075985',
          900: '#0c4a6e',
        },
      },
      
      boxShadow: {
        'glow': '0 0 20px rgba(99, 102, 241, 0.3)',
        'glow-lg': '0 0 30px rgba(99, 102, 241, 0.4)',
      },
      
      animation: {
        'fade-in': 'fadeIn 0.3s ease-in-out',
        'slide-in': 'slideIn 0.3s ease-out',
        'bounce-subtle': 'bounceSubtle 2s infinite',
      },
      
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideIn: {
          '0%': { transform: 'translateY(-10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        bounceSubtle: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-5px)' },
        },
      },
    },
  },
  
  plugins: [
    require('daisyui'),
  ],
  
  daisyui: {
    themes: [
      {
        // Light theme - matches current design
        light: {
          "primary": "#4f46e5",           // indigo-600
          "primary-content": "#ffffff",
          
          "secondary": "#0ea5e9",         // sky-500
          "secondary-content": "#ffffff",
          
          "accent": "#8b5cf6",            // violet-500
          "accent-content": "#ffffff",
          
          "neutral": "#1e293b",           // slate-800
          "neutral-content": "#f1f5f9",   // slate-100
          
          "base-100": "#ffffff",          // white
          "base-200": "#f8fafc",          // slate-50
          "base-300": "#e2e8f0",          // slate-200
          "base-content": "#0f172a",      // slate-900
          
          "info": "#0ea5e9",              // sky-500
          "info-content": "#ffffff",
          
          "success": "#10b981",           // emerald-500
          "success-content": "#ffffff",
          
          "warning": "#f59e0b",           // amber-500
          "warning-content": "#ffffff",
          
          "error": "#ef4444",             // red-500
          "error-content": "#ffffff",
        },
        
        // Dark theme
        dark: {
          "primary": "#6366f1",           // indigo-500
          "primary-content": "#ffffff",
          
          "secondary": "#38bdf8",         // sky-400
          "secondary-content": "#0f172a",
          
          "accent": "#a78bfa",            // violet-400
          "accent-content": "#1e1b4b",
          
          "neutral": "#1e293b",           // slate-800
          "neutral-content": "#f1f5f9",
          
          "base-100": "#0f172a",          // slate-900
          "base-200": "#1e293b",          // slate-800
          "base-300": "#334155",          // slate-700
          "base-content": "#f1f5f9",      // slate-100
          
          "info": "#38bdf8",
          "info-content": "#0f172a",
          
          "success": "#34d399",           // emerald-400
          "success-content": "#064e3b",
          
          "warning": "#fbbf24",           // amber-400
          "warning-content": "#78350f",
          
          "error": "#f87171",             // red-400
          "error-content": "#7f1d1d",
        },
      },
    ],
    
    // daisyUI config
    darkTheme: "dark",      // Name of dark theme
    base: true,             // Apply background color and foreground color
    styled: true,           // Include daisyUI colors and design decisions
    utils: true,            // Add responsive and modifier utility classes
    prefix: "",             // Prefix for daisyUI classnames (empty = no prefix)
    logs: true,             // Show info about daisyUI version and config
    themeRoot: ":root",     // The element that receives theme color CSS variables
  },
}
