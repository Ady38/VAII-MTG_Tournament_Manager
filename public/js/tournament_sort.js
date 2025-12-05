document.addEventListener('DOMContentLoaded', function () {
    // --- Sorting logic ---
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

