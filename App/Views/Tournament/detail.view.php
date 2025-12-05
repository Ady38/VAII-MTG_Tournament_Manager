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
