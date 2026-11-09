

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// storefront image zoom/lightbox - a single global store so every product template's gallery
// can open the same overlay without each one carrying its own modal markup.
Alpine.store('zoom', { open: false, src: null, alt: '' });

Alpine.start();
