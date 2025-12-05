document.addEventListener('DOMContentLoaded', function () {
    // --- Edit Tournament Modal logic ---
    let modal = document.getElementById('editModal');
    let closeBtn = document.getElementById('closeEditModal');
    let cancelBtn = document.getElementById('cancelEdit');

    if (!modal || !closeBtn || !cancelBtn) {
        return;
    }

    let idInput = document.getElementById('edit_id');
    let nameInput = document.getElementById('edit_name');
    let locationInput = document.getElementById('edit_location');
    let startDateInput = document.getElementById('edit_start_date');
    let endDateInput = document.getElementById('edit_end_date');
    let statusSelect = document.getElementById('edit_status');

    function openModal() {
        modal.style.display = 'flex';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    // open modal on Edit click and prefill form
    document.querySelectorAll('.edit-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            if (!idInput || !nameInput || !locationInput || !startDateInput || !endDateInput || !statusSelect) {
                return;
            }

            idInput.value = this.getAttribute('data-id') || '';
            nameInput.value = this.getAttribute('data-name') || '';
            locationInput.value = this.getAttribute('data-location') || '';
            startDateInput.value = (this.getAttribute('data-start_date') || '').substring(0, 10);
            endDateInput.value = (this.getAttribute('data-end_date') || '').substring(0, 10);
            statusSelect.value = this.getAttribute('data-status') || 'planned';

            openModal();
        });
    });

    // close modal on X or Cancel click
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', function (e) {
        e.preventDefault();
        closeModal();
    });

    // close when clicking outside modal content
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });
});
