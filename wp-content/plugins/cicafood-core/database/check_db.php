<?php
$pdo = new PDO('mysql:host=localhost;dbname=cicafood_wp;charset=utf8mb4', 'root', '');
$rows = $pdo->query('SELECT id, slug, name FROM wp_cica_restaurants ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r) echo $r['id'] . ' | ' . $r['slug'] . ' | ' . $r['name'] . PHP_EOL;
