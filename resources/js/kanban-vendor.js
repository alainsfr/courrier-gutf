import Sortable from 'sortablejs';
import { createIcons, icons } from 'lucide';

window.Sortable = Sortable;
window.lucide = { createIcons: (options = {}) => createIcons({ icons, ...options }) };
