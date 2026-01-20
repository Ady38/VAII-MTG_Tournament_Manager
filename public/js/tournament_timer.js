(function(){
    'use strict';
    var cfg = window.TOURNAMENT_DETAIL_CONFIG || {};
    var tournamentId = cfg.tournamentId || 0;
    var pollInterval = cfg.timerPollInterval || 5000;
    var timerDisplay = null;
    var mainRoundSelect = null;
    var startBtn = null;
    var resetBtn = null;

    function formatHMS(sec){
        var s = Math.max(0, Math.floor(sec));
        var hh = Math.floor(s/3600); var mm = Math.floor((s%3600)/60); var ss = s%60;
        if (hh>0) return hh+':' + String(mm).padStart(2,'0') + ':' + String(ss).padStart(2,'0');
        return String(mm).padStart(2,'0') + ':' + String(ss).padStart(2,'0');
    }

    var localCountdownId = null;
    var localRemaining = 0;

    function clearLocalCountdown(){ if (localCountdownId !== null) { clearInterval(localCountdownId); localCountdownId = null; } localRemaining = 0; }

    function startLocalCountdown(seconds){
        clearLocalCountdown();
        localRemaining = Math.max(0, Math.floor(seconds));
        if (localRemaining <= 0) { if (timerDisplay) timerDisplay.textContent = 'Not running'; return; }
        if (timerDisplay) timerDisplay.textContent = formatHMS(localRemaining);
        localCountdownId = setInterval(function(){
            localRemaining -= 1;
            if (localRemaining <= 0) { clearLocalCountdown(); if (timerDisplay) timerDisplay.textContent = 'Not running'; fetchStatus(); return; }
            if (timerDisplay) timerDisplay.textContent = formatHMS(localRemaining);
        }, 1000);
    }

    function getSelectedRound(){
        if (mainRoundSelect && mainRoundSelect.value) return mainRoundSelect.value;
        return cfg.selectedRound || 0;
    }

    function fetchStatus(){
        var round = getSelectedRound();
        var url = '?c=Tournament&a=timerStatus&tournament_id=' + encodeURIComponent(tournamentId) + '&round=' + encodeURIComponent(round);
        fetch(url, { credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'} })
            .then(function(r){ return r.json(); })
            .then(function(json){
                if (!json || !json.success) { clearLocalCountdown(); if (timerDisplay) timerDisplay.textContent = 'Error'; return; }
                if (!json.active || !json.remaining_seconds) { clearLocalCountdown(); if (timerDisplay) timerDisplay.textContent = 'Not running'; return; }
                var serverRem = parseInt(json.remaining_seconds, 10) || 0;
                if (localCountdownId === null) startLocalCountdown(serverRem);
                else if (Math.abs(localRemaining - serverRem) > 2) startLocalCountdown(serverRem);
                // expose started_by and round to UI if needed
                if (cfg.onStatus) cfg.onStatus(json);
            }).catch(function(err){ console.error('timer status fetch error', err); clearLocalCountdown(); if (timerDisplay) timerDisplay.textContent = 'Error'; });
    }

    function startTimerRequest(){
        var round = getSelectedRound();
        if (!round || round === '0') { alert('No round selected — cannot start timer.'); return; }
        var fd = new FormData(); fd.append('tournament_id', tournamentId); fd.append('round', round);
        if (startBtn) startBtn.disabled = true;
        fetch('?c=Tournament&a=startTimer', { method:'POST', body: fd, credentials:'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'} })
            .then(function(r){ return r.json(); })
            .then(function(json){ if (startBtn) startBtn.disabled = false; if (json && json.success) { if (json.end_time && json.round) { var end = Date.parse(json.end_time); if (!isNaN(end)) { var rem = Math.max(0, Math.floor((end - Date.now())/1000)); startLocalCountdown(rem); } else { fetchStatus(); } } else { fetchStatus(); } } else { alert('Failed to start timer: ' + (json && json.message ? json.message : 'unknown')); } })
            .catch(function(err){ if (startBtn) startBtn.disabled = false; console.error(err); alert('Network error'); });
    }

    function resetTimerRequest(){ if (!confirm('Reset the round timer?')) return; var fd = new FormData(); fd.append('tournament_id', tournamentId); if (resetBtn) resetBtn.disabled = true; fetch('?c=Tournament&a=resetTimer', { method:'POST', body: fd, credentials:'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'} }) .then(function(r){ return r.json(); }) .then(function(json){ if (resetBtn) resetBtn.disabled = false; if (json && json.success) { clearLocalCountdown(); if (timerDisplay) timerDisplay.textContent = 'Not running'; } else { alert('Failed to reset timer: ' + (json && json.message ? json.message : 'unknown')); } }) .catch(function(err){ if (resetBtn) resetBtn.disabled = false; console.error(err); alert('Network error'); }); }

    function init(){
        timerDisplay = document.getElementById('timer-display');
        mainRoundSelect = document.getElementById('round-select');
        startBtn = document.getElementById('start-timer-btn');
        resetBtn = document.getElementById('reset-timer-btn');

        if (startBtn) startBtn.addEventListener('click', startTimerRequest);
        if (resetBtn) resetBtn.addEventListener('click', resetTimerRequest);

        // immediate poll + periodic poll
        fetchStatus();
        setInterval(fetchStatus, pollInterval);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();

    // Expose for debugging
    window.TOURNAMENT_timer = { startLocalCountdown: startLocalCountdown, clearLocalCountdown: clearLocalCountdown };
})();

