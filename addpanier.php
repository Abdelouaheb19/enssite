

<?php 
require '_header.php'; 
require 'header.php'; 


if(isset($_GET['id']))
{
$product = $DB->query('SELECT id FROM products WHERE id=:id',array('id' => $_GET['id']));
if(empty($product))
{
    die("Ce produit n'existe pas");
}

$panier->add($product[0]->id);
die('Le produit a été bien ajouté à votre panier<a href="javascript:history.back()"> Retourner au Catalogue</a>');

}
else{

    die("Vous n'avez pas ajoutez le prooduits");
 
}





?>