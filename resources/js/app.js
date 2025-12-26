import './bootstrap';
import '../css/app.css';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

// Import language switcher enhancement
import './components/language-switcher.js';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();
