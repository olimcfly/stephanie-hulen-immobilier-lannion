<?php
/**
 * /admin/modules/seo/onboarding.php
 * Modal onboarding — Première visite, pour ceux qui n'y connaissent rien en SEO
 * À charger au premier accès au module SEO (contrôler avec session flag)
 * 
 * Utilisation dans index.php :
 * if (!isset($_SESSION['seo_onboarding_done'])) {
 *     include __DIR__ . '/onboarding.php';
 * }
 */
?>

<style>
/* ═════════════════════════════════════════════════════════════
   ONBOARDING SEO — Modal interactive
   Namespace : .onb-*
═════════════════════════════════════════════════════════════ */

.onb-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .55);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    font-family: var(--font, 'Inter', sans-serif);
    animation: onbFadeIn .3s ease;
}

.onb-modal {
    background: #fff;
    border-radius: 20px;
    width: 100%;
    max-width: 560px;
    margin: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
    display: flex;
    flex-direction: column;
    max-height: 90vh;
    overflow: hidden;
    animation: onbSlideUp .4s cubic-bezier(.34, 1.56, .64, 1);
}

@keyframes onbFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes onbSlideUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ─── Header ─── */
.onb-header {
    padding: 28px 32px;
    background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
    color: #fff;
    position: relative;
    overflow: hidden;
}

.onb-header::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(255, 255, 255, .1), transparent);
    border-radius: 50%;
}

.onb-header-content {
    position: relative;
    z-index: 1;
}

.onb-header h2 {
    font-size: 1.6rem;
    font-weight: 800;
    margin: 0 0 8px;
    line-height: 1.2;
    letter-spacing: -.02em;
}

.onb-header p {
    font-size: .95rem;
    opacity: .95;
    margin: 0;
    line-height: 1.5;
}

.onb-badge {
    display: inline-block;
    background: rgba(255, 255, 255, .25);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 12px;
}

/* ─── Body ─── */
.onb-body {
    flex: 1;
    overflow-y: auto;
    padding: 28px 32px;
}

.onb-body::-webkit-scrollbar {
    width: 6px;
}

.onb-body::-webkit-scrollbar-track {
    background: transparent;
}

.onb-body::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 3px;
}

.onb-body::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}

/* ─── Steps ─── */
.onb-steps {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.onb-step {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 16px;
    padding: 16px;
    background: var(--surface-2, #f9fafb);
    border-radius: 12px;
    border: 1px solid var(--border, #e5e7eb);
    transition: all .2s;
}

.onb-step:hover {
    border-color: #8b5cf6;
    background: rgba(139, 92, 246, .03);
}

.onb-step-num {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, #8b5cf6, #6366f1);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1rem;
    flex-shrink: 0;
}

.onb-step-content h3 {
    font-size: .95rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 6px;
}

.onb-step-content p {
    font-size: .85rem;
    color: #64748b;
    line-height: 1.5;
    margin: 0 0 8px;
}

.onb-step-action {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: .8rem;
    font-weight: 700;
    color: #8b5cf6;
    text-decoration: none;
    cursor: pointer;
    transition: gap .15s;
}

.onb-step-action:hover {
    gap: 6px;
}

/* ─── Tips box ─── */
.onb-tip-box {
    background: #fef9c3;
    border: 1px solid #fde047;
    border-radius: 10px;
    padding: 14px 16px;
    margin: 20px 0;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.onb-tip-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #fcd34d;
    color: #92400e;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .85rem;
    flex-shrink: 0;
    font-weight: 700;
}

.onb-tip-content {
    flex: 1;
}

.onb-tip-content strong {
    color: #92400e;
    font-weight: 700;
}

.onb-tip-content p {
    font-size: .85rem;
    color: #78350f;
    margin: 0;
    line-height: 1.4;
}

/* ─── Progress ─── */
.onb-progress {
    margin: 20px 0;
    display: flex;
    gap: 4px;
}

.onb-progress-bar {
    flex: 1;
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
}

.onb-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #8b5cf6, #6366f1);
    border-radius: 2px;
    transition: width .5s ease;
}

/* ─── Footer ─── */
.onb-footer {
    padding: 20px 32px;
    border-top: 1px solid var(--border, #e5e7eb);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: var(--surface, #fff);
}

.onb-footer-text {
    font-size: .8rem;
    color: var(--text-3, #9ca3af);
}

.onb-footer-actions {
    display: flex;
    gap: 8px;
}

.onb-btn {
    padding: 9px 18px;
    border-radius: 10px;
    font-size: .85rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all .15s;
    font-family: inherit;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.onb-btn-secondary {
    background: var(--surface-2, #f9fafb);
    color: var(--text-2, #6b7280);
    border: 1px solid var(--border, #e5e7eb);
}

.onb-btn-secondary:hover {
    background: var(--surface, #fff);
    border-color: #8b5cf6;
    color: #8b5cf6;
}

.onb-btn-primary {
    background: linear-gradient(135deg, #8b5cf6, #6366f1);
    color: #fff;
    box-shadow: 0 2px 8px rgba(139, 92, 246, .3);
}

.onb-btn-primary:hover {
    box-shadow: 0 4px 16px rgba(139, 92, 246, .4);
    transform: translateY(-1px);
}

.onb-btn:disabled {
    opacity: .5;
    cursor: not-allowed;
}

/* ─── Responsive ─── */
@media (max-width: 600px) {
    .onb-modal {
        max-width: 100%;
        border-radius: 16px 16px 0 0;
        margin: 0;
    }

    .onb-header {
        padding: 20px 24px;
    }

    .onb-header h2 {
        font-size: 1.35rem;
    }

    .onb-body {
        padding: 20px 24px;
    }

    .onb-footer {
        padding: 16px 24px;
        flex-direction: column-reverse;
    }

    .onb-footer-actions {
        width: 100%;
    }

    .onb-btn {
        flex: 1;
        justify-content: center;
    }
}
</style>

<!-- ═════════════════════════════════════════════════════════════ -->
<!-- MODAL ONBOARDING                                             -->
<!-- ═════════════════════════════════════════════════════════════ -->
<div class="onb-overlay" id="onbOverlay">
    <div class="onb-modal">
        <!-- ─── Header ─── -->
        <div class="onb-header">
            <div class="onb-header-content">
                <span class="onb-badge"><i class="fas fa-star"></i> Bienvenue!</span>
                <h2>Maîtriser le SEO en 5 min</h2>
                <p>Guide ultra-simple pour ceux qui trouvent le SEO compliqué</p>
            </div>
        </div>

        <!-- ─── Body ─── -->
        <div class="onb-body">
            <div class="onb-progress">
                <div class="onb-progress-bar">
                    <div class="onb-progress-fill" style="width: 0%;" id="onbProgressBar"></div>
                </div>
            </div>

            <div class="onb-steps" id="onbStepsContainer">
                <!-- Step 1 -->
                <div class="onb-step" data-step="1">
                    <div class="onb-step-num">1</div>
                    <div class="onb-step-content">
                        <h3>C'est quoi le SEO?</h3>
                        <p>
                            Le SEO c'est apparaître en 1ère page Google quand quelqu'un cherche 
                            "agent immobilier + VOTRE VILLE". C'est gratuit, contrairement aux pubs payantes.
                        </p>
                        <a href="#" onclick="onbGoToGuide()" class="onb-step-action">
                            Lire le guide complet <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="onb-step" data-step="2">
                    <div class="onb-step-num">2</div>
                    <div class="onb-step-content">
                        <h3>Les 3 piliers du SEO</h3>
                        <p>
                            <strong>✏️ On-Page :</strong> Comment vous écrivez vos pages<br>
                            <strong>⚙️ Technique :</strong> Vitesse, mobile-friendly du site<br>
                            <strong>⭐ Off-Page :</strong> Avis Google & publications (ultra important)
                        </p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="onb-step" data-step="3">
                    <div class="onb-step-num">3</div>
                    <div class="onb-step-content">
                        <h3>Google My Business = SEO gratuit</h3>
                        <p>
                            Avoir 50 avis ⭐⭐⭐⭐⭐ sur GMB = apparaître position 1 localement.
                            C'est votre arme secrète. Demandez un avis après chaque RDV réussi.
                        </p>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="onb-step" data-step="4">
                    <div class="onb-step-num">4</div>
                    <div class="onb-step-content">
                        <h3>Écrivez des articles</h3>
                        <p>
                            1 article/semaine pendant 6 mois = trafic stable.
                            Exemple: "Guide : comment vendre votre maison 20% plus cher"
                            Les articles = clients gratuits qui trouvent votre site avant de vous appeler.
                        </p>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="onb-step" data-step="5">
                    <div class="onb-step-num">5</div>
                    <div class="onb-step-content">
                        <h3>Temps = quand voir résultats?</h3>
                        <p>
                            <strong>3-6 mois</strong> avant premiers résultats visibles.
                            Mais une fois que vous êtes en position 1, vous avez du trafic gratuit à perpétuité.
                            SEO ≠ pub payante (qui s'arrête dès que vous arrêtez de payer).
                        </p>
                    </div>
                </div>

                <!-- Tip box -->
                <div class="onb-tip-box">
                    <div class="onb-tip-icon">💡</div>
                    <div class="onb-tip-content">
                        <strong>Conseil :</strong>
                        <p>
                            Commencez par 5 actions : GMB optimisé + page d'accueil + 1 article 
                            + Google Search Console + système pour récupérer des avis. 
                            Rien de compliqué, juste du travail méthodique.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Footer ─── -->
        <div class="onb-footer">
            <span class="onb-footer-text" id="onbStepCounter">Étape 1 sur 5</span>
            <div class="onb-footer-actions">
                <button class="onb-btn onb-btn-secondary" onclick="onbClose()">
                    <i class="fas fa-times"></i> Fermer
                </button>
                <a href="?page=seo&tab=guide" class="onb-btn onb-btn-primary">
                    <i class="fas fa-book"></i> Voir le guide complet
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// ─── Onboarding Logic ───
const ONB = {
    currentStep: 1,
    totalSteps: 5,

    init() {
        this.updateProgress();
        this.setupScrollListener();
    },

    updateProgress() {
        const percentage = (this.currentStep / this.totalSteps) * 100;
        document.getElementById('onbProgressBar').style.width = percentage + '%';
        document.getElementById('onbStepCounter').textContent = 
            `Étape ${this.currentStep} sur ${this.totalSteps}`;
    },

    setupScrollListener() {
        const body = document.querySelector('.onb-body');
        body.addEventListener('scroll', () => {
            const steps = document.querySelectorAll('.onb-step');
            let newStep = 1;
            steps.forEach((step, idx) => {
                const rect = step.getBoundingClientRect();
                if (rect.top < 150) {
                    newStep = idx + 1;
                }
            });
            if (newStep !== this.currentStep) {
                this.currentStep = newStep;
                this.updateProgress();
            }
        });
    }
};

function onbClose() {
    const overlay = document.getElementById('onbOverlay');
    overlay.style.animation = 'onbFadeOut .3s ease forwards';
    setTimeout(() => {
        overlay.remove();
        // Mark as seen in session
        fetch('?page=seo&action=mark_onboarding_done', {method: 'POST'});
    }, 300);
}

function onbGoToGuide() {
    window.location.href = '?page=seo&tab=guide';
}

// Init on load
document.addEventListener('DOMContentLoaded', () => ONB.init());

// Close on Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') onbClose();
});
</script>

<style>
@keyframes onbFadeOut {
    to {
        opacity: 0;
        backdrop-filter: blur(0px);
    }
}
</style>