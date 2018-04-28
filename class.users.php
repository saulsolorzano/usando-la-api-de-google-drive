<?php

// Nuestra nueva clase `USER` extiende la funcionalidad de DBHandler
class User extends DBHandler {
    // Declaramos nuestra tabla arriba, para no tener que estar cambiandola
    // en cada función
    public $table = '{TABLA}';
    //...
    // Aquí van otras funciones del directorio
    //...

    /**
     * Función para traernos todos los datos de una tabla
     * @return array todos los datos
     */
    public function getAllData() {
        $stmt = $this->getInstance()->prepare('SELECT * FROM '.$this->table);
        $stmt->execute();

        $result = $stmt->fetchAll();

        return $result;
    }
    // Acepta como parámetros el correo y
    // todos los tamaños de imágenes
    public function UpdateAllUserPictures($user_email, $picture_full, $picture_cropped, $picture_tiny) {
        $stmt = $this->getInstance()->prepare('UPDATE '.$this->table.' SET picture_full = :picture_full, picture_cropped = :picture_cropped, picture_tiny = :picture_tiny WHERE email = :email');
        $stmt->execute(array(
            ':email'   => $user_email,
            ':picture_full' => $picture_full,
            ':picture_cropped' => $picture_cropped,
            ':picture_tiny' => $picture_tiny
        ));     
    }
}