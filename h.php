
<?php 
require '_header.php'; ?>

<?php 
var_dump($_SESSION); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

            <table  >
              <tr>
                <th>Product name</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Sub Total</th>
            <th>Action</th>
            </tr>
            <?php 
    $products= $DB->query('SELECT * FROM products WHERE id IN (1,2)'); 
    var_dump($products);
     
     
    ?>
 

            <tr>
                <td><a href="" class="img"><img src="img/1.jpg" ></a> </td>
                <td>100 DZ </td>
                <td> 1</td>
                <td> 100 DZ</td>
                <td> </td>
            </tr>
         
          
        </table>
</body>
</html>
