<?php 
require 'header.php'; ?>
<?php 
require '_header.php'; ?>


<div class="checkout">
    <div class="title">
        <div class="warp">
            <h2 class="first">shopping card</h2>
           
        </div>
    </div>

</div> 


<div class="cards">
      
      <div class="card">
          <div class="table">
            <table  >
              <tr div="row">
                <th>Product name</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Sub Total</th>
            <th>Action</th>
            </tr>
            <?php 
           /* $ids= array_keys($_SESSION['panier']);
           $products = $DB->query('SELECT * FROM products WHERE id IN ('.implode (',',$ids).')'); 
            foreach($products as $product):
*/
           ?>


            <tr >
                <td><a href="" class="img"><img src="img/2.jpg" ></a></td>
                <td><?= number_format($product->price,2,',',''); ?> DZ </td>
                <td> 1</td>
                <td> <?= number_format($product->price * 1.19,2,',',''); ?> DZ</td>
                <td> </td>
            </tr>
         
 
        </table>
        <?php/* endforeach;*/ ?>
        
       
           </div>
       </div>
    </div>

   
<?php 
require 'footer.php'; ?>

