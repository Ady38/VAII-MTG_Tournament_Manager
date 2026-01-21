document.addEventListener('DOMContentLoaded', function() {
    // simple tab toggling for signups/pairings/standings
    const tabBtns = document.querySelectorAll('.tournament-tab-btn');
    const tabContents = {
        signups: document.getElementById('tab-signups'),
        pairings: document.getElementById('tab-pairings'),
        standings: document.getElementById('tab-standings')
    };
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            tabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            Object.keys(tabContents).forEach(key => {
                if (tabContents[key]) {
                    tabContents[key].style.display = (this.dataset.tab === key) ? '' : 'none';
                }
            });
        });
    });
});