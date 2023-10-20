

<?php

include_once("class.inc/BDD.class-inc.php");
include_once("class.inc/stopwords.php");
include_once("class.inc/moteur.php");
require 'header.php';

$base = mysqli_connect ('localhost', 'root', '','tuto');
$autocompletion = new autoCompletion("class.inc/autocompletion/autocompletion.php", "#moteur", "autosuggest", "words", true, 5, 0, false, true);

if(isset($_GET) && !empty($_GET['q'])) {
    $moteur = new moteurRecherche(stripslashes($_GET['q']), 'products', 'regexp', $stopwords);
    $colonnesWhere = array('id','name', 'price','Indexation');
    $moteur->moteurRequetes($colonnesWhere);
	

}

if(isset($moteur)) {
 
		echo'<br/><br/>';
		
    echo '<h3>Resultats de la recherche : <em>'.$moteur->requete.'</em></h3>';
	echo'<br/><br/>';

	function display($requete, $nbResults, $mots) {
		$base = mysqli_connect ('localhost', 'root', '','tuto');
		if($nbResults == 0) {
			 
			echo "<p>Aucun resultat, veuillez effectuer une autre recherche !</p>";    
		} else { 
			
			
			$affichageResultats = new affichageResultats();
			echo $affichageResultats->nbResultats();
			
			
			$nb = 0;
			
					

			if(isset($_GET['p'])) {
			
				$nb = $nb + (10 * ($_GET['p'] - 1));
			}
			
			while(($key = mysqli_fetch_assoc($requete))) {
				$nb++; 

				foreach($key as $k => $v) {
					 $key[$k] = utf8_encode($v);
				}

				
				$texte  = "<div class='results' id='".$nb."'>\n";
				$texte .="\t<img src='img/".$key['id'].".jpg'>";
				$texte .= "\t<h3>".$nb." - ".$key['name']."</h3>\n";
				$texte .= "\t<p>".$key['price']."</p>\n";
		        //$texte.="\t<p>".$key['Indexation']."</p>\n";
		       // $texte.= '<a href="cours/'.$key['file'].'" target="blank">consulter</a>';
				$texte .= "</div>\n";
               
			
				$surlignage = new surlignageMot($mots, $texte);
				echo $surlignage->contenu;
				
			} 
		}
	} 

  
 
	$limit = 10;
	
	
	if(isset($_GET['p'])) {
		$page = htmlspecialchars($_GET['p']);
	} else {
		$page = 0;
	}
	

	$moteur->moteurAffichage('display', '', array(true, $page, $limit, true));
	
	
	
	$moteur->moteurPagination($page, 'p');
	

	$autocompletion->autoComplete(stripslashes($_GET['q']));
}
?>


     


</div>

<div class="contents">

</div>
<div class="contents">

</div>
<script src="js/meny.js"></script>
<script>