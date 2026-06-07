/*
UTILITAIRES COOKIES
*/

function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = name + '=' + value + ';expires=' + d.toUTCString() + ';path=/';
}

function getCookie(name) {
    const prefix = name + '=';
    const cookies = document.cookie.split(';');
    for (let i = 0; i < cookies.length; i++) {
        let c = cookies[i].trim();
        if (c.indexOf(prefix) === 0) {
            return c.substring(prefix.length);
        }
    }
    return null;
}


/* 
MODE SOMBRE (cookie)
*/

function toggleDarkMode() {
    const body = document.body;
    const btn = document.getElementById('dark-mode-btn');
    body.classList.toggle('dark-mode');
    if (body.classList.contains('dark-mode')) {
        if (btn) btn.innerHTML = '☀️';
        setCookie('theme', 'dark', 365);
    } else {
        if (btn) btn.innerHTML = '🌙';
        setCookie('theme', 'light', 365);
    }
}

// Applique la classe dark-mode immédiatement pour éviter le flash
(function () {
    const savedTheme = getCookie('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }
})();

// Met à jour l'emoji du bouton une fois le DOM prêt
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('dark-mode-btn');
    if (btn) {
        btn.innerHTML = document.body.classList.contains('dark-mode') ? '☀️' : '🌙';
    }
});


/* 
DOMContentLoaded 
*/

document.addEventListener('DOMContentLoaded', () => {

/*
COMPTEUR DE CARACTÈRES
*/

    document.querySelectorAll('[data-max-length]').forEach(function (field) {
        const counterId = field.getAttribute('data-counter');
        const max = parseInt(field.getAttribute('data-max-length'));
        const counter = document.getElementById(counterId);
        if (!counter) return;
        field.addEventListener('input', function () {
            const remaining = max - field.value.length;
            counter.textContent = remaining + ' caractère' + (remaining <= 1 ? '' : 's') + ' restant' + (remaining <= 1 ? '' : 's');
            counter.style.color = remaining <= 10 ? '#d9534f' : 'var(--gris-texte)';
            counter.style.fontWeight = remaining <= 10 ? 'bold' : 'normal';
        });
    });

/*
AFFICHER / CACHER MOT DE PASSE
*/

    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈';
            } else {
                input.type = 'password';
                btn.textContent = '👁️';
            }
        });
    });


    /* 
    VALIDATION FORMULAIRE INSCRIPTION
    */

    const formInscription = document.getElementById('form-inscription');
    if (formInscription) {

        function showError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const errEl = document.getElementById('err-' + fieldId);
            if (field)  field.classList.add('champ-erreur');
            if (errEl)  errEl.textContent = message;
        }

        function clearError(fieldId) {
            const field = document.getElementById(fieldId);
            const errEl = document.getElementById('err-' + fieldId);
            if (field)  field.classList.remove('champ-erreur');
            if (errEl)  errEl.textContent = '';
        }

        const champsInscription = ['insc-nom', 'insc-prenom', 'insc-email', 'insc-telephone', 'insc-mdp', 'insc-mdp-confirm'];
        champsInscription.forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', function () { validerChampInscription(id); });
        });

        function validerChampInscription(id) {
            const val = (document.getElementById(id) || {}).value || '';
            clearError(id);
            if (id === 'insc-nom' && val.trim().length < 2) {
                showError(id, 'Le nom doit contenir au moins 2 caractères.');
                return false;
            }
            if (id === 'insc-prenom' && val.trim().length < 2) {
                showError(id, 'Le prénom doit contenir au moins 2 caractères.');
                return false;
            }
            if (id === 'insc-email') {
                if (val.trim() === '') { showError(id, 'Ce champ est obligatoire.'); return false; }
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(val)) { showError(id, 'Adresse e-mail invalide.'); return false; }
            }
            if (id === 'insc-telephone' && val.trim() !== '') {
                const telSansEspaces = val.replace(/\s/g, '');
                if (!/^(\+33|0)[0-9]{9}$/.test(telSansEspaces)) {
                    showError(id, 'Numéro de téléphone invalide (ex : 06 12 34 56 78).');
                    return false;
                }
            }
            if (id === 'insc-mdp' && val.length < 6) {
                showError(id, 'Le mot de passe doit contenir au moins 6 caractères.');
                return false;
            }
            if (id === 'insc-mdp-confirm') {
                const mdp = (document.getElementById('insc-mdp') || {}).value || '';
                if (val !== mdp) { showError(id, 'Les mots de passe ne correspondent pas.'); return false; }
            }
            return true;
        }

        formInscription.addEventListener('submit', function (e) {
            let ok = true;
            champsInscription.forEach(function (id) { if (!validerChampInscription(id)) ok = false; });
            if (!ok) {
                e.preventDefault();
                const firstErr = formInscription.querySelector('.champ-erreur');
                if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }


    /*
    VALIDATION FORMULAIRE CONNEXION
    */

    const formConnexion = document.getElementById('form-connexion');
    if (formConnexion) {

        function showErrorConn(fieldId, message) {
            const field = document.getElementById(fieldId);
            const errEl = document.getElementById('err-' + fieldId);
            if (field) field.classList.add('champ-erreur');
            if (errEl) errEl.textContent = message;
        }

        function clearErrorConn(fieldId) {
            const field = document.getElementById(fieldId);
            const errEl = document.getElementById('err-' + fieldId);
            if (field) field.classList.remove('champ-erreur');
            if (errEl) errEl.textContent = '';
        }

        ['conn-email', 'conn-mdp'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', function () { clearErrorConn(id); });
        });

        formConnexion.addEventListener('submit', function (e) {
            let ok = true;
            const email = (document.getElementById('conn-email') || {}).value || '';
            const mdp   = (document.getElementById('conn-mdp')   || {}).value || '';
            clearErrorConn('conn-email');
            clearErrorConn('conn-mdp');
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showErrorConn('conn-email', 'Adresse e-mail invalide.');
                ok = false;
            }
            if (mdp.length < 1) {
                showErrorConn('conn-mdp', 'Veuillez saisir votre mot de passe.');
                ok = false;
            }
            if (!ok) e.preventDefault();
        });
    }


    /*
    MODIFICATION DU PROFIL EN ASYNCHRONE
    */

    document.querySelectorAll('.edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const ligneForm = btn.closest('.ligne-form');
            if (!ligneForm) return;
            const input = ligneForm.querySelector('input');
            if (!input) return;

            const enEdition = btn.classList.contains('en-edition');

            if (!enEdition) {
                input.removeAttribute('readonly');
                input.focus();
                input.select();
                btn.innerHTML = '✅';
                btn.classList.add('en-edition');
                btn.title = 'Valider la modification';
                input.dataset.valeurInitiale = input.value;
            } else {
                const champ = input.getAttribute('data-champ') || input.getAttribute('name');
                const valeur = input.value.trim();

                if (valeur === input.dataset.valeurInitiale) {
                    input.setAttribute('readonly', true);
                    btn.innerHTML = '🖌️';
                    btn.classList.remove('en-edition');
                    return;
                }

                sauvegarderChampProfil(champ, valeur, input, btn);
            }
        });
    });

    function sauvegarderChampProfil(champ, valeur, inputEl, btnEl) {
        const msgGlobal = document.getElementById('msg-profil');
        const msgChamp  = document.getElementById('err-champ-' + champ);

        fetch('api_update_profil.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ champ: champ, valeur: valeur })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.succes) {
                inputEl.setAttribute('readonly', true);
                btnEl.innerHTML = '🖌️';
                btnEl.classList.remove('en-edition');
                inputEl.dataset.valeurInitiale = valeur;
                // Effacer message d'erreur sous le champ
                if (msgChamp) { msgChamp.textContent = ''; msgChamp.className = 'msg-erreur-champ'; }
                // Message succès global discret
                if (msgGlobal) {
                    msgGlobal.textContent = '✅ ' + (data.message || 'Modification enregistrée.');
                    msgGlobal.className = 'msg-profil-succes';
                    if (champ === 'email') {
                        setTimeout(function() { window.location.reload(); }, 1500);
                    } else {
                        setTimeout(function() { msgGlobal.textContent = ''; }, 3000);
                    }
                }
            } else {
                // Afficher l'erreur directement sous le champ concerné
                if (msgChamp) {
                    msgChamp.textContent = '❌ ' + (data.message || 'Erreur lors de la modification.');
                    msgChamp.className = 'msg-erreur-champ msg-profil-erreur';
                } else if (msgGlobal) {
                    msgGlobal.textContent = '❌ ' + (data.message || 'Erreur lors de la modification.');
                    msgGlobal.className = 'msg-profil-erreur';
                }
            }
        })
        .catch(function() {
            if (msgGlobal) {
                msgGlobal.textContent = '❌ Erreur réseau. Veuillez réessayer.';
                msgGlobal.className = 'msg-profil-erreur';
            }
        });
    }


    /* 
    FILTRES ET TRIS SUR LA CARTE (asynchrone)
    */

    const conteneurCarte = document.getElementById('carte-resultats');
    if (conteneurCarte) {

        let platsAffiches = [];
        let triActif = null;

        function chargerPlats(categorie, allergene) {
            let url = 'api_plats.php?';
            if (categorie && categorie !== 'tous') url += 'categorie=' + encodeURIComponent(categorie) + '&';
            if (allergene && allergene !== 'tous') url += 'allergene=' + encodeURIComponent(allergene) + '&';

            fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                platsAffiches = data.plats || [];
                if (triActif) {
                    appliquerTri(triActif);
                } else {
                    afficherPlats(platsAffiches);
                }
            })
            .catch(function() {
                conteneurCarte.innerHTML = '<p class="carte-vide">Erreur lors du chargement des plats.</p>';
            });
        }

        function appliquerTri(tri) {
            triActif = tri;
            let liste = platsAffiches.slice();
            if (tri === 'prix-asc')    liste.sort(function(a,b){ return a.prix - b.prix; });
            else if (tri === 'prix-desc')  liste.sort(function(a,b){ return b.prix - a.prix; });
            else if (tri === 'popularite') liste.sort(function(a,b){ return (b.nb_commandes||0) - (a.nb_commandes||0); });
            else if (tri === 'nom-asc')    liste.sort(function(a,b){ return a.nom.localeCompare(b.nom); });
            afficherPlats(liste);
        }

        function afficherPlats(liste) {
            const selectTri = document.getElementById('select-tri');
            if (selectTri && triActif) selectTri.value = triActif;
            else if (selectTri && !triActif) selectTri.value = 'defaut';
            if (liste.length === 0) {
                conteneurCarte.innerHTML = '<p class="carte-vide">Aucun plat ne correspond à vos critères.</p>';
                return;
            }
            const connecte = conteneurCarte.dataset.connecte === '1';
            let html = '<div class="colonnes-filtrees">';
            liste.forEach(function(plat) {
                const prix = parseFloat(plat.prix).toFixed(2);
                html += '<div class="plat-carte-item">';
                html += '<p class="titre3">' + esc(plat.nom) + '</p>';
                html += '<div class="plat" style="position:relative;">';
                html += '<img class="img" src="' + esc(plat.image || 'images/plats/default.jpg') + '" alt="' + esc(plat.nom) + '" onerror="this.src=\'images/plats/default.jpg\'">';
                html += '<div class="overlay-texte">Allergènes : ' + esc(plat.allergenes) + '<br>Prix : ' + prix + ' €</div>';
                html += '</div>';
                if (connecte) {
                    html += '<a href="commande-template.php?ajouter=' + esc(plat.id) + '" class="bouton-discover">Ajouter au panier</a>';
                } else {
                    html += '<a href="connexion.php" class="bouton-discover">Se connecter pour commander</a>';
                }
                html += '<span class="badge-categorie">' + esc(libelleCat(plat.categorie)) + '</span>';
                html += '</div>';
            });
            html += '</div>';
            conteneurCarte.innerHTML = html;
        }

        function libelleCat(cat) {
            return { entree:'Entrée', plat:'Plat', dessert:'Dessert' }[cat] || cat;
        }

        function esc(str) {
            if (!str) return '';
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
        }

        // Filtres catégorie
        document.querySelectorAll('.filtre-categorie').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filtre-categorie').forEach(function(b){ b.classList.remove('actif'); });
                btn.classList.add('actif');
                const cat = btn.dataset.categorie;
                const allergene = (document.querySelector('.filtre-allergene.actif') || {}).dataset?.allergene || 'tous';
                chargerPlats(cat, allergene);
            });
        });

        // Filtres allergène
        document.querySelectorAll('.filtre-allergene').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filtre-allergene').forEach(function(b){ b.classList.remove('actif'); });
                btn.classList.add('actif');
                const allergene = btn.dataset.allergene;
                const cat = (document.querySelector('.filtre-categorie.actif') || {}).dataset?.categorie || 'tous';
                chargerPlats(cat, allergene);
            });
        });

        // Tri
        const selectTri = document.getElementById('select-tri');
        if (selectTri) {
            selectTri.addEventListener('change', function() {
                if (selectTri.value === 'defaut') { triActif = null; afficherPlats(platsAffiches); }
                else appliquerTri(selectTri.value);
            });
        }

        chargerPlats('tous', 'tous');
    }


    /*
    MODIFICATION DE COMMANDE
   */

    const formModifCommande = document.getElementById('form-modif-commande');
    if (formModifCommande) {

        function mettreAJourTotal() {
            let total = 0;
            document.querySelectorAll('.modif-plat-item').forEach(function(item) {
                const q = parseInt(item.querySelector('.quantite-input').value) || 0;
                const p = parseFloat(item.dataset.prix) || 0;
                total += q * p;
            });
            const totalEl = document.getElementById('modif-total');
            if (totalEl) { totalEl.textContent = total.toFixed(2) + ' €'; totalEl.dataset.total = total.toFixed(2); }

            const ancienTotal = parseFloat(document.getElementById('modif-ancien-total')?.dataset.total || 0);
            const diff = total - ancienTotal;
            const diffEl = document.getElementById('modif-diff');
            if (diffEl) {
                if (diff > 0.005) { diffEl.textContent = 'Supplément à payer : +' + diff.toFixed(2) + ' €'; diffEl.className = 'modif-diff positif'; }
                else if (diff < -0.005) { diffEl.textContent = 'Économie : ' + Math.abs(diff).toFixed(2) + ' €'; diffEl.className = 'modif-diff negatif'; }
                else { diffEl.textContent = 'Aucun changement de montant.'; diffEl.className = 'modif-diff neutre'; }
            }
        }

        document.querySelectorAll('.quantite-input').forEach(function(input) { input.addEventListener('input', mettreAJourTotal); });

        document.querySelectorAll('.btn-quantite-plus').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const input = btn.closest('.modif-plat-item').querySelector('.quantite-input');
                input.value = Math.min(99, parseInt(input.value || 0) + 1);
                mettreAJourTotal();
            });
        });

        document.querySelectorAll('.btn-quantite-moins').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const input = btn.closest('.modif-plat-item').querySelector('.quantite-input');
                input.value = Math.max(0, parseInt(input.value || 0) - 1);
                mettreAJourTotal();
            });
        });

        formModifCommande.addEventListener('submit', function(e) {
            e.preventDefault();
            const commandeId = document.getElementById('modif-commande-id')?.value;
            const plats = [];
            document.querySelectorAll('.modif-plat-item').forEach(function(item) {
                const q = parseInt(item.querySelector('.quantite-input').value) || 0;
                if (q > 0) plats.push({ id: item.dataset.platId, quantite: q });
            });
            if (plats.length === 0) { alert('Votre commande ne peut pas être vide.'); return; }

            fetch('api_modifier_commande.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ commande_id: commandeId, plats: plats })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                const msgEl = document.getElementById('msg-modif-commande');
                if (data.succes) {
                    if (msgEl) { msgEl.textContent = '✅ ' + (data.message || 'Commande modifiée.'); msgEl.className = 'msg-profil-succes'; }
                    if (data.redirect) setTimeout(function(){ window.location.href = data.redirect; }, 1500);
                } else {
                    if (msgEl) { msgEl.textContent = '❌ ' + (data.message || 'Erreur.'); msgEl.className = 'msg-profil-erreur'; }
                }
            })
            .catch(function() {
                const msgEl = document.getElementById('msg-modif-commande');
                if (msgEl) { msgEl.textContent = '❌ Erreur réseau.'; msgEl.className = 'msg-profil-erreur'; }
            });
        });

        mettreAJourTotal();
    }


    /* 
    BLOCAGE/DÉBLOCAGE ADMIN EN ASYNCHRONE
   */

    document.querySelectorAll('.btn-bloquer-async, .btn-debloquer-async').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const email = btn.dataset.email;
            const action = btn.dataset.action;
            if (!email || !action) return;
            if (!confirm('Confirmer : ' + (action === 'bloquer' ? 'bloquer' : 'débloquer') + ' ' + email + ' ?')) return;

            fetch('api_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: action, email: email })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.succes) {
                    const ligne = document.querySelector('tr[data-email="' + email + '"]');
                    if (ligne) {
                        const badge = ligne.querySelector('.badge-statut');
                        const btnBloq = ligne.querySelector('.btn-bloquer-async');
                        const btnDebloq = ligne.querySelector('.btn-debloquer-async');
                        if (action === 'bloquer') {
                            if (badge) { badge.textContent = 'Bloqué'; badge.className = 'badge-statut statut-bloque'; }
                            ligne.classList.add('user-bloque');
                            if (btnBloq) btnBloq.style.display = 'none';
                            if (btnDebloq) btnDebloq.style.display = 'inline-block';
                        } else {
                            if (badge) { badge.textContent = 'Actif'; badge.className = 'badge-statut statut-actif'; }
                            ligne.classList.remove('user-bloque');
                            if (btnBloq) btnBloq.style.display = 'inline-block';
                            if (btnDebloq) btnDebloq.style.display = 'none';
                        }
                    }
                } else {
                    alert('Erreur : ' + (data.message || 'Action impossible.'));
                }
            })
            .catch(function() { alert('Erreur réseau.'); });
        });
    });

}); // fin DOMContentLoaded
