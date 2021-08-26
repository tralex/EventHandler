<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

$factory = new Factory;

$pdf = $factory->getPage('pdf');
//$pdf->createPage();

$docx = $factory->getPage('docx');
$docx->createPage();
?>
