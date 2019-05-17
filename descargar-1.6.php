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
    ps_product_lang.description AS \'description\',
    ps_product_lang.name AS \'title\',
    concat("https://www.tiendaprestashopejemplo.es/", ps_category_lang.link_rewrite, "/", ps_product_lang.link_rewrite) AS \'deeplink\',
    concat("https://www.tiendaprestashopejemplo.es/", ps_image.id_image, "-large_default/", ps_product_lang.link_rewrite, ".jpg") AS \'imagelink\'
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
#  while($row = mysqli_fetch_assoc($dbresult)) {
#    //echo $row["product_id"];
#    echo implode($row);
#  }
#
#} else {
#  echo "0 results";
#}
#exit;

// Create a new XML document
$doc = new DomDocument('1.0');
// create root node
$root = $doc->createElement('root');
$root = $doc->appendChild($root);
while ($row = mysqli_fetch_assoc($dbresult)) {
  $newRow = $doc->createElement('product');
  foreach ( $row as $name=>$value )    {
    $element = $doc->createElement($name, htmlspecialchars($value));
    $newRow->appendChild($element);
  }
  $root->appendChild($newRow);
}
$xml_string = $doc->saveXML();
header('Content-type: application/xml');
echo $xml_string;

// Close connection
mysqli_close($dbconnect);
