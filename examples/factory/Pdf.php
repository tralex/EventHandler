<?php

class Pdf implements Page
{
  public function createPage() {
    echo "new ".__CLASS__.' page<br>';
  }
}
 
 ?>
