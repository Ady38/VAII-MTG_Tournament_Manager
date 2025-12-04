<?php

/** @var \Framework\Support\LinkGenerator $link */
?>

<div class="container-fluid">
    <div class="row">
        <div class="col mt-5">
            <div class="text-center">
                <h1>MTG Tournament Manager</h1>
                <h4 class="subtitle">Organize and run Magic: The Gathering events with ease</h4>
                <img src="<?= $link->asset('images/logo_empty.png') ?>" alt="MTG Logo" style="max-width:220px;">

                <p class="lead mt-4">Create tournaments, register players, track pairings and standings, and export results — all in one place.</p>

                <div class="mt-4">
                    <a class="btn btn-primary btn-lg me-2" href="<?= $link->url('Tournament.index') ?>">View Tournaments</a>
                    <a class="btn btn-outline-secondary btn-lg" href="<?= $link->url('Auth.login') ?>">Admin Login</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-md-4 text-center">
            <h5>Manage Events</h5>
            <p>Create Swiss, Draft or Commander tournaments, set rounds, dates and locations, and manage player lists.</p>
        </div>
        <div class="col-md-4 text-center">
            <h5>Pairings & Standings</h5>
            <p>Automatic pairing generation, calculate match points, tie-breakers and display live standings during events.</p>
        </div>
        <div class="col-md-4 text-center">
            <h5>Reporting</h5>
            <p>Export pairings and results to printable PDFs or CSV for reporting and archival.</p>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col text-center">
            <h5>Quick Start</h5>
            <ol style="display:inline-block; text-align:left; max-width:600px;">
                <li>Create a new tournament from the <strong>View Tournaments</strong> page.</li>
                <li>Register players and configure rounds.</li>
                <li>Run pairings and record match results.</li>
            </ol>
        </div>
    </div>

    <footer class="row mt-5">
        <div class="col text-center footer-text mb-4">
            &copy; 2020-<?= date('Y') ?> MTG Tournament Manager — Built with Vaííčko MVC FW
        </div>
    </footer>
</div>
