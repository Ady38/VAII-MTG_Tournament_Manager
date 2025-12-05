document.addEventListener('DOMContentLoaded', function () {
    const addModal = document.getElementById('addModal');
    const openAddModalBtn = document.getElementById('openAddModal');
    const closeAddModalBtn = document.getElementById('closeAddModal');
    const cancelAddBtn = document.getElementById('cancelAdd');
    const addForm = document.getElementById('addTournamentForm');

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

    // Ak je na stránke JS premenná window.keepAddModalOpenAfterSubmit, nechaj modal otvorený
    if (window.keepAddModalOpenAfterSubmit && addModal) {
        addModal.style.display = 'flex';
    }

    // Ak je na stránke JS premenná window.addModalErrors, zobraz alert a otvor modal
    if (window.addModalErrors && Array.isArray(window.addModalErrors) && addModal) {
        alert(window.addModalErrors.join('\n'));
        addModal.style.display = 'flex';
    }
});