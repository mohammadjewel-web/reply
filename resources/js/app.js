import './bootstrap';

import Alpine from 'alpinejs';
import { initNotifications } from './notifications';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => initNotifications());
