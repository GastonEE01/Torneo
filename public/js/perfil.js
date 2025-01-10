// Enviar formulario automáticamente cuando se selecciona un archivo
document.getElementById('fotoPerfil').addEventListener('change', function () {
    if (this.files && this.files.length > 0) {
        document.getElementById('formCambiarFoto').submit();
    }
});

document.getElementById('fotoBanner').addEventListener('change', function () {
    if (this.files && this.files.length > 0) {
        document.getElementById('formCambiarBanner').submit();
    }
});

document.getElementById('plataformaStream').addEventListener('change', function () {
    document.getElementById('formCambiarPlataformaStream').submit();
});


