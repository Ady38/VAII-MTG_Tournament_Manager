/*Vytvorene s pomocou GitHub Copilot*/

(function(){
    'use strict';

    // Tab switching
    function initTabs(){
        document.querySelectorAll('.tournament-tab-btn').forEach(function(btn){
            btn.addEventListener('click', function(){
                var tab = btn.getAttribute('data-tab');
                document.querySelectorAll('.tournament-tab-btn').forEach(function(b){ b.classList.remove('active'); });
                btn.classList.add('active');
                document.querySelectorAll('.tournament-tab-content').forEach(function(c){
                    c.style.display = c.id === 'tab-'+tab ? '' : 'none';
                });
            });
        });
    }

    // Rebuild standings table from data
    function updateStandings(rankings, commanders) {
        try {
            var tbody = document.querySelector('#tab-standings .table-responsive table.tournament-table tbody');
            if (!tbody) return;
            while (tbody.firstChild) tbody.removeChild(tbody.firstChild);

            var fmtPercent = function(v){
                if (v === null || v === undefined || v === '') return '-';
                var num = Number(v);
                if (isNaN(num)) return '-';
                return (num * 100).toFixed(2) + '%';
            };

            for (var i = 0; i < rankings.length; i++) {
                var r = rankings[i];
                var tr = document.createElement('tr'); tr.className = 'tournament-row';

                var tdIdx = document.createElement('td'); tdIdx.textContent = (i+1).toString(); tr.appendChild(tdIdx);

                var tdPlayer = document.createElement('td'); tdPlayer.textContent = r.username || r.user_id; tr.appendChild(tdPlayer);

                var tdPoints = document.createElement('td'); tdPoints.className = 'points-cell'; tdPoints.setAttribute('data-user-id', String(r.user_id));
                tdPoints.textContent = (typeof r.points !== 'undefined' && r.points !== null) ? Number(r.points).toFixed(2) : '0.00';
                tr.appendChild(tdPoints);

                var tdGwp = document.createElement('td'); tdGwp.className = 'gwp-cell'; tdGwp.setAttribute('data-user-id', String(r.user_id));
                tdGwp.textContent = fmtPercent(r.gwp);
                tr.appendChild(tdGwp);

                var tdOgwp = document.createElement('td'); tdOgwp.className = 'ogwp-cell'; tdOgwp.setAttribute('data-user-id', String(r.user_id));
                tdOgwp.textContent = fmtPercent(r.ogwp);
                tr.appendChild(tdOgwp);

                var tdComm = document.createElement('td'); tdComm.className = 'commander-cell';
                var comm = (commanders && commanders[String(r.user_id)]) ? commanders[String(r.user_id)] : '';
                if (comm) {
                    var span = document.createElement('span'); span.className = 'commander-link'; span.setAttribute('data-card-name', comm); span.textContent = comm;
                    tdComm.appendChild(span);
                }
                tr.appendChild(tdComm);

                tbody.appendChild(tr);
            }
        } catch (e) {
            console.error('updateStandings error', e);
        }
    }

    // Setup AJAX result forms to save results and update UI
    function initResultForms(){
        document.querySelectorAll('.result-form').forEach(function(form){
            var select = form.querySelector('.result-select');
            if (!select) return;
            var matchId = form.getAttribute('data-match-id') || form.dataset.matchId || '';

            select.addEventListener('change', function(){
                var fd = new FormData(form);
                fd.append('ajax', '1');
                select.disabled = true;

                var url = form.getAttribute('action') || form.action;

                fetch(url, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(function(resp){
                    if (!resp.ok) {
                        return resp.text().then(function(text){ throw new Error('Server returned HTTP ' + resp.status + '. First 200 chars: ' + text.substring(0,200)); });
                    }
                    var ct = resp.headers.get('content-type') || '';
                    if (ct.indexOf('application/json') !== -1) return resp.json();
                    return resp.text().then(function(text){ throw new Error('Server returned non-JSON response. First 200 chars: ' + text.substring(0,200)); });
                })
                .then(function(json){
                    if (json && json.success) {
                        let lbl = document.querySelector('.result-label[data-match-id="' + matchId + '"]');
                        if (lbl) {
                            if (json.result === 'unplayed') {
                                lbl.textContent = 'Unplayed';
                            } else if (json.result === 'draw') {
                                lbl.textContent = 'Draw (1-1)';
                            } else if (json.result) {
                                lbl.textContent = select.options[select.selectedIndex].text;
                            } else {
                                lbl.textContent = select.options[select.selectedIndex].text;
                            }
                            lbl.classList.add('result-saved');
                            setTimeout(function(){ lbl.classList.remove('result-saved'); }, 700);
                        }

                        if (json.points) {
                            Object.keys(json.points).forEach(function(uid){
                                var cell = document.querySelector('.points-cell[data-user-id="' + uid + '"]');
                                if (cell) cell.textContent = json.points[uid];
                            });
                        }

                        if (json.rankings) {
                            updateStandings(json.rankings, json.commanders || {});
                        }

                        select.disabled = false;
                    } else {
                        var err = json && json.error ? json.error : 'Unknown error';
                        let lbl = document.querySelector('.result-label[data-match-id="' + matchId + '"]');
                        if (lbl) lbl.textContent = 'Error: ' + err;
                    }
                })
                .catch(function(err){
                    console.error('Fetch error:', err);
                    select.disabled = false;
                    alert('Error saving result: ' + err.message);
                });
            });
        });
    }

    // Client-side deck upload validation
    function initDeckUploadValidation(){
        var fileInput = document.getElementById('decklist');
        if (!fileInput) return;
        var form = fileInput.closest('form');
        if (!form) return;

        form.addEventListener('submit', function(e){
            var f = fileInput.files && fileInput.files[0];
            if (!f) return true; // allow empty (server will handle)
            var name = f.name || '';
            var ext = name.split('.').pop().toLowerCase();
            if (ext !== 'txt') {
                e.preventDefault();
                alert('Only .txt files are accepted for decklists.');
                return false;
            }
            // limit file size to 200KB to be safe client-side
            var maxBytes = 200 * 1024;
            if (f.size > maxBytes) {
                e.preventDefault();
                alert('Decklist file is too large (max 200KB).');
                return false;
            }
            return true;
        });
    }

    // Public init
    function init(){
        initTabs();
        initResultForms();
        initDeckUploadValidation();
        // expose updateStandings so timer/result code can call it
        window.TOURNAMENT_updateStandings = updateStandings;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
