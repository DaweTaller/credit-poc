<?php

declare(strict_types=1);

require_once __DIR__ . '/db-connection.php';

function renderSqlResult(PDO $pdo, string $name, string $sql): string {
    $query = $pdo->prepare($sql);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($results) === 0) {
        return '';
    }

    $html = sprintf('<h2>%s</h2>', $name);
    $html .= '<table class="table table-striped">';
    $html .= '<tr>';
    foreach ($results[0] as $columnName => $value) {
        $html .= sprintf('<th>%s</th>', $columnName);
    }
    $html .= '</tr>';
    foreach ($results as $row) {

        $html .= '<tr>';

        foreach ($row as $value) {
            $html .= sprintf('<td>%s</td>', $value);
        }

        $html .= '</tr>';
    }

    $html .= '</table>';

    return $html;
}


function renderResetDataForm() { ?>
    <form action="script/form-submit.php" method="post">
        <div class="form-group">
            <input type="submit" class="btn btn-danger" formnovalidate name="reset-data" value="Reset data" />
        </div>
    </form>

<?php }

function renderGoToUseCasesForm() { ?>
	<form action="use-cases.php" method="post">
		<div class="form-group">
			<input type="submit" class="btn btn-primary" formnovalidate name="go-to-use-cases" value="Go to use-cases" />
		</div>
	</form>

<?php }
function renderGoToAuditLogForm() { ?>
	<form action="audit-log.php" method="post">
		<div class="form-group">
			<input type="submit" class="btn btn-primary" formnovalidate name="go-to-audit-log" value="Go to audit-log" />
		</div>
	</form>

<?php }

function renderAddCreditForm(PDO $pdo) { ?>
    <form action="script/form-submit.php" method="post">
        <div class="form-group">
            <label for="user">User*</label>
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
            <label for="creditType">Credit type*</label>
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
                                '(expiration: %s%s%s)',
                                $expirationInDaysString,
                                $expiredAtString !== null && $expirationInDaysString !== null ? ' till ' : '',
                                $expiredAtString !== null ? $expiredAtString : ''
                            ) : '',
					);

                    echo sprintf('<option value="%s">%s</option>', $creditType['id'], $optionString);
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="amount">Amount*</label>
            <input name="amount" min="1" required type="number" class="form-control" id="amount" placeholder="Amount">
        </div>
		<div class="form-group">
			<label for="validFrom">Valid from</label>
			<input name="validFrom" type="datetime-local" class="form-control" id="validFrom" placeholder="Valid from">
		</div>
        <div class="form-group">
            <input type="submit" class="btn btn-primary pull-right" name="add-credit" value="Add credit" />
        </div>
    </form>
<?php }

function renderUseCreditForm(PDO $pdo) { ?>
    <form action="script/form-submit.php" method="post">
        <div class="form-group">
            <label for="user">User*</label>
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
            <label for="amount">Amount*</label>
            <input name="amount" min="1" required type="number" class="form-control" id="amount" placeholder="Amount">
        </div>
        <div class="form-group">
            <input type="submit" class="btn btn-primary pull-right" name="use-credit" value="Use credit" />
        </div>
    </form>
<?php }

function renderExpireCreditForm() { ?>
    <form action="script/form-submit.php" method="post">
        <div class="form-group">
            <label for="creditId">Credit Id*</label>
            <input name="creditId" min="1" required type="number" class="form-control" id="creditId" placeholder="Credit Id">
        </div>
        <div class="form-group">
            <input type="submit" class="btn btn-primary pull-right" name="expire-credit" value="Expire credit" />
        </div>
    </form>
<?php }

function renderValidFromNowForm() { ?>
	<form action="script/form-submit.php" method="post">
		<div class="form-group">
			<label for="requestId">Request id*</label>
			<input name="requestId" min="1" required type="number" class="form-control" id="requestId" placeholder="Request id">
		</div>
		<div class="form-group">
			<input type="submit" class="btn btn-primary pull-right" name="valid-from" value="Valid from now" />
		</div>
	</form>
<?php }


function renderAuditLogForm(PDO $pdo) { ?>
	<form action="audit-log.php" method="get">
		<div class="form-group">
			<label for="user">User</label>
			<select name="userId" id="user" class="form-control">
				<option value=""></option>
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
			<label for="transactionId">Transaction id</label>
			<input name="transactionId" min="1" type="number" class="form-control" id="transactionId" placeholder="Transaction id">
		</div>
		<div class="form-group">
			<label for="creditId">Credit Id</label>
			<input name="creditId" min="1" type="number" class="form-control" id="creditId" placeholder="Credit Id">
		</div>
		<div class="form-group">
			<input type="submit" class="btn btn-primary pull-right" name="audit-log" value="Show audit" />
		</div>
	</form>
<?php }

function renderAddTransactionsForm(PDO $pdo) { ?>
	<form action="script/form-submit.php" method="post" class="form-inline">
		<div class="form-group mb-2">
			<label for="transactionsCount">Count*</label>
			<input name="transactionsCount" step="1" min="1" required type="number" class="form-control" id="transactionsCount" placeholder="Transaction count">
		</div>
		<div class="form-group mb-2">
			<label for="minCredit">Min credit*</label>
			<input name="minCredit" step="1" required type="number" class="form-control" id="minCredit" placeholder="Min credit">
		</div>
		<div class="form-group mb-2">
			<label for="maxCredit">Max credit*</label>
			<input name="maxCredit" step="1" required type="number" class="form-control" id="maxCredit" placeholder="Max credit">
		</div>
		<input type="submit" class="btn btn-primary mb-2" name="generate-transactions" value="Generate" />
	</form>
<?php }
