<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var \App\Models\Tournament|null $nextTournament */
?>
<div class="container-fluid">
    <div class="row">
        <div class="col mt-5">
            <div class="text-center">
                <h1>MTG Tournament Manager</h1>
                <h4 class="subtitle">Organize and run Magic: The Gathering events with ease</h4>
                <img src="<?= $link->asset('images/logo_empty.png') ?>" alt="MTG Logo" class="home-logo">

                <p class="lead mt-4">Create tournaments, register players, track pairings and standings, and export results — all in one place.</p>

                <?php if ($nextTournament): ?>
                    <div class="mx-auto mt-5 home-next-tournament-wrapper">
                        <div class="text-center py-4 px-3 home-next-tournament-card">
                            <h3 class="mb-2 home-next-tournament-title">
                                <a href="<?= $link->url('Tournament.detail', ['id' => $nextTournament->tournament_id]) ?>">
                                    <?= htmlspecialchars($nextTournament->name) ?>
                                </a>
                            </h3>
                            <div class="mb-2 home-next-tournament-location">
                                <?= htmlspecialchars($nextTournament->location) ?>
                            </div>
                            <div class="home-next-tournament-date">
                                <?= date('j.n.Y', strtotime($nextTournament->start_date)) ?>
                            </div>
                            <a href="<?= $link->url('Tournament.detail', ['id' => $nextTournament->tournament_id]) ?>"
                               class="btn btn-primary btn-lg home-primary-btn home-next-tournament-btn mt-3">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="mx-auto mt-5 home-next-tournament-wrapper">
                        <div class="text-center py-4 px-3 home-no-tournament-card">
                            <h5 class="mb-0">No upcoming tournaments.</h5>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mt-4">
                    <a class="btn btn-primary btn-lg me-2 home-primary-btn"
                       href="<?= $link->url('Tournament.index') ?>">View Tournaments</a>
                    <a class="btn btn-outline-secondary btn-lg home-primary-btn"
                       href="<?= $link->url('Auth.login') ?>">Login</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-md-4 text-center">
            <h5>Manage Events</h5>
            <p>Create Swiss, Draft or Commander tournaments,
                set rounds, dates and locations, and manage player lists.</p>
        </div>
        <div class="col-md-4 text-center">
            <h5>Pairings & Standings</h5>
            <p>Automatic pairing generation, calculate match points,
                tie-breakers and display live standings during events.</p>
        </div>
        <div class="col-md-4 text-center">
            <h5>Reporting</h5>
            <p>Export pairings and results to printable PDFs or CSV for reporting and archival.</p>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col text-center">
            <h5>Quick Start</h5>
            <ol class="home-quickstart-list">
                <li>Create a new tournament from the <strong>View Tournaments</strong> page.</li>
                <li>Register players and configure rounds.</li>
                <li>Run pairings and record match results.</li>
            </ol>
        </div>
    </div>

    <footer class="row mt-5">
        <div class="col text-center footer-text mb-4">
            &copy; 2025-<?= date('Y') ?> MTG Tournament Manager — Built with Vaííčko MVC FW
        </div>
    </footer>
</div>
