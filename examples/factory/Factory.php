<?php
/**
 *
 */
class Factory
{
  public function getPage($type) {
    switch($type){ 
      case "pdf": return new Pdf;
      case "docx": return new Docx;
      default: throw new Exception("Error type");
    }
  }
}

?>
