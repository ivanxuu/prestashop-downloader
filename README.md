## OBJETIVO
Volcar la base de datos de todos los productos (o uno en particular) a un fichero XML.

## COMO USAR (Resumen):
  Poner este fichero en la raiz de prestashop y luego en el navegador ir a
  la URL:

    https://www.tiendaprestashopejemplo.es/descargar-1.7.php
    https://www.tiendaprestashopejemplo.es/descargar-1.7.php?product_id=123

  O `descargar-1.6.php` para la versión de prestashop 1.6.

  El navegador devolverá un fichero XML sin formato similar a `result_example.xml` en este mismo directorio.

## NOTAS Y ADVERTENCIAS
 - Este fichero espera ser colocado en la **raiz de prestashop**.  El motivo es para que sepa encontrar correctamente el fichero donde estan los datos de la base de datos (`'app/config/parameters.php'`). En su defecto dará error indicando la variable que falta. Tendrás seguramente que ponerla manualmente modificando este codigo: Ej `$database_host= 'localhost';`
 - Si deseas cambiar los datos de los productos que se van a recuperar, tendrás que modificar la cadena SQL donde se solicita los productos.  O hacer algun tipo de query del estilo `select * FROM ...`
 - Lo he probado en la última versión de prestashop 1.7 y 1.6. Hay un fichero PHP por cada versión.
 - La URL de las imagenes no sale entera en la base de datos, por lo que en la query esta escrito a pelo el dominio de la tienda (Busca la cadena `https://www.tiendaprestashopejemplo.es/`). La verdad que no tengo claro de donde se recupera el dominio de la tienda para haberlo hecho automático. Por lo que **habrá que modificar la URL de la tienda en el fichero!**
 - **En caso de ser otro prefijo distinto a `ps_` habra que modificarlo!**. Podría pasar que tengan un prefijo diferente a `ps_`. Por defecto es `ps_`, pero si lo cambiaron cuando hicieron la instalación de prestashop, pues habrá que modificar la query.
 - Dentro del script, hay varias lineas comentadas que pueden ser utiles para debuggear algo que no funcione.

## REPLICAR SISTEMA DE PRESTASHOP

### USANDO DOCKER:

  Para ejecutar un prestashop de ejemplo usando docker sigue las instrucciones de [Bitnami](https://hub.docker.com/r/bitnami/prestashop#run-the-application-manually)

  Tendras acceso al contenido de prestashop en tu disco curo consultando:

      $ sudo docker volume inspect prestashop_data

  Y ahi puedes poner ficheros o manipular lo que quieras de prestashop.

  Puedes ver los datos de contraseña y datos de la base de datos en el fichero de prestashop `app/config/parameters.php`.

  _NOTA: Yo he usado docker para recrear la versión 1.7._

### USANDO VAGRANT:
  Usa mi otro repo donde hay más instrucciones al respecto: [vagrant-prestashop](https://github.com/ivanxuu/vagrant-prestashop)

  _NOTA: Yo he usado vagrant para recrear la versión 1.6._
