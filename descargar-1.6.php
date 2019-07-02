<?php

// OBJETIVO: volcar la base de datos de todos los productos (o uno en
//   particular) a un fichero XML.
//
// COMO USAR (Resumen):
//   Poner este fichero en la raiz de prestashop y luego en el navegador ir a
//   la URL:
//	- https://www.tiendaprestashopejemplo.es/descargar-1.6.php
//	- https://www.tiendaprestashopejemplo.es/descargar-1.6.php?product_id=123
//
//////////////////////////////////////////////////////
//            LEER MAS INSTRUCCIONES EN README.md!!!
//////////////////////////////////////////////////////

//error_reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

//ini_set
ini_set('max_execution_time', 0);
ini_set('set_memory_limit', -1);

// Leer datos de conexión a la base de datos a partir del fichero
// parameters.php donde se dan dichos datos (en prestashop)
$site_base_path="./";
require($site_base_path . 'config/config.inc.php');
$database_host = _DB_SERVER_;
if (!isset($database_host)) {
  echo "'database_host' no esta definido.";
  echo "No me puedo conectar a la base de datos.";
  exit;
}
$database_user = _DB_USER_;
if (!isset($database_user)) {
  echo "'database_user' no esta definido";
  echo "No me puedo conectar a la base de datos.";
  exit;
}
$database_password= _DB_PASSWD_;
if (!isset($database_password)) {
  echo "'database_password' no esta definido";
  echo "No me puedo conectar a la base de datos.";
  exit;
}
$database_name= _DB_NAME_;
if (!isset($database_name)) {
  echo "'database_name' no esta definido";
  echo "No me puedo conectar a la base de datos.";
  exit;
}

// Create connection
$dbconnect =  new mysqli($database_host, $database_user, $database_password, $database_name );
// Check connection
if ($dbconnect->connect_error) {
  die('Connection failed: ' . $dbconnect->connect_error);
}else{
  //echo "Database connected\n";
}

#// Show available tables in database
#////////////////////////////////////////
#$sql="SHOW TABLES";
#
#if (!($result = mysqli_query($dbconnect,$sql))) {
#  printf("Error: %s\n", mysqli_error($conn));
#}
#while( $row = mysqli_fetch_row( $result ) ){
#  if (($row[0]!="information_schema") && ($row[0]!="mysql")) {
#    echo $row[0]."\r\n";
#  }
#}
#
#
#// NOTA: Es posible que si cuando instalaron el prestashop han puesto un
#// prefijo haya que hacer modificaciones. Por defecto es `ps_`, pero podría ser
#// otro.
if (isset($_GET['product_id'])) {
  $product_id = $_GET['product_id'];
  settype($product_id, 'integer'); // Asegurarse de que es un entero
  $where = "WHERE ps_product.id_product = $product_id";
} else {
  $where = '';
}
$query    = 'SELECT DISTINCT
    ps_product.id_product AS \'product_id\',
    ps_product.ean13 AS \'ean13\',
    ps_product.price AS \'price\',
    ps_product.reference AS \'product_reference\',
    ps_stock_available.quantity AS \'available_stock\',
    ps_manufacturer.name AS \'brand\',
    ps_product.active AS \'active\',
    ps_product_lang.description AS \'description\',

    ps_product.wholesale_price AS \'wholesale_price\',

    ps_product_lang.name AS \'title\',
    ps_product_lang.link_rewrite AS \'productlangrewrite\',
    concat("/", ps_category_lang.link_rewrite, "/", ps_product_lang.link_rewrite) AS \'deeplink\',
    concat("/", ps_image.id_image, "-large_default/", ps_product_lang.link_rewrite, ".jpg") AS \'imagelink\'
FROM
    ps_product
INNER JOIN
    ps_stock_available
    ON ps_stock_available.id_product = ps_product.id_product
INNER JOIN
    ps_manufacturer
    ON ps_manufacturer.id_manufacturer = ps_product.id_manufacturer
INNER JOIN
    ps_product_lang
    ON ps_product_lang.id_product = ps_product.id_product
INNER JOIN
    ps_category_lang
    ON ps_category_lang.id_category = ps_product.id_category_default
INNER JOIN
    ps_image
	ON ps_image.id_product = ps_product.id_product
'. $where .'
GROUP BY
    ps_product.id_product'; //Uso GROUP BY para evitar tener resultados duplicados
$dbresult = mysqli_query($dbconnect, $query);

#// Test para ver que es lo que devuelve la query
#if (mysqli_num_rows($dbresult) > 0) {
#  while($product_row = mysqli_fetch_assoc($dbresult)) {
#    foreach($product_row as $key => $value)
#    {
#      echo $key." = ". $value."</br>";
#    }
#  }
#} else {
#  echo "0 results";
#}
#exit;


// Create a new XML document
$doc = new DomDocument('1.0');
// create root node
$root = $doc->createElement('root');
$root = $doc->appendChild($root);
while ($product_row = mysqli_fetch_assoc($dbresult)) {
  $product_id = $product_row["product_id"]; //Ex: 1
  $product_lang_rewrite = $product_row["productlangrewrite"]; //Ex: 'faded-short-sleeves-tshirt'
  $product = $doc->createElement('product');
  foreach ($product_row as $name=>$value )    {
    $element = $doc->createElement($name, htmlspecialchars($value));
    $product->appendChild($element);
  }
  // Discounts
  $discounts_elmt = $doc->createElement('discounts');
  { // ALL discounts of this product
    $discounts_query = "SELECT
        ps_specific_price.id_specific_price,
        ps_specific_price.id_specific_price_rule,
        ps_specific_price.id_cart,
        ps_specific_price.id_shop,
        ps_specific_price.id_shop_group,
        ps_specific_price.id_currency,
        ps_specific_price.id_country,
        ps_specific_price.id_group,
        ps_specific_price.id_customer,
        ps_specific_price.id_product_attribute,
        ps_specific_price.price,
        ps_specific_price.from_quantity,
        ps_specific_price.reduction,
        ps_specific_price.reduction_tax,
        ps_specific_price.reduction_type,
        ps_specific_price.from,
        ps_specific_price.to
      FROM ps_specific_price
      WHERE ps_specific_price.id_product = $product_id";
    $dbdiscountsresult = mysqli_query($dbconnect, $discounts_query);
    while ($discount_row = mysqli_fetch_assoc($dbdiscountsresult)) {
      $discount = $doc->createElement('discount');
      foreach ($discount_row as $name=>$value )    {
        $element = $doc->createElement($name, htmlspecialchars($value));
        $discount->appendChild($element);
      }
      $discounts_elmt->appendChild($discount);
    }
  }
  $product->appendChild($discounts_elmt);

  // Variants
  $variants = $doc->createElement('variants');
  $variant_query = "SELECT id_product_attribute, reference, price, unit_price_impact, weight FROM ps_product_attribute WHERE ps_product_attribute.id_product = $product_id";
  $dbvariantresult = mysqli_query($dbconnect, $variant_query);
  while ($variant_row = mysqli_fetch_assoc($dbvariantresult)) {
    $variant = $doc->createElement('variant');
    { //ALL attributes of this variant
      $variant_id = $variant_row['id_product_attribute'];
      //variant name
      $query =  "SELECT GROUP_CONCAT(name SEPARATOR ' - ') AS combination FROM ps_attribute_lang WHERE id_lang=(SELECT id_lang FROM ps_lang WHERE iso_code = 'es') AND id_attribute IN (SELECT id_attribute FROM ps_product_attribute_combination WHERE id_product_attribute = $variant_id)";
      $name_query = mysqli_query($dbconnect, $query);
      $name_row = mysqli_fetch_assoc($name_query);
      $variant_name = $doc->createElement('name', $name_row['combination']);
      $variant->appendChild($variant_name);
      //images
      $images = $doc->createElement('images');
      { // ALL images of this variant
        $img_query = "SELECT
            id_image,
            concat(\"/\", id_image, \"-large_default/\", '$product_lang_rewrite', \".jpg\") AS imagelink,
            concat(\"/img/p/\", id_image, \"/\", id_image, \"-large_default\", \".jpg\") AS imagelegacylink
          FROM ps_product_attribute_image WHERE id_product_attribute = $variant_id";
        $dbimgresult = mysqli_query($dbconnect, $img_query);
        while ($image_row = mysqli_fetch_assoc($dbimgresult)) {
          $image = $doc->createElement('image');
          foreach ($image_row as $name=>$value )    {
            $element = $doc->createElement($name, htmlspecialchars($value));
            $image->appendChild($element);
          }
          $images->appendChild($image);
        }
      }
      $variant->appendChild($images);
      //supplier prices
      $supplier_prices_elmt = $doc->createElement('supplier_prices');
      { // ALL supplier prices of this variant
        $supplier_prices_query = "SELECT
            ps_product_supplier.product_supplier_price_te,
            ps_product_supplier.product_supplier_reference,
            ps_supplier.name,
            ps_supplier.id_supplier
          FROM ps_product_supplier
          INNER JOIN
              ps_supplier
              ON ps_product_supplier.id_supplier = ps_supplier.id_supplier
          WHERE ps_product_supplier.id_product_attribute = $variant_id";
        $dbsupplypriceresult = mysqli_query($dbconnect, $supplier_prices_query);
        while ($supplier_price_row = mysqli_fetch_assoc($dbsupplypriceresult)) {
          $supplier_price = $doc->createElement('supplier_price');
          foreach ($supplier_price_row as $name=>$value )    {
            $element = $doc->createElement($name, htmlspecialchars($value));
            $supplier_price->appendChild($element);
          }
          $supplier_prices_elmt->appendChild($supplier_price);
        }
      }
      $variant->appendChild($supplier_prices_elmt);
      //rest of values
      foreach ( $variant_row as $v_name=>$v_value )    {
        $v_element = $doc->createElement($v_name, htmlspecialchars($v_value));
        $variant->appendChild($v_element);
      }
    }
    $variants->appendChild($variant);
  }
  $product->appendChild($variants);

  $root->appendChild($product);
}
$xml_string = $doc->saveXML();
header('Content-type: application/xml');
echo $xml_string;

// Close connection
mysqli_close($dbconnect);
