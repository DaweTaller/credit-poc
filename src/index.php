<?php
require_once __DIR__ . '/script/include/db-connection.php';
require_once __DIR__ . '/script/include/render.php';
require_once __DIR__ . '/script/include/functions.php';
require_once __DIR__ . '/script/include/forms.php';
?>

<html>
	<header>
        <title>Credit - PoC</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    </header>
	<body>
		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-12">
					<br>
					<?php renderResetDataForm() ?>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<h2>Add credit</h2>
					<?php renderAddCreditForm($pdo) ?>
				</div>
				<div class="col-md-6">
					<h2>Use credit</h2>
                    <?php renderUseCreditForm($pdo) ?>
				</div>
			</div>

			<hr>
			<div class="row">
				<div class="col-md-6">
					<?php echo renderSqlResult($pdo, 'credit', 'SELECT * FROM credit ORDER BY created_at ASC'); ?>
				</div>
				<div class="col-md-6">
					<?php echo renderSqlResult($pdo, 'credit_type', 'SELECT * FROM credit_type ORDER BY created_at ASC'); ?>
				</div>
			</div>
			<hr>
			<div class="row">
				<div class="col-md-6">
					<?php echo renderSqlResult($pdo, 'transaction_audit', 'SELECT * FROM transaction_audit ORDER BY created_at ASC'); ?>
				</div>
				<div class="col-md-6">
					<?php echo renderSqlResult($pdo, 'transaction', 'SELECT * FROM transaction ORDER BY created_at ASC'); ?>
				</div>
			</div>
		</div>
    </body>
</html>
