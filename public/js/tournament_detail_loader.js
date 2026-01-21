/*Vytvorene s pomocou GitHub Copilot*/
// tournament_detail_loader.js
// Reads JSON config from hidden div and sets window.TOURNAMENT_DETAIL_CONFIG for other scripts to use
(function(){
    'use strict';
    function applyConfig(){
        var el = document.getElementById('tournament-detail-config');
        var cfg = {};
        if (el) {
            try { cfg = JSON.parse(el.getAttribute('data-config') || '{}'); } catch(e) { console.error('Failed to parse tournament config', e); }
        }
        window.TOURNAMENT_DETAIL_CONFIG = window.TOURNAMENT_DETAIL_CONFIG || {};
        Object.assign(window.TOURNAMENT_DETAIL_CONFIG, cfg);
    }

    // Try to apply immediately (the script is included at the end of the page so the element should exist).
    try { applyConfig(); } catch(e) { /* ignore */ }

    // Also ensure it's applied on DOMContentLoaded in case the script was included earlier.
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', applyConfig);
})();
