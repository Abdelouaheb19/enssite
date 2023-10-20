

<?php

class alterTableFullText {
	public function __construct($nomBDD, $table, $colonnes) {
		$base = mysqli_connect ('localhost', 'root', '','tuto');
		$engineSQL = mysqli_query($base,"SHOW TABLE STATUS FROM $nomBDD LIKE '".$table."'");
		$engine = mysqli_fetch_assoc($base,$engineSQL);
		
	
		if($engine["Engine"] != "MyISAM") {
			$MyISAMConverter = mysqli_query($base,"ALTER TABLE $table ENGINE=MYISAM") or die();
		}

		if (is_array($colonnes)) {
			foreach($colonnes as $colonne) {
				$base = mysqli_connect ('localhost', 'root', '','tuto');
				$ifFullTextExists = mysqli_query($base,"SHOW INDEX FROM $table WHERE column_name = '$colonne' AND Index_type = 'FULLTEXT'");
				$fullTextExists = mysqli_fetch_assoc($ifFullTextExists);
				if($fullTextExists['Index_type'] != 'FULLTEXT') {
					$alterTableFullText = mysqli_query($base,"ALTER TABLE $table ADD FULLTEXT($colonne)") or die();
				}
			}
		} else {
			$colonnes = str_ireplace(' ', '', $colonnes);
			$SQLFields = explode(',',$colonnes);
			foreach($SQLFields as $colonne) {
				$base = mysqli_connect ('localhost', 'root', '','tuto');
				$ifFullTextExists = mysqli_query($base,"SHOW INDEX FROM $table WHERE column_name = '$colonne' AND Index_type = 'FULLTEXT'");
				$fullTextExists = mysqli_fetch_assoc($base,$ifFullTextExists);
				if($fullTextExists['Index_type'] != 'FULLTEXT') {
					$alterTableFullText = mysqli_query($base,"ALTER TABLE $table ADD FULLTEXT($colonne)") or die();
				}
			}
		}
	}
}


class moteurRecherche {
	private $tableBDD;			
	private $encode;			
	private $searchType;		
	private $exactmatch;		
	private $colonnesWhere;		
	private $algoRequest;	
	private $request;			
	private $motsExpressions;	
	private $condition;			
	
	private $orderBy;			
	private $limitMinMax;		
	static $limitArg;			
	static $limit;				
	
	public $requete;			
	public $countWords;			
	public $nbResults;			
	static $nbResultsChiffre;	
	public $requeteTotale;		

	public function __construct($champ = '', $table = '', $typeRecherche = 'regexp', $stopWords = array(), $exclusion = '', $encoding = 'utf-8', $exact = true, $accent = false) {
		$this->requete		= $champ;
		$this->tableBDD		= $table;
		$this->encode		= strtolower($encoding);
		$this->searchType	= $typeRecherche;
		$this->exactmatch	= $exact;

		// Suppression des balises HTML (sécurité)
		if($this->encode == 'latin1' || $this->encode == 'Latin1' || $this->encode == 'latin-1' || $this->encode == 'Latin-1') {
			$mb_encode = "ISO-8859-1";
		} elseif($this->encode == 'utf8' || $this->encode == 'UTF8' || $this->encode == 'utf-8' || $this->encode == 'UTF-8') {
			$mb_encode = "UTF-8";
		} else {
			$mb_encode = $encoding;	
		}
		$champ = mb_strtolower(strip_tags($champ), $mb_encode);


		if(preg_match_all('/["]{1}([^"]+[^"]+)+["]{1}/i', $champ, $entreGuillemets)) {
			
			foreach($entreGuillemets[1] as $expression) {
				$results[] = $expression;
			}
			
			$sansExpressions = str_ireplace($entreGuillemets[0],"",$champ);
			$motsSepares = explode(" ",$sansExpressions);		
		} else {
			$motsSepares = explode(" ",$champ);
		}
		
		foreach($motsSepares as $key => $value) {
			
			if(!empty($exclusion)) {
				if(strlen($value) <= $exclusion) {
					$value = '';
				}
			}
			
			if(!empty($stopWords)) {
				if(in_array($value, $stopWords)) {
					$value = '';
				}
			}
			
			if(empty($value)) {
				unset($motsSepares[$key]);
			}
		}
		
		foreach($motsSepares as $motseul) {
			$results[] = $motseul;
		}
		
		
		if(!empty($results)) {
			
			for($y=0; $y < count($results); $y++) {
				$expression = $results[$y];
				
				
				if($accent == false) {
					$recherche[] = htmlspecialchars(trim(strip_tags($expression)));
				} else {
					$withaccent = array('à','á','â','ã','ä','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ','À','Á','Â','Ã','Ä','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ñ','Ò','Ó','Ô','Õ','Ö','Ù','Ú','Û','Ü','Ý');
					$withnoaccent = array('a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','U','U','U','U','Y');
					$recherche[] = str_ireplace($withaccent, $withnoaccent, htmlspecialchars(trim(strip_tags($expression))));
				}
			}
			$this->algoRequest = $recherche; 			
			$this->request = $recherche; 
			$this->countWords = count($recherche,1); 
		} else {
			$recherche = array('');
			$this->algoRequest = $recherche; 
			$this->request = $recherche; 
			$this->countWords = count($recherche,1); 
		}
	}


	private function regexEchap($regex = '([\+\*\?])') {
		if(preg_match($regex, $mot)) {
			$mot = str_ireplace(array('+','*','?'),array('\+','\*','\?'),$mot);
		}
	}


	private function requestKey($val) {
		
		if($this->encode == 'utf8' || $this->encode == "utf-8") {
			$encode = "utf8";			
		} else if($this->encode == 'iso-8859-1' || $this->encode == "iso-latin-1" || $this->encode == "latin1") {
			$encode = "latin1";
		} else {
			$encode = "utf8";
		}

		
		switch($this->searchType) {
	
			case "FULLTEXT":
			case "fulltext":
				foreach($this->request as $this->request[$val]) {
					if(preg_match('/(^[+-?!:;$^])|([+-?!:;^]$)/i',$this->request[$val])) {
						$this->request[$val] = str_ireplace(array("+", "-", "?", "!", ";", ":", "^"),"",$this->request[$val]);
					}
					
			
					if(preg_match("/([+])+/i",$this->request[$val])) {
						$this->request[$val] = str_ireplace(array("+"),array(" "),$this->request[$val]);
						$this->request[$val] = preg_replace('/('.$this->request[$val].')/', '"$1"', $this->request[$val]);
						$this->request[$val] = str_ireplace(array(" "),array("+"),$this->request[$val]);
					}
					
					
					if(preg_match("/([[:blank:]-'])+/i",$this->request[$val])) {
						$this->request[$val] = preg_replace('/('.$this->request[$val].')/i', '"$1"', $this->request[$val]);
					}
					
					
					$this->request[$val] = str_ireplace(array("'"),array("\'"),$this->request[$val]);
					
				
					$valueModif[] = $this->request[$val];
				
					$this->motsExpressions = $valueModif;
				}
				
				if($this->exactmatch == true) {
					$this->request[$val] = implode(' +', $valueModif);
					return " AGAINST(CONVERT(_".$encode." '+".$this->request[$val].")' USING ".$encode.") IN BOOLEAN MODE) ";
				} else {
					$this->request[$val] = implode(' ', $valueModif);
					return " AGAINST(CONVERT(_".$encode." '".$this->request[$val]."' USING ".$encode.") IN BOOLEAN MODE) ";
				}				
				break;

				
		
			case "REGEXP":
			case "regexp":		
				
				$this->motsExpressions = $this->request;				
				if(preg_match("/^[+\?$\*§\|\[\]\(\)]/i",$this->request[$val])) {
					$this->request[$val] = substr($this->request[$val],1,strlen($this->request[$val]));
				}
				if(preg_match("/[+\?$\*§\|\[\]\(\)]$/i",$this->request[$val])) {
					$this->request[$val] = substr($this->request[$val],0,-1);
				}
				if(preg_match("/^[²°]/i",$this->request[$val])) {
					$this->request[$val] = substr($this->request[$val],1,strlen($this->request[$val]));
				}
				
				if($this->exactmatch == true) {
					return " REGEXP CONVERT(_".$encode." '[[:<:]]".addslashes($this->request[$val])."[[:>:]]' USING ".$encode.") ";
				} else {
					return " REGEXP CONVERT(_".$encode." '".addslashes($this->request[$val])."' USING ".$encode.") ";
				}
				break;
			
			
			case "LIKE":
			case "like":
				
				$this->motsExpressions = $this->request;
				if(preg_match("/^[\(]/i",$this->request[$val])) {
					$this->request[$val] = substr($this->request[$val],1,strlen($this->request[$val]));
				}
				if(preg_match("/[\)]$/i",$this->request[$val])) {
					$this->request[$val] = substr($this->request[$val],0,-1);
				}

				return " LIKE CONVERT(_".$encode." '%".addslashes($this->request[$val])."%' USING ".$encode.") ";
				break;

			default:
				
				$this->motsExpressions = $this->request;
				if(preg_match("/^[\(]/i",$this->request[$val])) {
					$this->request[$val] = substr($this->request[$val],1,strlen($this->request[$val]));
				}
				if(preg_match("/[\)]$/i",$this->request[$val])) {
					$this->request[$val] = substr($this->request[$val],0,-1);
				}
				
				return " LIKE CONVERT(_".$encode." '%".addslashes($this->request[$val])."%' USING ".$encode.") ";
				break;
		}
	}

	public function moteurRequetes($colonnesWhere = array()) {

		$this->colonnesWhere = $colonnesWhere;
		
		$operateur = "AND";
	
		$operateurGroupe = "OR";
		
		$nbColumn= count($colonnesWhere,1);
		
	
		if($this->searchType == "LIKE" || $this->searchType == "REGEXP" || $this->searchType == "like" || $this->searchType == "regexp") { // Si recherche "like" ou "regexp"
			$query = " (";
			$query .= $colonnesWhere[0].$this->requestKey(0);
			if($nbColumn > 1) {
				for($nb=1; $nb < $nbColumn; $nb++) {
					$query .= $operateurGroupe." ".$colonnesWhere[$nb].$this->requestKey(0);
				}
			}
			$query .= ") ";
			
			if($this->countWords > 1) {
				for($i=1; $i < $this->countWords; $i++) {
					$query .= $operateur." (".$colonnesWhere[0].$this->requestKey($i);
					if($nbColumn > 1) {
						for($nb=1; $nb < $nbColumn; $nb++) {
							$query .= $operateurGroupe." ".$colonnesWhere[$nb].$this->requestKey($i);
						}
					}
					$query .= ") ";
				}
			}
			
		} else { 
			$colonnesStrSQL = implode(', ',$colonnesWhere);
			$query = " MATCH (".$colonnesStrSQL.")".$this->requestKey(0);
		}

		
		$this->condition = $query;
	}


	public function moteurAffichage($callback = '', $colonnesSelect = '', $limit = array(false, 0, 10, false), $ordre = array(false, "id", "DESC"), $algo = array(false,'algo','DESC','id'), $orderLimitPerso = '') {

		if(empty($colonnesSelect)) {
			$selectColumn = "*";
		} else if (is_array($colonnesSelect)) {
			$selectColumn = implode(", ",$colonnesSelect);
			
		} else {
			$selectColumn = $colonnesSelect;
		}
		
		
		if($limit[0] == true) {
			self::$limitArg = $limit[1];
			self::$limit	= $limit[2];
			
			if(!isset($limit[1])) {
				$limitDeb = 0;
			} else if($limit[1] == 0) {
				$limitDeb = $limit[1] * $limit[2];
			} else if($limit[3] == false) {
				$limitDeb = $limit[1];
			} else {
				$limitDeb = ($limit[1] - 1) * $limit[2];
			}
			$this->limitMinMax = " LIMIT $limitDeb, $limit[2]";
		} else {
			$this->limitMinMax = "";
		}
		
	
		if($algo[0] == true) {
			$base=mysqli_connect("Localhost","root","","tuto");
			$ifColumnExist = mysqli_query($base,"SHOW COLUMNS FROM $this->tableBDD LIKE '".$algo[1]."'");
			$columnExist = mysqli_fetch_row($ifColumnExist);
			if($columnExist[0] != $algo[1]) {
				$addColumn = mysqli_query($base,"ALTER TABLE $this->tableBDD ADD ".$algo[1]." DECIMAL(10,3)");
			}
			
			$colonnesStrSQL = implode(', ',$this->colonnesWhere);
			$requeteType = mysqli_query($base,"SELECT $algo[3], $colonnesStrSQL FROM $this->tableBDD WHERE $this->condition") or die("Erreur : ".mysql_error());
			while($ligne =  mysql_fetch_row($requeteType)) {
				$count = 0;
				for($p=1; $p < count($this->colonnesWhere)+1; $p++) {
					foreach($this->algoRequest as $mots) {
						$count += substr_count(utf8_encode(strtolower($ligne[$p])), strtolower($mots));
					}
				}
				
				$requeteAdd = mysqli_query($base,"UPDATE $this->tableBDD SET $algo[1] = '$count' WHERE $this->condition AND $algo[3] = '$ligne[0]'");
			}
		}

		if($algo[0] == true && $ordre[0] != true) {			
			$this->orderBy = " ORDER BY $algo[1] $algo[2]";
		} else if($algo[0] == true && $ordre[0] == true) {
			
			$this->orderBy = " ORDER BY $algo[1] $algo[2], $ordre[1] $ordre[2]";
		} else {		
		
			if($ordre[0] == true) {
				$this->orderBy = " ORDER BY $ordre[1] $ordre[2]";
			} else {
				$this->orderBy = "";
			}
		}

		if(empty($orderLimitPerso)) {
			$base = mysqli_connect ('localhost', 'root', '','tuto');
			$this->requeteTotale = mysqli_query($base,"SELECT $selectColumn FROM $this->tableBDD WHERE $this->condition $this->orderBy $this->limitMinMax")
			or die("<div>Erreur dans la requête finale, vérifiez bien votre paramétrage complet !</div>");
			// Pour calculer le nombre total de résultats justes
			$this->nbResults = mysqli_query($base,"SELECT count(*) FROM $this->tableBDD WHERE $this->condition")
			or die("<div>Erreur dans le comptage des resultats </div>");
			$compte = mysqli_query($base,"SELECT count(*) FROM $this->tableBDD WHERE $this->condition")
			or die("<div>Erreur dans le comptage des résultats (problème de requête) !</div>");
		} else {
			if($limit[0] == true && $ordre[0] == true) {
				$this->requeteTotale = mysqli_query($base,"SELECT $selectColumn FROM $this->tableBDD WHERE $this->condition $orderLimitPerso $this->orderBy $this->limitMinMax")
				or die("<div>Erreur dans la requête, vérifiez bien votre paramétrage complet !</div>");
			} else if($limit[0] == true && $ordre[0] == false) {
				$this->requeteTotale = mysqli_query($base,"SELECT $selectColumn FROM $this->tableBDD WHERE $this->condition $orderLimitPerso $this->limitMinMax")
				or die("<div>Erreur dans la requête, vérifiez bien votre parametrage complet !</div>");
			} else {
				$this->requeteTotale = mysqli_query($base,"SELECT $selectColumn FROM $this->tableBDD WHERE $this->condition $orderLimitPerso")
				or die("<div>Erreur dans la requête, vérifiez bien votre parametrage complet !</div>");
			}
		
			$this->nbResults = mysqli_query($base,"SELECT count(*) FROM $this->tableBDD WHERE $this->condition $orderLimitPerso");
			$compte = mysqli_query($base,"SELECT count(*) FROM $this->tableBDD WHERE $this->condition $orderLimitPerso");
		}

		$compteTotal = mysqli_fetch_row($compte);
		self::$nbResultsChiffre = $compteTotal[0];

	
		if(!empty($callback)) {

			$nbResultats = mysqli_fetch_row($this->nbResults);

			call_user_func_array($callback, array(&$this->requeteTotale, &$nbResultats[0], &$this->motsExpressions));
		} else {
			echo "<p>Attention ! Aucune fonction de rappel appelée pour afficher les resultats</p>";	
		}
	}

	

	public function moteurPagination($instruction = 0, $param = "page", $NbVisible = 2, $debutFin = 0, $suivPrec = true, $firstLast = true, $arrayAff = array('&laquo; Précédent', 'Suivant &raquo;', 'Première page', 'Dernière page', 'precsuiv', 'current', 'pagination', 'inactif'), $arraySeparateur = array('&hellip;', ' ', ' ', ' ', ' ')) {
		

		$nb_pages = ceil(self::$nbResultsChiffre / self::$limit);

		
		$this->requete = htmlspecialchars($this->requete);
		
		// Numero de page courante (1 par défaut)
		$parametreGetPost = self::$limitArg;
		if(isset($parametreGetPost) && is_numeric($parametreGetPost)) {
			if($parametreGetPost == 0) {
				$current_page = 1;
			} else {
				$current_page = $parametreGetPost;
			}
		} else {
			$current_page = 1;
		}
		
	
		if(($instruction >= 0 && is_numeric($instruction) && $instruction < $nb_pages+1) || $instruction == 0) {
			preg_match_all('#([^=])+([^?&\#])+#i', $_SERVER['QUERY_STRING'], $valueArgs);
			$urlPage = $_SERVER['PHP_SELF'].'?';
			foreach($valueArgs[0] as $arg) {
				$urlPage .= $arg;
				$urlPage = str_replace("&".$param."=".$parametreGetPost, "", $urlPage);
			}
			$urlPage .= "&".$param."=";
			$urlPage = str_replace("?".$param."=".$parametreGetPost."&", "?", $urlPage);
		} else {
			$urlpropre = str_ireplace("?".$param."=".$instruction,"?".$param."=1",$_SERVER['REQUEST_URI']);
			$urlpropre = str_ireplace("&".$param."=".$instruction,"&".$param."=1",$_SERVER['REQUEST_URI']);
			header('location:'.$urlpropre);
		}

		$pagination = '<div class="'.$arrayAff[6].'">';
		
		if($nb_pages > 1) {
	
			if($firstLast == true) {
				for($i=1; $i<=1; $i++) {
					$pagination .= ($current_page==$i) ? '<span class="'.$arrayAff[4].' '.$arrayAff[7].'">'.$arrayAff[2].'</span>' : '<a href="'.$urlPage.$i.'">'.$arrayAff[2].'</a>';
					$pagination .= $arraySeparateur[1];
				}
			}

			if($suivPrec == true) {
				if ($current_page > 1) {
					$pagination .= '<a class="'.$arrayAff[4].'" href="'.$urlPage.($current_page-1).'" title="'.$arrayAff[0].'">'.$arrayAff[0].'</a>';
					$pagination .= $arraySeparateur[3];
				} else {
					$pagination .= '<span class="'.$arrayAff[4].' '.$arrayAff[7].'">'.$arrayAff[0].'</span>';
					$pagination .= $arraySeparateur[3];
				}
			}
			
			
			for($i=1; $i<=$debutFin; $i++) {
				$pagination .= ($current_page==$i) ? '<span class="'.$arrayAff[5].'">'.$i.'</span>' : '<a href="'.$urlPage.$i.'">'.$i.'</a>';
				$pagination .= $arraySeparateur[4];
			}
	
			
			if(($current_page-$NbVisible) > ($debutFin+1)) {
				$pagination .= ' '.$arraySeparateur[0];
			}
			
			
			$start = ($current_page-$NbVisible) > $debutFin ? $current_page-$NbVisible : $debutFin+1;
			$end = ($current_page+$NbVisible)<=($nb_pages-$debutFin) ? $current_page+$NbVisible : $nb_pages-$debutFin;
			for($i=$start; $i<=$end; $i++) {
				$pagination .= $arraySeparateur[2];
				if($i==$current_page) {
					$pagination .= '<span class="'.$arrayAff[5].'">'.$i.'</span>';
				} else {
					$pagination .= '<a href="'.$urlPage.$i.'">'.$i.'</a>';
				}
			}
	
			
			if(($current_page+$NbVisible) < ($nb_pages-$debutFin)) {
				$pagination .= ' '.$arraySeparateur[0];
			}
			
			
			$start = $nb_pages-$debutFin+1;
			if($start <= $debutFin) { $start = $debutFin+1; }
			for($i=$start; $i<=$nb_pages; $i++) {
				$pagination .= $arraySeparateur[4];
				$pagination .= ($current_page==$i) ? '<span class="'.$arrayAff[5].'">'.$i.'</span>' : '<a href="'.$urlPage.$i.'">'.$i.'</a>';
			}
	
			
			if($suivPrec == true) {
				if($current_page < $nb_pages) {
					$pagination .= $arraySeparateur[3];
					$pagination .= ' <a class="'.$arrayAff[4].'" href="'.$urlPage.($current_page+1).'" title="'.$arrayAff[1].'">'.$arrayAff[1].'</a>';
				} else {
					$pagination .= $arraySeparateur[3];
					$pagination .= ' <span class="'.$arrayAff[4].' '.$arrayAff[7].'">'.$arrayAff[1].'</span>';
				}
			}
			
			
			if($firstLast == true) {
				$start = $nb_pages-1;
				for($i=$start+1; $i<=$nb_pages; $i++) {
					$pagination .= $arraySeparateur[1];
					$pagination .= ($current_page==$i) ? '<span class="'.$arrayAff[4].' '.$arrayAff[7].'">'.$arrayAff[3].'</span>' : '<a href="'.$urlPage.$i.'">'.$arrayAff[3].'</a>';
				}
			}
		}
		$pagination .= "</div>"; 
		echo $pagination;
	}

	function limit() {
		return self::$limit;	
	}
	function nbResults() {
		return self::$nbResultsChiffre;	
	}
	
}


class affichageResultats extends moteurRecherche {
	public function nbResultats($illimite = false, $wordsResults = array("resultat", "resultats"), $phrase = 'pour votre recherche', $coord = " a ") {
		if($illimite == true) {
			if(parent::nbResults() < 2) {
				$res = " ".$wordsResults[0];	
			} else {
				$res = " ".$wordsResults[1];
			}
			return "<div class=\"searchNbResults\"><span class='numR'>".parent::nbResults()."</span>".$res." ".$phrase.".</div>";
		} else {
			if(parent::$limitArg == 0) {
				$nbDebut = 1;
				if(parent::nbResults() > parent::$limit) {
					$nbFin = (parent::$limitArg+1) * parent::$limit;
				} else {
					$nbFin = parent::nbResults();
				}
			} else {
				$nbDebut = ((parent::$limitArg-1) * parent::$limit)+1;
				
				if(ceil(parent::nbResults()/(parent::$limit*parent::$limitArg)) != 1) {
					$nbFin = parent::$limitArg * parent::$limit;
				} else {
					$nbFin = parent::nbResults();
				}
			}
			
			if(parent::nbResults() < 2) {
				$res = " ".$wordsResults[0];	
			} else {
				$res = " ".$wordsResults[1];
			}
			return "<div class=\"searchNbResults\"><span class='numR'>".parent::nbResults()."</span>".$res." ".$phrase." (".$nbDebut.$coord.$nbFin.").</div>";
		}
	}
}

class surlignageMot {
	public $contenu;

	// Permet de lancer la fonction sans "echo/print" pour le texte
	public function __get($var) {
		echo $this->contenu;
	}
	
	
	public function __construct($mots, &$contenu, $typeSurlignage = "exact", $exact = true, $typeRecherche = "FULLTEXT") {
		foreach($mots as $mot) {			
			// Permet d'afficher les expressions entre guillemets en gras
			if(preg_match_all('/"(.*)"/i', $mot, $args)) {
				foreach($args[0] as $arg) {
					$mot = str_ireplace(array('"','\"'),array(' ',' '),$mot);
				}
			}
			// Permet d'échapper les caractères du regex
			if(preg_match_all('([\+\*\?\/\'\"\-])', $mot, $args)) {
				foreach($args[0] as $arg) {
					$mot = str_ireplace(array('+', '*', '?', '/', "'", '"'),array('\+','\*','\?', '\/', '\'', ''),$mot);
				}
			}

			
			if($typeSurlignage == "exact" && (($exact == true && $typeRecherche != "LIKE") || ($exact == false && $typeRecherche == "FULLTEXT"))) {
				$contenu = preg_replace('/([[:blank:]<>\(\[\{\'\/].?:?;?,?)('.$mot.')([\)\]\}.,;:!\?\/[:blank:]<>])/i', '$1<b>$2</b>$3', $contenu);
			} else if($typeSurlignage == "exact" && (($exact == true && $typeRecherche == "LIKE") || ($exact == false) && $typeRecherche != "FULLTEXT")) {
				$contenu = preg_replace('/('.$mot.'{1,'.strlen($mot).'})/i', '<b>$1</b>', $contenu);
			} else if($typeSurlignage == "total" || $typeSurlignage == "complet") {
				$contenu = preg_replace('/([[:blank:]<>])([^[:blank:]<>]*'.$mot.'[^[:blank:]<>]*)([[:blank:]])/i', '$1<b>$2</b>$3', $contenu);
			}
			
			
			if(preg_match_all('/<[\/]?[hH]+<b>('.$mot.')<\/b>+/i', $contenu, $args)) {
				foreach($args[0] as $arg) {
					$contenu = preg_replace('/(<[\/]?[a-zA-Z]+)<b>('.$mot.')<\/b(>)+/i', '$1$2$3', $contenu);
				}
			}

		
			if(preg_match_all('/<[\/]?[^hH]?<b>('.$mot.')<\/b>?(^>)*/i', $contenu, $args)) {
				foreach($args[0] as $arg) {
					$contenu = preg_replace('/(<[\/]?[^hH]?)<b>('.$mot.')<\/b>?(^>)*/i', '$1$2$3$4', $contenu);
				}
			}
			
			if(preg_match_all('/(src|href|alt|title|class|id|rel)=["\']{1}[^\'"]+('.$mot.')[^\'"]+["\']{1}/i',$contenu, $args)) {
				foreach($args[0] as $arg) {
					$contenu = preg_replace('/(src|href|alt|title|class|id|rel)*(=["\']{1}[^\'"]*)<b>+('.$mot.')<\/b>+([^\'"]*["\']{1})/i', '$1$2$3$4', $contenu);
				}
			}
		}
		$this->contenu = $contenu;
	}
} 


class autoCompletion {
	private $table;
	private $column;
	private $encode;
	
	public function __construct($urlDestination = "autocompletion.php", $selector = "#moteur", $tableName = "autosuggest", $colName = "words", $multiple = true, $limitDisplay = 5, $type = 0, $autoFocus = false, $create = false, $encode = "utf-8") {
		
		$this->table	= htmlspecialchars($tableName);
		$this->column	= htmlspecialchars($colName);
		$this->encode	= strtolower($encode);
		$base = mysqli_connect ('localhost', 'root', '','tuto');
		if($create == true) {
			$createSQL = "CREATE TABLE IF NOT EXISTS ".$this->table." (
						 idindex INT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						 ".$this->column." VARCHAR(250) NOT NULL)
						 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
			mysqli_query($base,$createSQL) or die("Erreur : ".mysqli_error());
		}
		
		if($autoFocus == true) {
			$autoFocus = "true";
		} else {
			$autoFocus = "false";
		}
		
		$scriptAutoCompletion = "\n".'<script type="text/javascript">'."\n";
		$scriptAutoCompletion.= '$(document).ready(function() {'."\n";
		$scriptAutoCompletion.= "$('".$selector."').autocomplete('".$urlDestination."?t=".$tableName."&f=".$colName."&l=".$limitDisplay."&type=".$type."&e=".$encode."', { selectFirst:".$autoFocus.", max:".$limitDisplay.", multiple:".$multiple.", multipleSeparator:' ', delay:100, noRecord:'' })"."\n";
		$scriptAutoCompletion.=	"})"."\n";
		$scriptAutoCompletion.=	'</script>'."\n";
		echo $scriptAutoCompletion;
	}

	public function autoComplete($field = '', $minLength = 2) {
		$table = $this->table;
		$column = $this->column;

		
		if($this->encode == 'latin1' || $this->encode == 'Latin1' || $this->encode == 'latin-1' || $this->encode == 'Latin-1') {
			$mb_encode = "ISO-8859-1";
		} elseif($this->encode == 'utf8' || $this->encode == 'UTF8' || $this->encode == 'utf-8' || $this->encode == 'UTF-8') {
			$mb_encode = "UTF-8";
		} else {
			$mb_encode = $encoding;	
		}
		$field = mb_strtolower(strip_tags($field), $mb_encode);


		if(preg_match_all('/["]{1}([^"]+[^"]+)+["]{1}/i', $field, $entreGuillemets)) {
			$base = mysqli_connect ('localhost', 'root', '','tuto');

			foreach($entreGuillemets[1] as $expression) {
				$results[] = mysqli_real_escape_string($base,$expression);
			}
		
			$sansExpressions = str_ireplace($entreGuillemets[0],"",$field);
			$motsSepares = explode(" ",$sansExpressions);		
		} else {
			$base = mysqli_connect ('localhost', 'root', '','tuto');

			$motsSepares = explode(" ",mysqli_real_escape_string($base,$field));
		}

		foreach($motsSepares as $key => $value) {
			
			if(!empty($exclusion)) {
				if(strlen($value) <= $exclusion) {
					$value = '';
				}
			}
	
			if(!empty($stopWords)) {
				if(in_array($value, $stopWords)) {
					$value = '';
				}
			}

			if(empty($value)) {
				unset($motsSepares[$key]);
			}
		}
	
		foreach($motsSepares as $motseul) {
			$results[] = $motseul;
		}
		
		
		if(!empty($results)) {
			
			for($y=0; $y < count($results); $y++) {
				$expression = $results[$y];
				$recherche[] = htmlspecialchars(trim(strip_tags($expression)));
			}

			$base = mysqli_connect ('localhost', 'root', '','tuto');

			$selectWords = mysqli_query($base,"SELECT ".$column." FROM ".$table."") or die("Erreur : ".mysql_error());
			$selected = array();
			while($w = mysqli_fetch_assoc($selectWords)) {
				$selected[] = $w[$column];
			}

			foreach($recherche as $word) {
				if(strlen($word) > $minLength) {						
					if(!in_array($word, $selected)) {
						$addWordsSQL = "INSERT INTO ".$this->table." SET ".$this->column." = '".$word."'";
						mysqli_query($base,$addWordsSQL) or die("Erreur : ".mysqli_error());
					}
				}
			}
		}
	}
} 
?>