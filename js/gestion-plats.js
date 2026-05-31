document.addEventListener('DOMContentLoaded', function () {

    // Ouvrir modal ajout
    const btnOuvrirAjout = document.getElementById('btn-ouvrir-ajout');
    if (btnOuvrirAjout) {
        btnOuvrirAjout.addEventListener('click', function () {
            ouvrirModalGP('modal-ajout');
        });
    }

    // Ouvrir modal modification avec les données du plat
    document.querySelectorAll('.btn-edit-plat').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('modif-id').value          = btn.dataset.id;
            document.getElementById('modif-nom').value         = btn.dataset.nom;
            document.getElementById('modif-description').value = btn.dataset.description;
            document.getElementById('modif-prix').value        = btn.dataset.prix;
            document.getElementById('modif-categorie').value   = btn.dataset.categorie;
            document.getElementById('modif-allergenes').value  = btn.dataset.allergenes;
            document.getElementById('modif-image').value       = btn.dataset.image;
            ouvrirModalGP('modal-modif');
        });
    });

    // Fermer en cliquant sur l'overlay
    document.querySelectorAll('.gp-modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) fermerModalGP(overlay.id);
        });
    });
});

function ouvrirModalGP(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('actif');
}

function fermerModalGP(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('actif');
}
