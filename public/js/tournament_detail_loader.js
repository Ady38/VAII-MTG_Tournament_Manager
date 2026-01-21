// tournament_detail_loader.js
// Reads JSON config from hidden div and sets window.TOURNAMENT_DETAIL_CONFIG for other scripts to use
(function(){
    'use strict';
    document.addEventListener('DOMContentLoaded', function(){
        var el = document.getElementById('tournament-detail-config');
        var cfg = {};
        if (el) {
            try { cfg = JSON.parse(el.getAttribute('data-config') || '{}'); } catch(e) { console.error('Failed to parse tournament config', e); }
        }
        window.TOURNAMENT_DETAIL_CONFIG = window.TOURNAMENT_DETAIL_CONFIG || {};
        Object.assign(window.TOURNAMENT_DETAIL_CONFIG, cfg);
    });
})();

