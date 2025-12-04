document.addEventListener('DOMContentLoaded', function () {
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

    // Sorting logic
    const table = document.getElementById('tournamentTable');
    if (table) {
        const headers = table.querySelectorAll('thead th[data-sort]');
        const tbody = table.querySelector('tbody');
        let sortState = {}; // columnIndex -> 'asc' | 'desc'

        function compareValues(a, b, type, direction) {
            if (type === 'date') {
                const da = a ? new Date(a) : null;
                const db = b ? new Date(b) : null;
                const va = da ? da.getTime() : 0;
                const vb = db ? db.getTime() : 0;
                return direction === 'asc' ? va - vb : vb - va;
            } else { // text
                const va = (a || '').toString().toLowerCase();
                const vb = (b || '').toString().toLowerCase();
                if (va < vb) return direction === 'asc' ? -1 : 1;
                if (va > vb) return direction === 'asc' ? 1 : -1;
                return 0;
            }
        }

        headers.forEach(function (header, index) {
            header.addEventListener('click', function () {
                const type = header.getAttribute('data-sort') || 'text';
                const current = sortState[index] === 'asc' ? 'desc' : 'asc';
                sortState = {}; // reset other columns
                sortState[index] = current;

                const rows = Array.from(tbody.querySelectorAll('tr'));
                rows.sort(function (rowA, rowB) {
                    const cellA = rowA.children[index].innerText.trim();
                    const cellB = rowB.children[index].innerText.trim();
                    return compareValues(cellA, cellB, type, current);
                });

                // Re-append sorted rows
                rows.forEach(function (row) { tbody.appendChild(row); });
            });
        });
    }
});
