document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modal-modif');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) fermerModal();
        });
    }
});

function ouvrirModal(email, nom, prenom, adresse, telephone, role) {
    document.getElementById('modal-email').value     = email;
    document.getElementById('modal-nom').value       = nom;
    document.getElementById('modal-prenom').value    = prenom;
    document.getElementById('modal-adresse').value   = adresse;
    document.getElementById('modal-telephone').value = telephone;
    document.getElementById('modal-role').value      = role;
    document.getElementById('modal-modif').classList.add('actif');
}

function fermerModal() {
    document.getElementById('modal-modif').classList.remove('actif');
}
