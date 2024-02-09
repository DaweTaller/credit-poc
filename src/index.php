<?php
require_once __DIR__ . '/script/include/db-connection.php';
require_once __DIR__ . '/script/include/render.php';
require_once __DIR__ . '/script/include/functions.php';
?>

<html>
	<header>
        <title>Credit - PoC</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    </header>
	<body>
		<h2>Add transaction</h2>
		<form action="script/form_submit.php" method="post">
			<div class="form-row">
				<div class="form-group col-md-4">
					<label for="user">User</label>
					<select name="userId" id="user" class="form-control">
                        <?php
                        foreach (getUsers($pdo) as $user) {
                            echo sprintf(
                                '<option value="%s">%s</option>',
                                $user['id'],
                                sprintf(
                                    '%s %s (id: %s)',
                                    $user['first_name'],
                                    $user['last_name'],
                                    $user['id'],
								)
                            );
                        }
                        ?>
					</select>
				</div>
				<div class="form-group col-md-4">
					<label for="creditType">Credit type</label>
					<select name="creditTypeId" id="creditType" class="form-control">
						<?php
							foreach (getCreditTypes($pdo) as $creditType) {
								echo sprintf(
									'<option value="%s">%s</option>',
									$creditType['id'],
									sprintf(
											'%s%s',
											$creditType['name'],
												$creditType['expiration_in_days'] !== null
													? ' (expiration: ' . $creditType['expiration_in_days'] . ' days)' : '')
								);
							}
						?>
					</select>
				</div>
				<div class="form-group col-md-4">
					<label for="amount">Amount</label>
					<input name="amount" type="numeric" class="form-control" id="amount" placeholder="Amount">
				</div>
			</div>
			<input type="submit" class="btn btn-primary" name="send" value="Add transaction" />
			<input type="submit" class="btn btn-primary" name="clear-data" value="Clear data" />
		</form>
		<hr>
		<?php echo renderTable($pdo, 'credit', 'SELECT * FROM credit ORDER BY created_at ASC'); ?>
		<hr>
		<?php echo renderTable($pdo, 'transaction', 'SELECT * FROM transaction ORDER BY created_at ASC'); ?>
		<hr>
		<?php echo renderTable($pdo, 'credit_transaction', 'SELECT * FROM credit_transaction ORDER BY created_at ASC'); ?>
		<hr>
		<?php echo renderTable($pdo, 'credit_type', 'SELECT * FROM credit_type ORDER BY created_at ASC'); ?>
    </body>
</html>
