import './bootstrap';
import '@fontsource/manrope/400.css';
import '@fontsource/manrope/500.css';
import '@fontsource/manrope/600.css';
import '@fontsource/manrope/700.css';
import '@fontsource/space-grotesk/500.css';
import '@fontsource/space-grotesk/600.css';
import '@fontsource/space-grotesk/700.css';
import '../css/app.css';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

// Import language switcher enhancement
import './components/language-switcher.js';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();
