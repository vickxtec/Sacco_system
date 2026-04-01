<?php
$c = new mysqli('localhost', 'root', '', 'sacco_db');
if ($c->connect_error) { echo 'connect failed: ' . $c->connect_error; exit(1); }
$res = $c->query('SHOW TABLES');
$tables = [];
while ($row = $res->fetch_array()) { $tables[] = $row[0]; }
foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $q = $c->query("SHOW CREATE TABLE $table");
    $r = $q->fetch_assoc();
    echo $r['Create Table'] . "\n\n";
}
