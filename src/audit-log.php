<?php

declare(strict_types=1);

require_once __DIR__ . '/script/include/db-connection.php';
require_once __DIR__ . '/script/include/functions.php';
require_once __DIR__ . '/script/include/render.php';

processExpirations($pdo);
processValidFrom($pdo);

$data = [];
$userId = !empty($_GET['userId']) > 0 ? $_GET['userId'] : null;
$transactionId = !empty($_GET['transactionId']) > 0 ? $_GET['transactionId'] : null;
$creditId = !empty($_GET['creditId']) > 0 ? $_GET['creditId'] : null;

if ($userId) {
    $data[] = renderSqlResult(
        $pdo,
        sprintf('Audit log for userId: %d', $userId),
        'SELECT ta.id AS log_id, t.id AS transaction_id, t.type AS transaction_type, ct.name AS credit_type, ta.amount, c.id AS credit_id, ta.created_at, c.expired_at   
             FROM transaction_audit ta
             JOIN transaction t ON t.id = ta.transaction_id
             JOIN credit c ON c.id = ta.credit_id
             JOIN credit_type ct ON c.credit_type_id = ct.id
             WHERE t.user_id = ' . $userId . '
             ORDER BY ta.created_at ASC'
    );

    $data[] = renderSqlResult(
        $pdo,
        sprintf('Requests for userId: %d', $userId),
        'SELECT *   
             FROM request r
             WHERE r.user_id = ' . $userId . '
             ORDER BY r.created_at ASC'
    );
} elseif ($transactionId) {
    $data[] = renderSqlResult(
        $pdo,
        sprintf('Audit log for transaction: %d', $transactionId),
        'SELECT ta.id AS log_id, t.id AS transaction_id, t.type AS transaction_type, ct.name AS credit_type, ta.amount, c.id AS credit_id, ta.created_at, c.expired_at   
             FROM transaction t
             LEFT JOIN transaction_audit ta ON t.id = ta.transaction_id
             JOIN credit c ON c.id = ta.credit_id
             JOIN credit_type ct ON c.credit_type_id = ct.id
             WHERE t.id = ' . $transactionId . '
             ORDER BY ta.created_at ASC'
    );

    $data[] = renderSqlResult(
        $pdo,
        sprintf('Request for transaction: %d', $transactionId),
        'SELECT *   
             FROM request r
             WHERE r.transaction_id = ' . $transactionId . '
             ORDER BY r.created_at ASC'
    );

} elseif ($creditId) {
    $data[] = renderSqlResult(
        $pdo,
        sprintf('Audit log for creditId: %d', $creditId),
        'SELECT ta.id AS log_id, t.id AS transaction_id, t.type AS transaction_type, ct.name AS credit_type, ta.amount, c.id AS credit_id, ta.created_at, c.expired_at  
             FROM credit c
             JOIN credit_type ct ON c.credit_type_id = ct.id
             JOIN user u ON c.user_id = u.id
             LEFT JOIN transaction_audit ta ON ta.credit_id = c.id
             JOIN transaction t ON t.id = ta.transaction_id
             WHERE c.id = ' . $creditId . '
             ORDER BY t.created_at ASC'
    );

    $data[] = renderSqlResult(
        $pdo,
        sprintf('Request for creditId: %d', $creditId),
        'SELECT *   
             FROM request r
             JOIN transaction t ON r.transaction_id = t.id
             LEFT JOIN transaction_audit ta ON ta.transaction_id = t.id
             WHERE ta.credit_id = ' . $creditId . '
             ORDER BY r.created_at ASC'
    );

} else {
    $data[] = renderSqlResult(
        $pdo,
        'Audit log',
        'SELECT ta.id AS log_id, t.id AS transaction_id, t.type AS transaction_type, ct.name AS credit_type, ta.amount, c.id AS credit_id, ta.created_at, c.expired_at   
             FROM transaction_audit ta
             JOIN transaction t ON t.id = ta.transaction_id
             JOIN credit c ON c.id = ta.credit_id
             JOIN credit_type ct ON c.credit_type_id = ct.id
             ORDER BY ta.created_at ASC'
    );

    $data[] = renderSqlResult(
        $pdo,
        'Requests',
        'SELECT *   
             FROM request r
             ORDER BY r.created_at ASC'
    );
}

$protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ? "https://" : "http://";
$redirectUrl = $protocol . $_SERVER['HTTP_HOST'];

?>

<html>
<header>
    <title>Credit - PoC</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
</header>
<body>
    <div class="container-fluid">
		<div class="row">
			<div class="col-md-12">
                <?php echo sprintf('<br><a href="%s">Go back</a><br><br>', $redirectUrl);?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4">
				<?php echo renderAuditLogForm($pdo); ?>
			</div>
		</div>
        <div class="row">
			<?php
				if (count(array_filter($data)) === 0) {
					echo sprintf(
						'<strong>%s</strong><br><br>',
						'No data found...',
					);
				} else {
					foreach ($data as $item) {
						echo '<div class="col-md-12">';
							echo $item;
						echo '</div>';
					}
				}
			?>
        </div>
    </div>
</body>
</html>







