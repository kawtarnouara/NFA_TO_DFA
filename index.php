<!DOCTYPE HTML>
<html>
<!-- 
Ce projet est réalisé par:

Fouad Marzouki
Kawtar Nouara
Mehdi Lamrini
-->
<head>
  <title>NFA to DFA</title>
  <meta name="description" content="website description" />
  <meta name="keywords" content="website keywords, website keywords" />
  <meta http-equiv="content-type" content="text/html; charset=windows-1252" />
  <link rel="stylesheet" type="text/css" href="style.css" />
  <link rel="stylesheet" type="text/css" href="style/style.css" />


</head>

<body>
  <div id="main">
    <div id="header">
      <div id="logo">
        <div id="logo_text">
          <!-- class="logo_colour", allows you to change the colour of the text -->
          <h1>Déterminisation d'un automate <a><span class="logo_colour">non déterministe</span></a></h1>
         
        </div>
      </div>
     
    </div>
   
    <div id="site_content">
      <div id="sidebar_container">
        <div class="sidebar">
          <div class="sidebar_top"></div>
          <div class="sidebar_item">
            <!-- insert your sidebar items here -->
            
          </div>
          <div class="sidebar_base"></div>
        </div>
        <div class="sidebar">
          <div class="sidebar_top"></div>
          <div class="sidebar_item">
        
          </div>
          <div class="sidebar_base"></div>
        </div>
        <div class="sidebar">
          <div class="sidebar_top"></div>
          <div class="sidebar_item">
            
           
          </div>
          <div class="sidebar_base"></div>
        </div>
      </div>
      <div id="content">
        <?php

use Nette\Diagnostics\Debugger;
use Nette\Application\UI;
use Nette\Http;
use Nette\Http\FileUpload;

require_once __DIR__ . '/3rd-party/Nette/loader.php';
require_once __DIR__ . '/NFA_DFA/Automate.php';
require_once __DIR__ . '/NFA_DFA/Etat.php';


?>

<br/><br/><br/><br/>

<p> Pour déterminiser un automate non déterministe, veuillez joindre un fichier .txt 
  contenant votre automate, en respectant le format suivant </p>
<p align='center'><img src="modele.jpg"/></p>
<ul>
  <li>Commencez votre fichier par NFA, suivi des symboles du vocabulaire séparés par un espace.</li>
  <li> Retournez à la ligne.
  <li> Pour chaque état de votre NFA, écrivez les états d'arrivée par les symboles
    en respectant l'odre dans lequel vous les avez écrits au début.
  <li>  Ajoutez <span class="finitial">« -> »</span> avant chaque état initial. 
  <li>  Ajoutez <span class="finitial">« * »</span> avant chaque état final. 
  <li> Si aucune transition n'est possible, écrivez <span class="finitial">« - »</span> </li>
  <li> Si plusieurs transitions sont possibles avec le même symbole,séparez les par <span class="finitial">« | »</span> </li>
  <li>Vous pouvez à présent importer votre fichier   <form method='post' enctype='multipart/form-data'>
  <input type='file' name='fichier'></li>
  <br/>  
  <li> Valider l'automate <input type='submit' id='bouton' name='confirmer' Value="Valider" class='btn btn-info'></li>
  
  </form>
  
</ul> 



<?php
if (isset($_POST['confirmer'])){

    try {
      set_time_limit(0);

      $a = Automate::lireFichier( $_FILES['fichier']['tmp_name'] );

?><br/><br/>
<hr class="style2">
      <h2>Automate source </h2>
<?php
      echo"<table class= 'heavyTable'border='1' style='margin: 0px auto;'>";
      echo "<br/>";
      $a->_print();
      echo"</table>";
      echo "<br/>";
?>
<br/>
<hr class="style2">
     <h2>Automate sans  Ɛ-transitions</h2>
<?php
      echo "<br/>";
      echo"<table border='1' class= 'heavyTable' style='margin: 0px auto;'>";
      $a->plusdEpsilon()
        ->_print();
      echo"</table>";
      echo "<br/>";

?>
<br/>
<hr class="style2">
      <div class="tit"><h2>Automate déterministe</h2></div>
<?php
      echo "<br/>";
      echo"<table class= 'heavyTable' border='1' style='margin: 0px auto;'>";
      $a->determiniser()
        ->_print();
        echo"</table>";


?>
<br/><br/>
<hr class="style2">
      <h2>Automate minimal</h2>
<?php
      echo "<br/>";
      echo"<table class= 'heavyTable' border='1' style='margin: 0px auto;'>";
      $a->minimiser()
        ->_print();
        echo"</table>";




    } catch (Exception $e) {
      echo "\nError: {$e->getMessage()}\n\n";
      die();
    }
  //}
  }
?>
      </div>
    </div>
    
</body>
</html>
