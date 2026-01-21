document.addEventListener('DOMContentLoaded', function () {
    // modal element refs
    let modal = document.getElementById('editModal');
    let closeBtn = document.getElementById('closeEditModal');
    let cancelBtn = document.getElementById('cancelEdit');

    // if elements missing, stop
    if (!modal || !closeBtn || !cancelBtn) {
        return;
    }

    // inputs inside modal
    let idInput = document.getElementById('edit_id');
    let nameInput = document.getElementById('edit_name');
    let locationInput = document.getElementById('edit_location');
    let startDateInput = document.getElementById('edit_start_date');
    let endDateInput = document.getElementById('edit_end_date');
    let statusSelect = document.getElementById('edit_status');

    // open/close helpers
    function openModal() { modal.style.display = 'flex'; }
    function closeModal() { modal.style.display = 'none'; }

    // prefill and open modal when Edit clicked
    document.querySelectorAll('.edit-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            if (!idInput || !nameInput || !locationInput || !startDateInput || !endDateInput || !statusSelect) {
                return;
            }

            idInput.value = this.getAttribute('data-id') || '';
            nameInput.value = this.getAttribute('data-name') || '';
            locationInput.value = this.getAttribute('data-location') || '';
            let startRaw = this.getAttribute('data-start_date') || '';
            let endRaw = this.getAttribute('data-end_date') || '';
            console.debug('Edit modal raw datetimes:', { startRaw, endRaw });
            startDateInput.value = startRaw;
            endDateInput.value = endRaw;
            statusSelect.value = this.getAttribute('data-status') || 'planned';

            openModal();
        });
    });

    // close handlers
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', function (e) { e.preventDefault(); closeModal(); });

    // close by clicking outside content
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
});
