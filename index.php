<?php

// Debemos hacer primero la carga de autoload.php
require_once('vendor/autoload.php');

require_once('basic.php');
require_once('class.db.php');
require_once('class.users.php');

// Nueva instancia de nuestra clase
$usuarios = new User();

// Aquí corremos nuestra función y guardamos los datos en la 
// variable `$data`
$data = $usuarios->getAllData();

$client = new Google_Client();
$client->setApplicationName("{{APP}}");
$client->setDeveloperKey("{{KEY}}");
$service = new Google_Service_Drive($client);


function clean($string) {
    // Reemplaza todos los espacios por el dash
    // y convierte el nombre en minúscula
    $string = str_replace(' ', '-', strtolower($string));
    // Con preg_replace usamos una expresión regular
    // para eliminar todos los caracteres que no
    // sean letras y números normales.
    return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
}

/**
 * Función para traernos nuestro archivo
 * @param  object $service el llamado a google
 * @param  string $id      ID completo de nuestro archivo
 * @return object          nuestro archivo
 */
function getFile($service, $id) {
    // $service la creamos arriba, es la nueva
    // instancia de nuestro llamado a google,
    // aquí pasamos el ID
    return $service->files->get($id, array('alt' => 'media'));
}
/**
 * Creamos el archivo
 * @param  object $response llamado de google
 * @param  string $fullName nombre que le colocaremos a nuestro archivo
 */
function createFile($response, $fullName) {
    // Aquí es mejor usar rutas absolutas
    // primero usamos la función fopen como le
    // colocamos 'w+' en nuestro segundo parámetro,
    // escribir y leer php creará el archivo, ya que no existe
    $outHandle = fopen(UPLOAD_DIR . $fullName, "w+");
    // Hacemos el loop por nuestra respuesta,
    // recuerden, 1024 bytes cada vez
    while (!$response->getBody()->eof()) {
        fwrite($outHandle, $response->getBody()->read(1024));
    }
    // Cerramos el proceso
    fclose($outHandle);
}

/**
 * Usamos esta función para determinar la extensión de nuestro archivo
 * @param  string $contentType de cada archivo
 * @return string              nuestra extensión
 */
function getFileExtension($contentType) {
    switch ($contentType) {
        case 'image/gif':
            $ext = '.gif';
            break;
        case 'image/jpeg':
            $ext = '.jpg';
            break;
        case 'image/png':
            $ext = '.png';
            break;
    }
    return $ext;
}

/**
 * Función para cortar y editar nuestras imágenes
 * @param  string $image           Este es el nombre de la imagen
 * @param  string $fileNameCropped Nombre que tendrá la imagen cortada
 * @param  string $fileNameTiny    Nombre que tendrá la imagen pequeña
 * @param  string $ct              El Content Type de nuestra imagen
 */
function editImage($image, $fileNameCropped, $fileNameTiny, $ct) {
    $image = new \Gumlet\ImageResize(UPLOAD_DIR.$image);
    $image->crop(400, 400);
    $image->save(UPLOAD_DIR . $fileNameCropped);
    $image->crop(10, 10);
    $image->save(UPLOAD_DIR . $fileNameTiny);

    switch ($ct) {
        case 'image/jpeg':
            $source_image = imagecreatefromjpeg(UPLOAD_DIR.$fileNameCropped);
            $source_tiny = imagecreatefromjpeg(UPLOAD_DIR.$fileNameTiny);
            break;
        case 'image/png':
            $source_image = imagecreatefrompng(UPLOAD_DIR.$fileNameCropped);
            $source_tiny = imagecreatefrompng(UPLOAD_DIR.$fileNameTiny);
            break;
    }

    if ($source_image === false) {
        return false;
    }

    if ($source_image && imagefilter($source_image, IMG_FILTER_GRAYSCALE) ) {
        imagefilter($source_tiny, IMG_FILTER_GRAYSCALE);
        for ($x=1; $x <=4; $x++){
            imagefilter($source_tiny, IMG_FILTER_GAUSSIAN_BLUR, 999);
        }

        imagefilter($source_tiny, IMG_FILTER_SMOOTH,99);
        imagefilter($source_tiny, IMG_FILTER_BRIGHTNESS, 10);

        switch ($ct) {
            case 'image/jpeg':
                imagejpeg($source_image, UPLOAD_DIR.$fileNameCropped);
                imagejpeg($source_tiny, UPLOAD_DIR.$fileNameTiny);
                break;
            case 'image/png':
                imagepng($source_image, UPLOAD_DIR.$fileNameCropped);
                imagepng($source_tiny, UPLOAD_DIR.$fileNameTiny);
                break;
        }
    } else {
        echo 'Conversion to grayscale failed.';
    }
}

foreach ($data as $member) {
    // name es el nombre de la columna de MySQL
    $filename = clean($member['name']);

    // Lo sé, no es la solución más elegante pero
    // solo lo usaremos esta vez.
    $videoExploted = explode('=', $member['picture']);
    $videoId = $videoExploted[1];

    // Nuestra función creada anteriormente
    $response = getFile($service, $videoId);

    // Llamamos a getHeaders() y pasamos lo que buscamos
    $ct = $response->getHeaders()['Content-Type'][0];
    // Función que no hemos creado aún
    $ext = getFileExtension($ct);

    //Creamos el nombre completo del archivo con nuestro nombre limpio y la extensión
    $fullFileName = $filename . $ext;

    $fileByW = $filename . '-byn' . $ext; //Nombre del archivo blanco y negro
    $fileTiny = $filename . '-tiny' . $ext; // Nombre del archivo pequeño

    createFile($response, $fullFileName);
    editImage($fullFileName, $fileByW, $fileTiny, $ct);
    $usuarios->UpdateAllUserPictures($member['email'], $fullFileName, $fileByW, $fileTiny);
}