<?php
function get_categories(mysqli $conn): array
{
    $result = $conn->query('SELECT id, name FROM categories ORDER BY name ASC');
    $categories = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[(int)$row['id']] = $row['name'];
        }
    }

    return $categories;
}
