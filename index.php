<!DOCTYPE html>
<html lang="ru" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Aleksandr Stankevich hub - web programmer</title>
    <link rel="stylesheet" href="/css/style.css">
  </head>
  <body>
    <div class="card">
      <div class="content">
        <div class="imgBx">
          <img class="mePhoto" src="https://xn----8sbfka6a6afpgo3a.xn--p1ai/local/templates/recept-vkusa/images/me.jpg" alt="me">
        </div>
        <h2>
          Александр Станкевич <br><span>веб-программист</span>
        </h2>
      </div>
      <?php
      // Путь, где смотрим примеры
      $dir = $_SERVER['DOCUMENT_ROOT'].'/examples';
      // Сканируем
      $files = scandir($dir);
      // Формируем ссылки
      $links = array_reduce(
        $files,
        function ($result, $elem) {
          if ($elem != '.' && $elem != '..') {
            $result[] = [
              'NAME' => ucfirst($elem),
              'LINK' => '/examples/'.$elem.'/',
            ];
          }

          return $result;
        },
        []
      );

      // echo "<br>";
      // print_r($links);
      ?>
      <?if($links):?>
      <ul class="navigation">
        <?foreach ($links as $link):?>
        <li>
          <a href="<?=$link['LINK']?>">
            <ion-icon name="code-working-outline"></ion-icon>
            <?=$link['NAME']?>
          </a>
        </li>
        <?endforeach;?>
      </ul>
      <div class="toggle">
        <ion-icon name="arrow-down-outline"></ion-icon>
      </div>
      <?endif;?>
    </div>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <script type="text/javascript">
      const card = document.querySelector('.card');
      const cardtoggle = document.querySelector('.toggle');

      cardtoggle.onclick = function() {
        card.classList.toggle('active');
      }
    </script>
  </body>
</html>
