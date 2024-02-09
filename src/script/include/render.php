<?php

declare(strict_types=1);

require_once __DIR__ . '/db-connection.php';

function renderTable(PDO $pdo, string $table, string $sql): string {
    $query = $pdo->prepare($sql);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($results) === 0) {
        return '';
    }

    $html = sprintf('<h2>%s</h2>', $table);
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