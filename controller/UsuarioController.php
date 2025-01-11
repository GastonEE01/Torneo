<?php
require 'libs/PHPMailer/src/Exception.php';
require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class UsuarioController
{
    private $model;
    private $presenter;
    private $modelPartida;
    private $senderEmailPHPMailer;
    private $generadorQR;

    public function __construct($model, $presenter,$modelPartida,$senderEmailPHPMailer,$generadorQR)
    {
        $this->model = $model;
        $this->presenter = $presenter;
        $this->modelPartida = $modelPartida;
        $this->senderEmailPHPMailer = $senderEmailPHPMailer;
        $this->generadorQR = $generadorQR;

    }

    public function vistaRegistro()
    {
        $this->presenter->render("view/registro.mustache", []);
    }

    public function vistaLogin()
    {
        $this->presenter->render("view/login.mustache", []);
    }

    public function cerrarSesion()
    {
        $sesion = new ManejoSesiones();
        $sesion->cerrarSesion();
        $this->presenter->render("view/login.mustache");
    }

    public function clip() {
        $sidebarContent = file_get_contents("view/template/sidebar.mustache");
        $footerContent = file_get_contents("view/template/footer.mustache");
        $headerContent = file_get_contents("view/template/header.mustache");

        $this->presenter->render("view/clip.mustache", [
            "sidebar" => $sidebarContent,
            "footer" => $footerContent,
            "header" => $headerContent,
        ], [
            'includeHeader' => false,
            'includeFooter' => true,
            'includeSidebar' => true,
        ]);
    }

    public function vistaHome() {
        $sidebarContent = file_get_contents("view/template/sidebar.mustache");
        $footerContent = file_get_contents("view/template/footer.mustache");

        $this->presenter->render("view/home.mustache", [
            "sidebar" => $sidebarContent,
            "footer" => $footerContent,
        ], [
            'includeFooter' => true,
            'includeSidebar' => true,
        ]);
    }

    public function vistaPerfil() {
        $sesion = new ManejoSesiones();
        $id_usuario = $sesion->obtenerUsuarioID();
        $user = $sesion->obtenerUsuario();

        $sidebarContent = file_get_contents("view/template/sidebar.mustache");
        $footerContent = file_get_contents("view/template/footer.mustache");

        $this->presenter->render("view/perfil.mustache", [
            "sidebar" => $sidebarContent,
            "footer" => $footerContent,
            'nombre_usuario' => $user['nombre_usuario'],
            'id' => $id_usuario,
            'Path_img_perfil' => $user['Path_img_perfil'],
            'banner' => $user['banner'],

        ], [
            'includeFooter' => false,
            'includeSidebar' => true,
        ]);
    }

        public  function registro($data){
        $errors = [];

        // Validar email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strpos($data['email'], '@gmail.com') === false) {
            $errors[] = 'El email debe ser un Gmail válido.';
        }

        // Validar que el usuario sea unico
        if ($this->model->verificarNombreUsuario($data['nombre_usuario'])){
            $errors[] = 'El nombre de usuario ya existe.';
        }

        // Validar contraseña
        if (strlen($data['contrasenia']) < 5 || !preg_match('/[A-Za-z]/', $data['contrasenia']) || !preg_match('/[0-9]/', $data['contrasenia'])) {
            $errors[] = 'La contraseña debe tener al menos 5 caracteres, incluyendo al menos 1 letra y 1 número.';
        }

        // Verificar que la contraseña y la repetición coincidan
        if ($data['contrasenia'] !== $data['repeatPassword']) {
            $errors[] = 'Las contraseñas no coinciden.';
        }

        if (!empty($errors)) {
            echo $this->presenter->render('view/registro.mustache', ['errors' => $errors]);
            return;
        }
        $data['contrasenia'] = password_hash($data['contrasenia'], PASSWORD_DEFAULT);
        // Generar un token antes de crear el usuario
        $token = bin2hex(random_bytes(16));
        $userID =  $this->model->crearUsuario($data,$token);
        if ($userID['affected_rows']) {
            $this->senderEmailPHPMailer->sendActivationEmail($userID['user_id'], $data['email'], $token);
        } else {
            echo "Error al registrar el usuario.";
        }
        echo $this->presenter->render('view/login.mustache', ['success' => 'Revisa tu correo para activar tu cuenta.']);
    }
    public function  activarCuenta()
    {
        $token=isset($_GET['token'])?$_GET['token']:null;
        $idUser=isset( $_GET['id'])?$_GET['id']:null;
        if ($idUser!=null){
            $this->model->activarUsuario($idUser,$token);

        }
        echo $this->presenter->render('view/login.mustache');
    }


    public function validarUsuario($formData)
    {
        $email = $formData['email'] ?? null;
        $contrasenia = $formData['contrasenia'] ?? null;

        // Usa el modelo para validar al usuario
        $user = $this->model->loginUser($email, $contrasenia);

        if ($user && $user['activo'] == 1) {
            $sesion = new ManejoSesiones();
            $sesion->iniciarSesion($user);
            $id_usuario = $sesion->obtenerUsuarioID();
            $fotoIMG = $user['Path_img_perfil'] ?? 'Invitado';

            $sidebarContent = file_get_contents("view/template/sidebar.mustache");
            $footerContent = file_get_contents("view/template/footer.mustache");

            // Renderizar la vista principal
            $this->presenter->render("view/home.mustache", [
                "sidebar" => $sidebarContent,
                "footer" => $footerContent,
                'nombre_usuario' => $user['nombre_usuario'],
                'id' => $id_usuario,
                'Path_img_perfil' => $fotoIMG,
            ], [
                'includeFooter' => true,
                'includeSidebar' => true,
            ]);

        } else {
            $this->presenter->render("view/login.mustache", [
                'error' => 'Correo o contraseña incorrectos, o cuenta inactiva'
            ]);
        }
    }

    /*public function cambiarImagen($formData)
    {
        $sesion = new ManejoSesiones();
        $id_usuario = $sesion->obtenerUsuarioID();
        $user = $sesion->obtenerUsuario();
        $nombre_usuario = $user['nombre_usuario'];
        if (isset($_FILES['fotoPerfil']) && $_FILES['fotoPerfil']['error'] === UPLOAD_ERR_OK) {
            $this->model->cambiarFotoPerfil($nombre_usuario, $_FILES['fotoPerfil']);
            vistaPerfil();
            /* $fotoTempPath = $_FILES['fotoPerfil']['tmp_name'];
             $fotoNombre = basename($_FILES['fotoPerfil']['name']);
             $fotoDestino = "public/imagenes/usuarios/" . $fotoNombre;

             // Mover la imagen al directorio destino
             if (move_uploaded_file($fotoTempPath, $fotoDestino)) {
                 // Actualizar la ruta en la base de datos
                 $this->model->cambiarFotoPerfil($fotoNombre);
             } else {
                 // Manejar error al mover la imagen
                 echo "Error al guardar la imagen.";
             }*/
      /*  } else {
            // Manejar error al subir la imagen
            echo "No se recibió ninguna imagen o hubo un error al subirla.";
        }
    }*/

    public function cambiarFotoPerfil()
    {
        $sesion = new ManejoSesiones();
        $id_usuario = $sesion->obtenerUsuarioID();
        $user = $sesion->obtenerUsuario();

        if (!$id_usuario) {
            die("El usuario no está autenticado.");
        }

        if (isset($_FILES['fotoPerfil']) && $_FILES['fotoPerfil']['error'] === UPLOAD_ERR_OK) {
            // Obtener información del archivo
            $archivo = $_FILES["fotoPerfil"]["name"];
            $rutaTemporal = $_FILES["fotoPerfil"]["tmp_name"];

            // Carpeta de destino
            $directorioDestino = $_SERVER['DOCUMENT_ROOT'] . "/Torneo/public/imagenes/usuarios/";

            // Crear un nombre único para el archivo
            $nombreImagen = pathinfo($archivo, PATHINFO_FILENAME);
            $extension = pathinfo($archivo, PATHINFO_EXTENSION);
            $rutaDestino = $directorioDestino . $nombreImagen . "_" . time() . "." . $extension;

            // Mover el archivo
            if (move_uploaded_file($rutaTemporal, $rutaDestino)) {
                $nombreArchivoGuardado = $nombreImagen . "_" . time() . "." . $extension;

                // Actualizar la base de datos
                $this->model->cambiarFotoPerfil($id_usuario, $nombreArchivoGuardado);

                // Redirigir al perfil
                $this->vistaPerfil();
            } else {
                die("Error al mover el archivo al servidor.");
            }
        } else {
            die("No se recibió ninguna imagen o hubo un error al subirla.");
        }
    }


    public function cambiarBanner()
     {
         $sesion = new ManejoSesiones();
         $id_usuario = $sesion->obtenerUsuarioID();
         $user = $sesion->obtenerUsuario();

         if (!$id_usuario) {
             die("El usuario no está autenticado.");
         }

         if (isset($_FILES['fotoBanner']) && $_FILES['fotoBanner']['error'] === UPLOAD_ERR_OK) {
             // Obtener información del archivo
             $archivo = $_FILES["fotoBanner"]["name"];
             $rutaTemporal = $_FILES["fotoBanner"]["tmp_name"];

             // Carpeta de destino
             $directorioDestino = $_SERVER['DOCUMENT_ROOT'] . "/Torneo/public/imagenes/usuarios/";

             // Crear un nombre único para el archivo
             $nombreImagen = pathinfo($archivo, PATHINFO_FILENAME);
             $extension = pathinfo($archivo, PATHINFO_EXTENSION);
             $rutaDestino = $directorioDestino . $nombreImagen . "_" . time() . "." . $extension;

             // Mover el archivo
             if (move_uploaded_file($rutaTemporal, $rutaDestino)) {
                 $nombreArchivoGuardado = $nombreImagen . "_" . time() . "." . $extension;

                 // Actualizar la base de datos
                 $this->model->cambiarBanner($id_usuario, $nombreArchivoGuardado);

                 // Redirigir al perfil
                 $this->vistaPerfil();
             } else {
                 die("Error al mover el archivo al servidor.");
             }
         } else {
             die("No se recibió ninguna imagen o hubo un error al subirla.");
         }
     }

    public function cambiarPlataformaStream() {
        $sesion = new ManejoSesiones();
        $id_usuario = $sesion->obtenerUsuarioID();

        if (!$id_usuario) {
            die("El usuario no está autenticado.");
        }

        if (isset($_POST['plataformaStream'])) {
            $nuevaUrl = $_POST['plataformaStream'];

            $this->model->actualizarPlataformaStream($id_usuario, $nuevaUrl);

            $this->vistaPerfil();
        } else {
            die("No se recibió ninguna URL.");
        }
    }

}
