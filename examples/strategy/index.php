<meta charset="utf-8">
<?php
//автозагрузка классов
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

class Strategy {
  // свойство куда сохраняем объект
  private $strategy;

  function __construct(Transport $strategy){
    $this->strategy = $strategy;
  }
  function getTransport($name){
    return $this->strategy->setTransport($name);
  }
}

$truck = new Strategy(new truck);
echo $truck->getTransport('Грузовик');
echo '<br>';

$plane = new Strategy(new plane);
echo $plane->getTransport('Самолёт');
echo '<br>';

$ship = new Strategy(new ship);
echo $ship->getTransport('Пароход');
echo '<br>';
// var_dump($ship);
// echo '<br>';


?>
