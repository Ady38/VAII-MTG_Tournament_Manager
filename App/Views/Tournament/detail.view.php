<?php
/** @var object $tournament */
?>
<h1>Tournament Detail</h1>
<style>
.tournament-detail-row {
    display: flex;
    flex-direction: row;
    gap: 20px;
    justify-content: flex-start;
    margin-bottom: 24px;
}
.tournament-detail-card {
    background: rgba(0, 0, 0, 0.7);
    border: 2px solid #d4af37;
    border-radius: 18px;
    color: #fff;
    min-width: 180px;
    padding: 18px 24px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
.tournament-detail-label {
    color: #d4af37;
    font-weight: 600;
    margin-bottom: 6px;
    font-size: 1.1em;
}
.tournament-detail-value {
    color: #fff;
    font-size: 1.15em;
    word-break: break-word;
}
.tournament-tabs {
    margin-top: 32px;
    margin-bottom: 12px;
    display: flex;
    gap: 8px;
}
.tournament-tab-btn {
    background: #222;
    color: #d4af37;
    border: 1px solid #d4af37;
    border-radius: 8px 8px 0 0;
    padding: 8px 24px;
    cursor: pointer;
    font-size: 1.1em;
    outline: none;
    transition: background 0.2s, color 0.2s;
}
.tournament-tab-btn.active {
    background: #d4af37;
    color: #222;
    font-weight: bold;
}
.tournament-tab-content {
    background: rgba(0,0,0,0.7);
    border: 1px solid #d4af37;
    border-radius: 0 0 12px 12px;
    padding: 24px;
    margin-bottom: 32px;
    color: #fff;
}
</style>
<div class="tournament-detail-row">
    <div class="tournament-detail-card">
        <div class="tournament-detail-label">Name</div>
        <div class="tournament-detail-value"><?= htmlspecialchars($tournament->name) ?></div>
    </div>
    <div class="tournament-detail-card">
        <div class="tournament-detail-label">Location</div>
        <div class="tournament-detail-value"><?= htmlspecialchars($tournament->location) ?></div>
    </div>
    <div class="tournament-detail-card">
        <div class="tournament-detail-label">Start Date</div>
        <div class="tournament-detail-value"><?= htmlspecialchars($tournament->start_date) ?></div>
    </div>
    <div class="tournament-detail-card">
        <div class="tournament-detail-label">End Date</div>
        <div class="tournament-detail-value"><?= htmlspecialchars($tournament->end_date) ?></div>
    </div>
    <div class="tournament-detail-card">
        <div class="tournament-detail-label">Status</div>
        <div class="tournament-detail-value"><?= htmlspecialchars($tournament->status) ?></div>
    </div>
</div>
<a href="?c=Tournament&a=index">Back to tournaments</a>

<hr>
<?php $showAllTabs = in_array($tournament->status, ['ongoing', 'finished']); ?>
<div class="tournament-tabs">
    <button class="tournament-tab-btn active" data-tab="signups">Prihlasovanie</button>
    <?php if ($showAllTabs): ?>
        <button class="tournament-tab-btn" data-tab="pairings">Párovanie</button>
        <button class="tournament-tab-btn" data-tab="standings">Poradie</button>
    <?php endif; ?>
</div>
<div class="tournament-tab-content" id="tab-signups">
    <p>Tu bude prihlasovanie na turnaj.</p>
</div>
<?php if ($showAllTabs): ?>
<div class="tournament-tab-content" id="tab-pairings" style="display:none">
    <p>Tu bude párovanie hráčov/zápasov.</p>
</div>
<div class="tournament-tab-content" id="tab-standings" style="display:none">
    <p>Tu bude poradie (výsledky) turnaja.</p>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
</script>
