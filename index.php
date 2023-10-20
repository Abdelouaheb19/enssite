

   
<?php require '_header.php';?>
<?php 
session_start();
require 'search.php';?>
  <!-- Section principale -->
  <?php  $products = $DB->query(' SELECT * FROM products');?>
  <?php foreach($products as $product): ?>
  <section class="main">
  
  <div class="cards">
      
      <div class="card">
	
        <img src="img/<?= $product->id; ?>.jpg">
                <div class="card-header">
				  
			
        <h4 class="title"><?= $product->name; ?></h4>
        <h4 class="price"><?= number_format($product->price,2,',',''); ?>DZ</h4>
        </div>
     
        <a class="add" href="addpanier.php?id=<?= $product->id; ?>">gift</a>

       <a class="add" href="addpanier.php?id=<?= $product->id; ?>"> <p><i class="fas fa-shopping-cart">Add</i></p></a>
       
      </div> 
     </div>
     </div>
     
            </section>




</div>

<div class="contents">

</div>
<div class="contents">

</div>
<script src="js/meny.js"></script>

  <?php endforeach; ?>
  <!-- Fin de la section principale -->
  
  <!-- Pied de page -->
  <?php var_dump($_SESSION); ?>
  <?php require 'footer.php'; ?>
  
