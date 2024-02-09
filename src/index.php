<?php
require_once __DIR__ . '/script/include/db-connection.php';
require_once __DIR__ . '/script/include/render.php';
?>

<html>
	<header>
        <title>Credit - PoC</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    </header>
	<body>
		<?php echo renderTable($pdo, 'credit'); ?>
		<hr>
		<?php echo renderTable($pdo, 'transaction'); ?>
		<hr>
		<?php echo renderTable($pdo, 'credit_type'); ?>
		<hr>
		<?php echo renderTable($pdo, 'credit_transaction'); ?>
    </body>
</html>
