CREATE TABLE Usuario
(
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario   VARCHAR(50)  NOT NULL,
    contrasenia      VARCHAR(255) NOT NULL,
    email            VARCHAR(255) NOT NULL,
    Path_img_perfil  VARCHAR(255),
    plataformaStream VARCHAR(255) NULL,
    activo           BOOLEAN DEFAULT FALSE,
    token            INT,
    banner           VARCHAR(255)
);


