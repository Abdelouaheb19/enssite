<?php
if(isset($_GET['q']) && !empty($_GET['q'])) {
	$query = htmlspecialchars(stripslashes($_GET['q']));

	
	$table	 = htmlspecialchars($_GET['t']);
	$field	 = htmlspecialchars($_GET['f']);
	$type	 = htmlspecialchars($_GET['type']);
	$encode	 = htmlspecialchars($_GET['e']);

	if(is_numeric($_GET['l'])) {
		$limitS  = htmlspecialchars($_GET['l']);
	} else {
		$limitS = 5;	
	}
	
	if($type == 0 || $type > 1) {
		$arg = "";
	} else {
		$arg = "%";	
	}

	include_once("../BDD.class-inc.php");
				$base = mysqli_connect ('localhost', 'root', '','tuto');

    $requeteSQL = "SELECT DISTINCT ".$field." FROM ".$table." WHERE ".$field." LIKE '".$arg.mysqli_real_escape_string($query)."%' ORDER BY ".$field." ASC, idindex DESC LIMIT 0 , ".$limitS."";
    
	
    $results = mysqli_query($base,$requeteSQL) or die("Erreur : ".mysqli_error());
 
    while($donnees = mysqli_fetch_assoc($results)) {
		$mots = $donnees[$field];
		if(preg_match("#([ ]+)#", $mots)) {
			$mots = '"'.$mots.'"';
		}
	
        if($encode == "utf-8" || $encode == "utf8" || $encode == "UTF-8" || $encode == "UTF8") {
			echo utf8_encode($mots)."\n";
		} else {
			echo $mots."\n";	
		}
    }
}
	
?>