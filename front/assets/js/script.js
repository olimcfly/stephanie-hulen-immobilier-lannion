/**
 * script.js — /front/assets/js/script.js
 * Scripts frontend — Eduardo De Sul Immobilier
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initSmoothScroll();
    initScrollAnimations();
    initForms();
    initMobileMenu();
  });

  // ── Smooth scroll ──────────────────────────────────────────────────────
  function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        var t = document.querySelector(this.getAttribute('href'));
        if (!t) return;
        e.preventDefault();
        t.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  }

  // ── Animations IntersectionObserver ───────────────────────────────────
  function initScrollAnimations() {
    if (!('IntersectionObserver' in window)) return;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          en.target.classList.add('visible');
          io.unobserve(en.target);
        }
      });
    }, { threshold: 0.12 });
    document.querySelectorAll('.anim, .fade-in, .slide-up').forEach(function (el) {
      io.observe(el);
    });
  }

  // ── Formulaires ────────────────────────────────────────────────────────
  function initForms() {
    // Format téléphone FR
    document.querySelectorAll('input[type="tel"]').forEach(function (inp) {
      inp.addEventListener('input', function () {
        var v = this.value.replace(/\D/g, '').slice(0, 10);
        this.value = v.replace(/(\d{2})(?=\d)/g, '$1 ').trim();
      });
    });
    // Désactiver bouton submit pendant l'envoi
    document.querySelectorAll('form').forEach(function (f) {
      f.addEventListener('submit', function () {
        var btn = f.querySelector('[type="submit"]');
        if (btn && !btn.dataset.noDisable) {
          btn.disabled = true;
          var orig = btn.textContent;
          btn.textContent = 'Envoi…';
          setTimeout(function () { btn.disabled = false; btn.textContent = orig; }, 8000);
        }
      });
    });
  }

  // ── Menu mobile ────────────────────────────────────────────────────────
  function initMobileMenu() {
    var toggle = document.getElementById('menu-toggle');
    var nav    = document.getElementById('mobile-nav');
    if (!toggle || !nav) return;
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      if (!toggle.contains(e.target) && !nav.contains(e.target)) nav.classList.remove('open');
    });
  }

  // ── Notification toast ────────────────────────────────────────────────
  window.showNotif = function (msg, type) {
    type = type || 'success';
    var el = document.createElement('div');
    el.textContent = msg;
    el.style.cssText = [
      'position:fixed;bottom:20px;right:20px;z-index:9999',
      'padding:12px 20px;border-radius:8px',
      'font-size:14px;font-weight:500;color:#fff',
      'box-shadow:0 4px 16px rgba(0,0,0,.15)',
      'background:' + (type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#f59e0b'),
      'transition:opacity .3s'
    ].join(';');
    document.body.appendChild(el);
    setTimeout(function () { el.style.opacity = '0'; setTimeout(function () { el.remove(); }, 300); }, 3500);
  };

})();