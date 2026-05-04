document.querySelectorAll('[data-image-preview]').forEach((input) => {
    const target = document.querySelector(input.dataset.imagePreview);

    if (!target) {
        return;
    }

    input.addEventListener('change', () => {
        target.innerHTML = '';

        Array.from(input.files || []).forEach((file) => {
            if (!file.type.startsWith('image/')) {
                return;
            }

            const image = document.createElement('img');
            image.src = URL.createObjectURL(file);
            image.alt = file.name;
            image.onload = () => URL.revokeObjectURL(image.src);
            target.appendChild(image);
        });
    });
});
