
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Créer un site e-commerce</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.13.0/css/all.css" />
</head>
<body>
  <!-- Barre de navigation -->
  <nav class="nav">
    <h1>Ecommerce</h1>
    <div class="onglets">
      <p class="link">Nouveautés</p>
      <p class="link">Homme</p>
      <p class="link">Femme</p>
      <p class="link">Enfant</p>
      <p class="link">Cadeaux</p>
      <form>
      <input type="search" placeholder="Rechercher"  value="<?php $page ="search.php"; if(isset($_GET['q'])) { echo htmlspecialchars($_GET['q']); } ?>"name="q" id="moteur"/>
			<input type="submit" value="Envoyer"  /> 
      </form>
      <ul class ="panier">
  <li class="items"><span>13</span></li>
  <li class="total"><span>1320 DZ</span></li>
  </ul>
      <p><i class="far fa-heart"></i></p>
     <a href="panier.php"><p><i class="fas fa-shopping-cart"></i></p></a> 
    </div>
  </nav>

  
  <!-- Fin de la barre de navigation -->
  
  <!-- Header -->
   <header>
     <h1>Wellcome votre boutique.</h1>
    <a href="index.php"> <button>Naviguer <i class="fas fa-paper-plane"></i></button></a>
   </header>