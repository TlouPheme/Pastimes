<?php
/*
    GROUP MEMBERS:
    Tlou Pheme - ST10177726
    Mahlatse Mphelo - ST10449570

    Declaration: This code is our own group work except where external sources are referenced.
*/

$databaseName = 'ClothingStore';

$serverConnection = new mysqli('localhost', 'root', '');

if ($serverConnection->connect_error) {
    die('Connection failed: ' . $serverConnection->connect_error);
}

$sqlFile = __DIR__ . '/myClothingStore.sql';

if (!is_file($sqlFile)) {
    die('myClothingStore.sql was not found.');
}

$sql = file_get_contents($sqlFile);

if (!$serverConnection->multi_query($sql)) {
    die('Could not load ClothingStore SQL: ' . $serverConnection->error);
}

do {
    if ($result = $serverConnection->store_result()) {
        $result->free();
    }
} while ($serverConnection->more_results() && $serverConnection->next_result());

if ($serverConnection->errno) {
    die('SQL load stopped with error: ' . $serverConnection->error);
}

echo 'ClothingStore database recreated successfully.';
