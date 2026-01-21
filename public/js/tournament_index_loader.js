// tournament_index_loader.js
// Move inline modal logic from Tournament index view to external file
document.addEventListener('DOMContentLoaded', function(){
    var addModal = document.getElementById('addModal');
    var openAddModalBtn = document.getElementById('openAddModal');
    var closeAddModalBtn = document.getElementById('closeAddModal');
    var cancelAddBtn = document.getElementById('cancelAdd');
    var addForm = document.getElementById('addTournamentForm');

    if (addModal && openAddModalBtn && closeAddModalBtn && cancelAddBtn) {
        openAddModalBtn.addEventListener('click', function () { addModal.style.display = 'flex'; });
        closeAddModalBtn.addEventListener('click', function () { addModal.style.display = 'none'; });
        cancelAddBtn.addEventListener('click', function () { addModal.style.display = 'none'; });
        window.addEventListener('click', function (e) { if (e.target === addModal) addModal.style.display = 'none'; });
    }

    // show server validation errors and open modal when provided via hidden div
    var errDiv = document.getElementById('add-modal-errors');
    if (errDiv) {
        try {
            var raw = errDiv.getAttribute('data-errors') || '[]';
            var arr = JSON.parse(raw);
            if (Array.isArray(arr) && arr.length) {
                alert(arr.join('\n'));
                if (addModal) addModal.style.display = 'flex';
            }
        } catch (e) { console.error('Failed to parse add modal errors', e); }
    }

    // client-side validation: ensure required fields and date ordering
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            var name = document.getElementById('add_name')?.value?.trim() || '';
            var start = document.getElementById('add_start_date')?.value || '';
            var end = document.getElementById('add_end_date')?.value || '';
            var status = document.getElementById('add_status')?.value || '';
            var errors = [];
            if (name === '') errors.push('Name is required.');
            if (start === '') errors.push('Start date is required.');
            if (end === '') errors.push('End date is required.');
            if (start !== '' && end !== '' && (new Date(start) > new Date(end))) errors.push('End date must be same or later than start date.');
            if (status === '') errors.push('Status is required.');
            if (errors.length > 0) { e.preventDefault(); alert(errors.join('\n')); return false; }
            return true;
        });
    }
});

