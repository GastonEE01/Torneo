// Selecciona todos los botones
const options = document.querySelectorAll('.option');
const hiddenInput = document.getElementById('selectedPlatform');

options.forEach(option => {
    option.addEventListener('click', () => {
        const selectedName = option.getAttribute('data-name');
        hiddenInput.value = selectedName; // Actualiza el valor del input oculto
    });
});


