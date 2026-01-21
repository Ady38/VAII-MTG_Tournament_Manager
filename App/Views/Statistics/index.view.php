<?php
/** @var \Framework\Support\LinkGenerator $link */
/** @var array $commanders */
?>
<h1>Commander Statistics</h1>
<p>Top commanders (from top-8 results of tournaments). Click a commander name to see decklists and players.</p>
<?php if (empty($commanders)): ?>
    <p>No commander data available.</p>
<?php else: ?>
    <?php $total = array_sum(array_map(function($x){ return (int)($x['count'] ?? 0); }, $commanders)); ?>
    <div class="table-responsive">
        <table class="tournament-table text-center">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Commander</th>
                    <th>Share</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($commanders as $c): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><a class="commander-name-link" href="?c=Statistics&a=detail&name=<?= urlencode($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></a></td>
                        <td>
                            <?php if ($total > 0): ?>
                                <?= htmlspecialchars(number_format((($c['count'] ?? 0) / $total) * 100, 1)) ?>%
                            <?php else: ?>
                                0%
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>