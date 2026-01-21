// require DOM ready and modal elements
document.addEventListener('DOMContentLoaded', function () {
    const addModal = document.getElementById('addModal');
    const openAddModalBtn = document.getElementById('openAddModal');
    const closeAddModalBtn = document.getElementById('closeAddModal');
    const cancelAddBtn = document.getElementById('cancelAdd');

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
});