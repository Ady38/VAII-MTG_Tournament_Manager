/*Vytvorene s pomocou GitHub Copilot*/

// require DOM ready and modal elements
document.addEventListener('DOMContentLoaded', function () {
    const addModal = document.getElementById('addModal');
    const openAddModalBtn = document.getElementById('openAddModal');
    const closeAddModalBtn = document.getElementById('closeAddModal');
    const cancelAddBtn = document.getElementById('cancelAdd');
    const addForm = document.getElementById('addTournamentForm');

    // attach open/close handlers
    if (addModal && openAddModalBtn && closeAddModalBtn && cancelAddBtn) {
        openAddModalBtn.addEventListener('click', function () {
            addModal.style.display = 'flex';
        });
        closeAddModalBtn.addEventListener('click', function () {
            addModal.style.display = 'none';
        });
        cancelAddBtn.addEventListener('click', function () {
            addModal.style.display = 'none';
        });
        window.addEventListener('click', function (e) {
            if (e.target === addModal) {
                addModal.style.display = 'none';
            }
        });
    }

    // keep modal open if server requested via global flag
    if (window.keepAddModalOpenAfterSubmit && addModal) {
        addModal.style.display = 'flex';
    }

    // show validation errors and open modal when server passed errors
    if (window.addModalErrors && Array.isArray(window.addModalErrors) && addModal) {
        alert(window.addModalErrors.join('\n'));
        addModal.style.display = 'flex';
    }

    // client-side validation before submit: ensure required fields and dates
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            const name = document.getElementById('add_name')?.value?.trim() || '';
            const start = document.getElementById('add_start_date')?.value || '';
            const end = document.getElementById('add_end_date')?.value || '';
            const status = document.getElementById('add_status')?.value || '';
            const errors = [];
            if (name === '') errors.push('Name is required.');
            if (start === '') errors.push('Start date is required.');
            if (end === '') errors.push('End date is required.');
            if (start !== '' && end !== '' && (new Date(start) > new Date(end))) errors.push('End date must be same or later than start date.');
            if (status === '') errors.push('Status is required.');
            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join('\n'));
                return false;
            }
            return true;
        });
    }
});