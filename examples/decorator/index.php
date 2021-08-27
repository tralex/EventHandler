<?php
//автозагрузка классов
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

$apple = new apple;
$redApple = new ApplePropertiesDecorator($apple);
echo $apple->getFruit();
echo "<br><br>";
echo $redApple->getFruit();
echo "<br><br>";
$banana = new Banana;
echo $banana->getFruit();
echo "<br><br>";
$smashedBanana = new BananaPropertiesDecorator($banana);
echo $smashedBanana->getFruit();
 ?>
