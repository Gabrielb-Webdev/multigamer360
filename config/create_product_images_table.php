<?php
$pdo = new PDO('mysql:host=localhost;dbname=multigamer360', 'root', '');
$sql = file_get_contents('config/create_product_images_table.sql');
$pdo->exec($sql);
echo 'Tabla product_images creada correctamente';
