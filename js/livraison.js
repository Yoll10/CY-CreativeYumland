function toggleDetails(id) {
    const liste = document.getElementById('details-' + id);
    if (liste.classList.contains('visible')) {
        liste.classList.remove('visible');
    } else {
        liste.classList.add('visible');
    }
}
