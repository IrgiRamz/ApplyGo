/**
 * Custom Velzone Layout Initialization
 * Minimal version - main handlers are in app.blade.php inline script
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }

        // Initialize Waves effect
        if (typeof Waves !== 'undefined') {
            Waves.init();
        }

        // SimpleBar for sidebar scrollbar
        var scrollbar = document.getElementById('scrollbar');
        if (scrollbar && typeof SimpleBar !== 'undefined') {
            try {
                new SimpleBar(scrollbar);
            } catch (e) {}
        }
    });

    window.addEventListener('load', function() {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
})();
