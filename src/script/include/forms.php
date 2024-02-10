<?php

function renderResetDataForm() { ?>
<form action="script/form_submit.php" method="post">
    <div class="form-group">
        <input type="submit" class="btn btn-danger" formnovalidate name="reset-data" value="Reset data" />
    </div>
</form>

<?php }

function renderAddCreditForm(PDO $pdo) { ?>
    <form action="script/form_submit.php" method="post">
        <div class="form-group">
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
        <div class="form-group">
            <label for="creditType">Credit type</label>
            <select name="creditTypeId" id="creditType" class="form-control">
                <?php
                foreach (getCreditTypes($pdo) as $creditType) {
					$expirationInDaysString = $creditType['expiration_in_days']
						? sprintf('%d days', $creditType['expiration_in_days'])
						: null;
                    $expiredAtString = $creditType['expirate_at']
                        ? $creditType['expirate_at']
                        : null;
					$hasExpiration = $expiredAtString !== null || $expirationInDaysString !== null;
					$optionString = sprintf(
                        '%s%s',
                        $creditType['name'],
                        $hasExpiration ?
                            sprintf(
                                '(expiration: %s%s)',
                                $expirationInDaysString,
                                $expiredAtString !== null ? ' or ' . $expiredAtString : ''
                            ) : '',
					);

                    echo sprintf('<option value="%s">%s</option>', $creditType['id'], $optionString);
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="amount">Amount</label>
            <input name="amount" min="1" required type="number" class="form-control" id="amount" placeholder="Amount">
        </div>
        <div class="form-group">
            <input type="submit" class="btn btn-primary pull-right" name="add-credit" value="Add credit" />
        </div>
    </form>
<?php }

function renderUseCreditForm(PDO $pdo) { ?>
    <form action="script/form_submit.php" method="post">
        <div class="form-group">
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
        <div class="form-group">
            <label for="amount">Amount</label>
            <input name="amount" min="1" required type="number" class="form-control" id="amount" placeholder="Amount">
        </div>
        <div class="form-group">
            <input type="submit" class="btn btn-primary pull-right" name="use-credit" value="Use credit" />
        </div>
    </form>
<?php }

function renderExpireCreditForm() { ?>
	<form action="script/form_submit.php" method="post">
		<div class="form-group">
			<label for="creditId">Credit Id</label>
			<input name="creditId" min="1" required type="number" class="form-control" id="creditId" placeholder="Credit Id">
		</div>
		<div class="form-group">
			<input type="submit" class="btn btn-primary pull-right" name="expire-credit" value="Expire credit" />
		</div>
	</form>
<?php }
