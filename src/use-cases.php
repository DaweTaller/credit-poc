<?php

declare(strict_types=1);

require_once __DIR__ . '/script/include/db-connection.php';
require_once __DIR__ . '/script/include/functions.php';
require_once __DIR__ . '/script/include/render.php';

processExpirations($pdo);
processValidFrom($pdo);

$useCases = [];

// total amount from credit
$sql = 'SELECT user_id, SUM(amount) AS total_amount FROM credit WHERE expired_at > NOW() GROUP BY user_id';
$useCases[] = [
	'name' => 'Total amount (variant 1 - credit)',
	'sql' => $sql,
	'data' => renderSqlResult($pdo, '', $sql)
];
// total amount from transaction_audit
$sql = 'SELECT user_id, SUM(ta.amount) AS total_amount FROM transaction_audit ta JOIN credit c ON c.id = ta.credit_id  WHERE expired_at > NOW() GROUP BY c.user_id';
$useCases[] = [
    'name' => 'Total amount (variant 2 - transaction audit)',
    'sql' => $sql,
    'data' => renderSqlResult($pdo, '', $sql)
];
// amount by credit type
$sql = 'SELECT user_id, credit_type_id, SUM(amount) AS amount FROM credit WHERE expired_at > NOW() GROUP BY user_id, credit_type_id';
$useCases[] = [
    'name' => 'Amount by credit type (variant 1 - credit)',
    'sql' => $sql,
    'data' => renderSqlResult($pdo, '', $sql)
];
// amount by credit type
$sql = 'SELECT user_id, credit_type_id, SUM(ta.amount) AS amount FROM transaction_audit ta JOIN credit c ON c.id = ta.credit_id WHERE expired_at > NOW() GROUP BY user_id, credit_type_id';
$useCases[] = [
    'name' => 'Amount by credit type (variant 2 - transaction audit)',
    'sql' => $sql,
    'data' => renderSqlResult($pdo, '', $sql)
];
// total expired credit
$sql = 'SELECT user_id, SUM(t.amount) AS expired_amount FROM transaction t WHERE t.type = \'' . TransactionTypeEnum::EXPIRATION->value . '\' GROUP BY user_id';
$useCases[] = [
    'name' => 'Total expired credit',
    'sql' => $sql,
    'data' => renderSqlResult($pdo, '', $sql)
];

// expired credit by credit type
$sql = 'SELECT t.user_id, credit_type_id, SUM(t.amount) AS expired_amount FROM transaction t LEFT JOIN transaction_audit ta ON t.id = ta.transaction_id JOIN credit c ON c.id = ta.credit_id WHERE t.type = \'' . TransactionTypeEnum::EXPIRATION->value . '\' GROUP BY t.user_id, credit_type_id';
$useCases[] = [
    'name' => 'Expired credit by credit type',
    'sql' => $sql,
    'data' => renderSqlResult($pdo, '', $sql)
];

// getUsable credit by priority
 $sql = 'SELECT c.user_id, c.id AS credit_id, ct.priority, ct.name AS creditType, c.created_at, c.expired_at, amount
        FROM credit c JOIN credit_type ct ON c.credit_type_id = ct.id
        WHERE (c.expired_at IS NULL OR c.expired_at > NOW()) AND amount > 0
        ORDER BY c.user_id, ct.priority, c.expired_at, c.created_at ASC';
$useCases[] = [
    'name' => 'Usable credits by priority',
    'sql' => $sql,
    'data' => renderSqlResult($pdo, '', $sql)
];

// credits to validate
$sql = "SELECT id, user_id, amount, credit_type_id
            FROM request
            WHERE transaction_id IS NULL 
              AND valid_from IS NOT NULL
              AND valid_from <= NOW()
              AND rollback_at IS NULL
              AND amount > 0";
$useCases[] = [
    'name' => 'Get valid requests with validate_from',
    'sql' => $sql,
    'data' => renderSqlResult($pdo, '', $sql)
];


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







