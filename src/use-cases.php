<?php

declare(strict_types=1);

require_once __DIR__ . '/script/include/db-connection.php';
require_once __DIR__ . '/script/include/functions.php';
require_once __DIR__ . '/script/include/render.php';

processExpirations($pdo);

$userId = $_GET['userId'] ?? null;

$useCases = [];

// total amount from credit
if ($userId) {
	$sql = 'SELECT SUM(amount) AS total_amount FROM credit WHERE user_id = ' . $userId . ' AND expired_at > NOW();';
} else {
    $sql = 'SELECT user_id, SUM(amount) AS total_amount FROM credit WHERE expired_at > NOW() GROUP BY user_id';
}
$useCases[] = [
	'name' => 'Total amount (variant 1 - credit)',
	'sql' => $sql,
	'data' => renderSqlResult($pdo, '', $sql)
];
// total amount from transaction_audit
if ($userId) {
    $sql = 'SELECT SUM(amount) AS total_amount FROM transaction_audit WHERE user_id = ' . $userId . ' AND expired_at > NOW();';
} else {
    $sql = 'SELECT user_id, SUM(ta.amount) AS total_amount FROM transaction_audit ta JOIN credit c ON c.id = ta.credit_id  WHERE expired_at > NOW() GROUP BY c.user_id';
}
$useCases[] = [
    'name' => 'Total amount (variant 2 - transaction audit)',
    'sql' => $sql,
    'data' => renderSqlResult($pdo, '', $sql)
];
// amount by credit type
if ($userId) {
    $sql = 'SELECT SUM(amount) AS amount FROM credit WHERE user_id = ' . $userId . ' AND expired_at > NOW();';
} else {
    $sql = 'SELECT user_id, credit_type_id, SUM(amount) AS amount FROM credit WHERE expired_at > NOW() GROUP BY user_id, credit_type_id';
}
$useCases[] = [
    'name' => 'Amount by credit type (variant 1 - credit)',
    'sql' => $sql,
    'data' => renderSqlResult($pdo, '', $sql)
];
// amount by credit type
if ($userId) {
    $sql = 'SELECT SUM(amount) AS amount FROM credit WHERE user_id = ' . $userId . ' AND expired_at > NOW();';
} else {
    $sql = 'SELECT user_id, credit_type_id, SUM(ta.amount) AS amount FROM transaction_audit ta JOIN credit c ON c.id = ta.credit_id WHERE expired_at > NOW() GROUP BY user_id, credit_type_id';
}
$useCases[] = [
    'name' => 'Amount by credit type (variant 2 - transaction audit)',
    'sql' => $sql,
    'data' => renderSqlResult($pdo, '', $sql)
];
// total expired credit
if ($userId) {
    $sql = 'SELECT SUM(amount) AS amount FROM transaction WHERE user_id = ' . $userId . ' AND type = \'' . TransactionTypeEnum::CREDIT_EXPIRATION->value . '\'';
} else {
    $sql = 'SELECT user_id, SUM(t.amount) AS expired_amount FROM transaction t WHERE t.type = \'' . TransactionTypeEnum::CREDIT_EXPIRATION->value . '\' GROUP BY user_id';
}
$useCases[] = [
    'name' => 'Total expired credit',
    'sql' => $sql,
    'data' => renderSqlResult($pdo, '', $sql)
];

// expired credit by credit type
if ($userId) {
    $sql = 'SELECT SUM(amount) AS amount FROM transaction WHERE user_id = ' . $userId . ' AND type = \'' . TransactionTypeEnum::CREDIT_EXPIRATION->value . '\'';
} else {
    $sql = 'SELECT t.user_id, credit_type_id, SUM(t.amount) AS expired_amount FROM transaction t LEFT JOIN transaction_audit ta ON t.id = ta.transaction_id JOIN credit c ON c.id = ta.credit_id WHERE t.type = \'' . TransactionTypeEnum::CREDIT_EXPIRATION->value . '\' GROUP BY t.user_id, credit_type_id';
}
$useCases[] = [
    'name' => 'Expired credit by credit type',
    'sql' => $sql,
    'data' => renderSqlResult($pdo, '', $sql)
];

// getUsable credit by priority
if ($userId) {
    $sql = 'SELECT SUM(amount) AS amount FROM transaction WHERE user_id = ' . $userId . ' AND type = \'' . TransactionTypeEnum::CREDIT_EXPIRATION->value . '\'';
} else {
    $sql = 'SELECT c.user_id, c.id AS credit_id, ct.priority, ct.name AS creditType, c.created_at, c.expired_at, amount
        FROM credit c JOIN credit_type ct ON c.credit_type_id = ct.id
        WHERE (c.expired_at IS NULL OR c.expired_at > NOW()) AND amount > 0
        ORDER BY c.user_id, ct.priority, c.expired_at, c.created_at ASC';
}
$useCases[] = [
    'name' => 'Usable credits by priority',
    'sql' => $sql,
    'data' => renderSqlResult($pdo, '', $sql)
];



?>

<html>
<header>
    <title>Credit - PoC</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
</header>
<body>
    <div class="container-fluid">
        <div class="row">
			<h2>Use-cases</h2>
			<?php foreach ($useCases as $i => $useCase) { ?>
				<div class="col-md-6">
					<h3><?php echo $i+1 . ': ' . $useCase['name'] ?></h3>
					<br>
					<i><?php echo $useCase['sql'] ?></i>
					<div><?php echo $useCase['data'] ?></div>
				</div>
				<hr>
			<?php } ?>
        </div>
    </div>
</body>
</html>







