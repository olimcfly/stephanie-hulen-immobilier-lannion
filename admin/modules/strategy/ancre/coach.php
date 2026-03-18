<?php
/**
 * ══════════════════════════════════════════════════════════════
 * ANCRE COACH — Composant assistant IA partagé
 * /admin/modules/strategy/ancre/coach.php
 *
 * Usage : include avec $coach_pilier défini
 *   $coach_pilier = ['lettre'=>'A','mot'=>'Ancrage','contexte'=>'...']
 * ══════════════════════════════════════════════════════════════
 */
if (!isset($coach_pilier)) return;

$cp_lettre  = htmlspecialchars($coach_pilier['lettre']  ?? 'A');
$cp_mot     = htmlspecialchars($coach_pilier['mot']     ?? '');
$cp_ctx     = addslashes($coach_pilier['contexte']      ?? '');
$cp_prompts = $coach_pilier['suggestions']              ?? [];

// Récupérer clé API depuis ai_settings
$coach_api_key = '';
try {
    $db_c  = getDB();
    $s_key = $db_c->prepare(
        "SELECT setting_value FROM ai_settings
         WHERE instance_id = :iid AND setting_key = 'anthropic_api_key'
         LIMIT 1"
    );
    $s_key->execute([':iid' => INSTANCE_ID]);
    $coach_api_key = $s_key->fetchColumn() ?: '';
} catch (Exception $e) { /* silencieux */ }
?>

<!-- ══ COACH ANCRE — Floating button + panel ══════════════════ -->
<style>
/* ── Coach panel ──────────────────────────────────────────── */
.coach-fab {
    position: fixed; bottom: 28px; right: 28px; z-index: 1000;
    width: 56px; height: 56px; border-radius: 50%;
    background: linear-gradient(135deg, #c9913b, #a0722a);
    border: none; cursor: pointer;
    box-shadow: 0 4px 20px rgba(201,145,59,.45);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.3rem;
    transition: transform .2s, box-shadow .2s;
}
.coach-fab:hover {
    transform: scale(1.08);
    box-shadow: 0 6px 28px rgba(201,145,59,.55);
}
.coach-fab .coach-fab-badge {
    position: absolute; top: -4px; right: -4px;
    width: 20px; height: 20px; border-radius: 50%;
    background: #ef4444; color: #fff;
    font-size: .6rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid var(--surface, #fff);
}

.coach-panel {
    position: fixed; bottom: 96px; right: 28px; z-index: 999;
    width: 380px; max-height: 560px;
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,.18);
    display: flex; flex-direction: column;
    transform: translateY(20px) scale(.96);
    opacity: 0; pointer-events: none;
    transition: all .25s cubic-bezier(.34,1.56,.64,1);
}
.coach-panel.is-open {
    transform: translateY(0) scale(1);
    opacity: 1; pointer-events: all;
}

.coach-panel-hd {
    padding: 16px 18px 12px;
    border-bottom: 1px solid var(--border, #e5e7eb);
    display: flex; align-items: center; gap: 10px; flex-shrink: 0;
}
.coach-panel-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, #c9913b, #a0722a);
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; font-weight: 900; color: #fff; flex-shrink: 0;
    font-family: 'Fraunces', Georgia, serif;
}
.coach-panel-meta { flex: 1; }
.coach-panel-name {
    font-size: .83rem; font-weight: 700;
    color: var(--text, #111827); margin-bottom: 1px;
}
.coach-panel-sub {
    font-size: .68rem; color: var(--text-2, #6b7280);
}
.coach-panel-close {
    background: none; border: none; cursor: pointer;
    color: var(--text-3, #9ca3af); font-size: .85rem;
    padding: 4px; border-radius: 6px;
    transition: background .15s, color .15s;
}
.coach-panel-close:hover {
    background: var(--surface-2, #f9fafb);
    color: var(--text-2, #6b7280);
}

/* Messages */
.coach-messages {
    flex: 1; overflow-y: auto; padding: 14px 16px;
    display: flex; flex-direction: column; gap: 10px;
    scroll-behavior: smooth;
}
.coach-msg {
    max-width: 88%;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: .8rem; line-height: 1.55;
}
.coach-msg.assistant {
    background: var(--surface-2, #f9fafb);
    border: 1px solid var(--border, #e5e7eb);
    color: var(--text, #111827);
    align-self: flex-start;
    border-bottom-left-radius: 4px;
}
.coach-msg.user {
    background: #0f172a;
    color: rgba(255,255,255,.9);
    align-self: flex-end;
    border-bottom-right-radius: 4px;
}
.coach-msg.thinking {
    background: var(--surface-2, #f9fafb);
    border: 1px solid var(--border, #e5e7eb);
    align-self: flex-start;
    border-bottom-left-radius: 4px;
}
.coach-thinking-dots {
    display: flex; gap: 4px; align-items: center; padding: 2px 0;
}
.coach-thinking-dots span {
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--text-3, #9ca3af);
    animation: coachDot .9s ease-in-out infinite;
}
.coach-thinking-dots span:nth-child(2) { animation-delay: .15s; }
.coach-thinking-dots span:nth-child(3) { animation-delay: .3s; }
@keyframes coachDot {
    0%,80%,100% { transform: scale(.8); opacity: .4; }
    40%         { transform: scale(1);  opacity: 1; }
}

/* Suggestions */
.coach-suggestions {
    padding: 0 16px 10px;
    display: flex; flex-wrap: wrap; gap: 6px; flex-shrink: 0;
}
.coach-suggestion-btn {
    padding: 5px 12px;
    background: var(--surface-2, #f9fafb);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 20px;
    font-size: .7rem; font-weight: 600;
    color: var(--text-2, #6b7280); cursor: pointer;
    transition: background .15s, border-color .15s, color .15s;
    white-space: nowrap;
}
.coach-suggestion-btn:hover {
    background: #0f172a; border-color: #0f172a; color: #fff;
}

/* Input */
.coach-input-area {
    padding: 10px 14px 14px;
    border-top: 1px solid var(--border, #e5e7eb);
    display: flex; gap: 8px; align-items: flex-end; flex-shrink: 0;
}
.coach-input {
    flex: 1;
    background: var(--surface-2, #f9fafb);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 10px;
    padding: 8px 12px;
    font-size: .8rem; color: var(--text, #111827);
    font-family: inherit; resize: none; max-height: 80px;
    outline: none; transition: border-color .15s;
    line-height: 1.4;
}
.coach-input:focus { border-color: #c9913b; }
.coach-send-btn {
    width: 34px; height: 34px; border-radius: 8px;
    background: linear-gradient(135deg, #c9913b, #a0722a);
    border: none; cursor: pointer; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem; flex-shrink: 0;
    transition: transform .15s, opacity .15s;
}
.coach-send-btn:hover { transform: scale(1.05); }
.coach-send-btn:disabled { opacity: .5; cursor: not-allowed; transform: none; }

@media (max-width: 480px) {
    .coach-panel { width: calc(100vw - 24px); right: 12px; bottom: 80px; }
}
</style>

<!-- FAB -->
<button class="coach-fab" onclick="coachToggle()" id="coachFab" title="Coach ANCRE">
    <i class="fas fa-anchor"></i>
    <span class="coach-fab-badge" id="coachBadge" style="display:none">1</span>
</button>

<!-- Panel -->
<div class="coach-panel" id="coachPanel">
    <div class="coach-panel-hd">
        <div class="coach-panel-avatar"><?= $cp_lettre ?></div>
        <div class="coach-panel-meta">
            <div class="coach-panel-name">Coach ANCRE — Pilier <?= $cp_lettre ?> · <?= $cp_mot ?></div>
            <div class="coach-panel-sub">Votre assistant stratégie immobilier local</div>
        </div>
        <button class="coach-panel-close" onclick="coachToggle()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="coach-messages" id="coachMessages">
        <!-- Message d'accueil injecté par JS -->
    </div>

    <div class="coach-suggestions" id="coachSuggestions">
        <?php foreach ($cp_prompts as $sugg): ?>
        <button class="coach-suggestion-btn"
                onclick="coachSend(<?= json_encode($sugg) ?>)">
            <?= htmlspecialchars($sugg) ?>
        </button>
        <?php endforeach; ?>
    </div>

    <div class="coach-input-area">
        <textarea class="coach-input" id="coachInput"
                  placeholder="Posez votre question…" rows="1"
                  onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();coachSend()}"
                  oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'">
        </textarea>
        <button class="coach-send-btn" id="coachSendBtn" onclick="coachSend()">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
(function () {
    'use strict';

    const API_KEY    = <?= json_encode($coach_api_key) ?>;
    const PILIER_CTX = <?= json_encode($cp_ctx) ?>;
    const PILIER_MOT = <?= json_encode($cp_mot) ?>;
    const PILIER_LTR = <?= json_encode($cp_lettre) ?>;

    let history     = [];
    let isOpen      = false;
    let hasGreeted  = false;
    let isThinking  = false;

    const panel      = document.getElementById('coachPanel');
    const messages   = document.getElementById('coachMessages');
    const input      = document.getElementById('coachInput');
    const sendBtn    = document.getElementById('coachSendBtn');
    const badge      = document.getElementById('coachBadge');
    const suggestions= document.getElementById('coachSuggestions');

    // ── Système prompt ─────────────────────────────────────────
    const SYSTEM = `Tu es le Coach ANCRE, un assistant expert en stratégie immobilière locale pour des conseillers indépendants en France travaillant sous l'enseigne eXp France ou en réseau.

Tu guides l'utilisateur dans le pilier ${PILIER_LTR} — ${PILIER_MOT} de la Méthode ANCRE.

Contexte du pilier :
${PILIER_CTX}

Règles absolues :
- Réponds toujours en français, de façon concise (max 3 paragraphes)
- Sois direct, actionnable, professionnel mais chaleureux
- Donne des exemples concrets adaptés à l'immobilier local français
- Si l'utilisateur est bloqué sur une action, décompose-la en micro-tâches
- N'invente jamais de chiffres ou de résultats garantis
- Encourage et motive sans être complaisant
- Si une question sort du pilier actuel, réponds brièvement et redirige vers le bon pilier ANCRE`;

    // ── Toggle panel ──────────────────────────────────────────
    window.coachToggle = function () {
        isOpen = !isOpen;
        panel.classList.toggle('is-open', isOpen);
        badge.style.display = 'none';

        if (isOpen && !hasGreeted) {
            hasGreeted = true;
            // Petit délai pour l'animation
            setTimeout(() => {
                appendMsg('assistant',
                    `Bonjour ! Je suis votre Coach ANCRE pour le pilier **${PILIER_LTR} — ${PILIER_MOT}**.\n\nJe suis là pour vous guider étape par étape. Sur quoi puis-je vous aider en ce moment ?`
                );
            }, 180);
        }

        if (isOpen) setTimeout(() => input.focus(), 250);
    };

    // ── Envoyer un message ────────────────────────────────────
    window.coachSend = function (text) {
        const msg = (text ?? input.value).trim();
        if (!msg || isThinking) return;

        // Masquer suggestions après 1er envoi
        suggestions.style.display = 'none';

        input.value = '';
        input.style.height = 'auto';
        appendMsg('user', msg);
        history.push({ role: 'user', content: msg });

        if (!API_KEY) {
            appendMsg('assistant',
                'La clé API Anthropic n\'est pas configurée. Rendez-vous dans **Paramètres → Clés API** pour l\'ajouter.'
            );
            return;
        }

        showThinking();
        isThinking = true;
        sendBtn.disabled = true;

        fetch('https://api.anthropic.com/v1/messages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'x-api-key': API_KEY,
                'anthropic-version': '2023-06-01',
            },
            body: JSON.stringify({
                model: 'claude-opus-4-6',
                max_tokens: 1024,
                system: SYSTEM,
                messages: history,
            }),
        })
        .then(r => r.json())
        .then(data => {
            hideThinking();
            isThinking  = false;
            sendBtn.disabled = false;

            const reply = data?.content?.[0]?.text ?? 'Désolé, une erreur est survenue.';
            history.push({ role: 'assistant', content: reply });
            appendMsg('assistant', reply);
        })
        .catch(() => {
            hideThinking();
            isThinking  = false;
            sendBtn.disabled = false;
            appendMsg('assistant', 'Erreur réseau. Vérifiez votre connexion et réessayez.');
        });
    };

    // ── DOM helpers ───────────────────────────────────────────
    function appendMsg(role, text) {
        const el = document.createElement('div');
        el.className = `coach-msg ${role}`;
        el.innerHTML = markdownLight(text);
        messages.appendChild(el);
        messages.scrollTop = messages.scrollHeight;
    }

    let thinkingEl = null;
    function showThinking() {
        thinkingEl = document.createElement('div');
        thinkingEl.className = 'coach-msg thinking';
        thinkingEl.innerHTML = '<div class="coach-thinking-dots"><span></span><span></span><span></span></div>';
        messages.appendChild(thinkingEl);
        messages.scrollTop = messages.scrollHeight;
    }
    function hideThinking() {
        if (thinkingEl) { thinkingEl.remove(); thinkingEl = null; }
    }

    // Markdown minimal : **gras**, *italique*, retours à la ligne
    function markdownLight(txt) {
        return txt
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g,   '<em>$1</em>')
            .replace(/\n/g, '<br>');
    }

    // Badge si panel fermé et message non lu
    function notifyBadge() {
        if (!isOpen) badge.style.display = 'flex';
    }

    // Exposer pour usage externe
    window.coachNotify = notifyBadge;

})();
</script>