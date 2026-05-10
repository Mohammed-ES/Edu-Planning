/**
 * app.js — Edu-Planning UCA
 * Grand Academic JavaScript Interactions
 * Université Cadi Ayyad, Marrakech
 */

(function () {
  'use strict';

  /* ============================================================
     PRE-DECLARATIONS OF INIT FUNCTIONS
     ============================================================ */
  function initCounters() {
    var counters = document.querySelectorAll('[data-count]');
    if (!counters.length) return;

    var options = {
      threshold: 0.1
    };

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting && !entry.target.dataset.counted) {
          var target = parseInt(entry.target.getAttribute('data-count'), 10);
          animateCounter(entry.target, target, 2000);
          entry.target.dataset.counted = 'true';
          observer.unobserve(entry.target);
        }
      });
    }, options);

    counters.forEach(function (counter) {
      observer.observe(counter);
    });
  }

  function initScrollReveal() {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          entry.target.classList.remove('sr-init');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.05 });

    document.querySelectorAll('.scroll-reveal').forEach(function (el) {
      el.classList.add('sr-init');
      observer.observe(el);
    });
  }

  function initProgressBars() {
    var bars = document.querySelectorAll('.progress-fill');
    bars.forEach(function (bar) {
      var targetWidth = bar.parentElement.getAttribute('data-width') || bar.style.width;
      bar.style.width = '0%';
      setTimeout(function () {
        bar.style.width = targetWidth;
      }, 100);
    });
  }

  function initFormFields() {
    var inputs = document.querySelectorAll('.form-control');
    inputs.forEach(function (input) {
      input.addEventListener('focus', function () {
        this.parentNode.classList.add('input-focused');
      });
      input.addEventListener('blur', function () {
        if (this.value === '') {
          this.parentNode.classList.remove('input-focused');
        }
      });
    });
  }

  function initCardAnimations() {
    var cards = document.querySelectorAll('.stat-card, .module-card, .diagram-card, .planning-card, .rec-card');
    cards.forEach(function (card, index) {
      card.style.setProperty('--anim-delay', (index * 0.07) + 's');
    });
  }

  function animateCounter(el, target, duration) {
    var start = 0;
    var startTime = null;
    var suffix = el.getAttribute('data-suffix') || '+';

    function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var elapsed = timestamp - startTime;
      var progress = Math.min(elapsed / duration, 1);
      var value = Math.floor(easeOutCubic(progress) * target);
      el.textContent = value + suffix;
      if (progress < 1) requestAnimationFrame(step);
    }

    requestAnimationFrame(step);
  }

  function initTypewriterCleanup() {
    var el = document.querySelector('.typewriter-text');
    if (!el) return;
    setTimeout(function () {
      el.style.borderRight = 'none';
    }, 6000);
  }

  function initNavbarScroll() {
    var navbar = document.getElementById('main-navbar');
    if (!navbar) return;

    var ticking = false;
    window.addEventListener('scroll', function () {
      if (!ticking) {
        requestAnimationFrame(function () {
          if (window.scrollY > 60) {
            navbar.classList.add('navbar-scrolled');
          } else {
            navbar.classList.remove('navbar-scrolled');
          }
          ticking = false;
        });
        ticking = true;
      }
    });
  }

  function initParticles() {
    var heroContainer = document.querySelector('.hero-particles');
    if (heroContainer) createParticles(heroContainer, 15);
    var authContainer = document.querySelector('.auth-particles');
    if (authContainer) createParticles(authContainer, 8);
  }

  function createParticles(container, count) {
    if (window.innerWidth < 768) count = Math.min(count, 6);
    for (var i = 0; i < count; i++) {
      var p = document.createElement('span');
      p.className = 'particle';
      var size = 4 + Math.random() * 8;
      p.style.cssText = [
        'left:' + (Math.random() * 100) + '%',
        'bottom:-20px',
        'width:' + size + 'px',
        'height:' + size + 'px',
        'animation-name:particleFloat',
        'animation-duration:' + (8 + Math.random() * 12) + 's',
        'animation-delay:' + (Math.random() * 8) + 's',
        'animation-timing-function:linear',
        'animation-iteration-count:infinite',
        'border-radius:' + (Math.random() > 0.5 ? '50%' : '3px'),
        'opacity:0',
        'transform:rotate(' + (Math.random() * 45) + 'deg)',
        'position:absolute',
        'background:#C8962E',
        'pointer-events:none',
        'z-index:1'
      ].join(';');
      container.appendChild(p);
    }
  }

  function initRipples() {
    var selectors = [
      '.btn-primary', '.btn-gold', '.btn-shimmer',
      '.btn-login', '.btn-register',
      '.btn-premium', '.btn-outline-premium'
    ].join(',');

    document.addEventListener('click', function (e) {
      var btn = e.target.closest(selectors);
      if (!btn) return;
      var ripple = document.createElement('span');
      var rect = btn.getBoundingClientRect();
      var x = e.clientX - rect.left;
      var y = e.clientY - rect.top;
      ripple.style.cssText = [
        'position:absolute',
        'border-radius:50%',
        'background:rgba(255,255,255,0.32)',
        'width:0', 'height:0',
        'left:' + x + 'px',
        'top:' + y + 'px',
        'transform:translate(-50%,-50%)',
        'animation:rippleEffect 0.6s ease-out forwards',
        'pointer-events:none',
        'z-index:10'
      ].join(';');
      btn.style.position = 'relative';
      btn.style.overflow = 'hidden';
      btn.appendChild(ripple);
      setTimeout(function () { if (ripple.parentNode) ripple.remove(); }, 700);
    });
  }

  function initCustomCursor() {
    if ('ontouchstart' in window || window.innerWidth < 1024) return;
    var cursor = document.createElement('div');
    cursor.className = 'custom-cursor';
    document.body.appendChild(cursor);
    var mouseX = 0, mouseY = 0;
    var curX = 0, curY = 0;
    document.addEventListener('mousemove', function (e) {
      mouseX = e.clientX;
      mouseY = e.clientY;
    });
    function animateCursor() {
      curX += (mouseX - curX) * 0.18;
      curY += (mouseY - curY) * 0.18;
      cursor.style.left = curX + 'px';
      cursor.style.top = curY + 'px';
      requestAnimationFrame(animateCursor);
    }
    animateCursor();
    var hoverEls = document.querySelectorAll('a, button, .btn, input, textarea, [role="button"]');
    hoverEls.forEach(function (el) {
      el.addEventListener('mouseenter', function () { cursor.classList.add('cursor-hover'); });
      el.addEventListener('mouseleave', function () { cursor.classList.remove('cursor-hover'); });
    });
    document.addEventListener('mouseleave', function () { cursor.style.opacity = '0'; });
    document.addEventListener('mouseenter', function () { cursor.style.opacity = '1'; });
  }

  function initPasswordStrength() {
    var pwInput = document.getElementById('password');
    var strengthWrap = document.querySelector('.strength-wrapper');
    if (!pwInput || !strengthWrap) return;
    pwInput.addEventListener('input', function () {
      var pw = this.value;
      var score = 0;
      var label = '';
      if (pw.length >= 8) score++;
      if (/[A-Z]/.test(pw)) score++;
      if (/[0-9]/.test(pw)) score++;
      if (/[^A-Za-z0-9]/.test(pw)) score++;
      var levels = ['', 'strength-weak', 'strength-fair', 'strength-good', 'strength-strong'];
      var labels = ['', 'Faible', 'Acceptable', 'Bon', 'Fort'];
      strengthWrap.className = 'strength-wrapper ' + (pw.length > 0 ? levels[score] : '');
      var labelEl = strengthWrap.querySelector('.strength-label');
      if (labelEl) labelEl.textContent = pw.length > 0 ? labels[score] : '';
    });
  }

  function initFormValidation() {
    var emailInput = document.getElementById('email');
    if (emailInput) {
      emailInput.addEventListener('blur', function () {
        validateField(this, /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value));
      });
    }
    var usernameInput = document.getElementById('username');
    if (usernameInput) {
      usernameInput.addEventListener('blur', function () {
        validateField(this, this.value.trim().length >= 3);
      });
    }
    var pwInput = document.getElementById('password');
    var pwConfirm = document.getElementById('password_confirm');
    if (pwConfirm && pwInput) {
      pwConfirm.addEventListener('blur', function () {
        validateField(this, this.value === pwInput.value && this.value.length > 0);
      });
    }
  }

  function validateField(input, isValid) {
    var group = input.closest('.form-group');
    if (!group) return;
    group.classList.remove('field-valid', 'field-invalid');
    if (input.value.length === 0) return;
    group.classList.add(isValid ? 'field-valid' : 'field-invalid');
    updateFieldIcon(group, isValid);
  }

  function updateFieldIcon(group, isValid) {
    var icon = group.querySelector('.field-icon');
    if (!icon) {
      icon = document.createElement('span');
      icon.className = 'field-icon';
      icon.setAttribute('aria-hidden', 'true');
      var inputWrap = group.querySelector('.input-wrapper') || group;
      inputWrap.style.position = 'relative';
      inputWrap.appendChild(icon);
    }
    icon.innerHTML = isValid
      ? '<i class="fas fa-check-circle" style="color:#27AE60;animation:zoomIn 0.3s ease both"></i>'
      : '<i class="fas fa-times-circle" style="color:#C0392B;animation:zoomIn 0.3s ease both"></i>';
  }

  function initWelcomeScreen() {
    var ws = document.getElementById('welcome-screen');
    if (!ws) return;
    
    // Check if welcome already shown in this session
    var shown = sessionStorage.getItem('uca_welcome_shown');
    if (shown) {
      ws.remove();
      revealDashboard();
      return;
    }
    
    // Mark as shown in session storage
    sessionStorage.setItem('uca_welcome_shown', '1');
    
    // Create enhanced floating particles
    createWelcomeParticles();
    
    // 3-step exit sequence with precise timing
    // T=0.00s: Welcome content visible
    // T=4.20s: Start exit
    // T=4.20s → T=4.70s: contentBlurOut animation (0.5s)
    // T=4.70s → T=5.60s: curtainRise animation (0.9s)
    // T=5.60s: Remove welcome-screen, reveal dashboard
    
    setTimeout(function () {
      // Step 1: Blur out content (0.5s)
      var content = ws.querySelector('.welcome-content');
      if (content) {
        content.style.animation = 'contentBlurOut 0.5s cubic-bezier(0.76, 0, 0.24, 1) forwards';
      }
      
      // Step 2: Curtain rise after content blur (delay 0.3s = 300ms)
      setTimeout(function () {
        ws.style.animation = 'curtainRise 0.9s cubic-bezier(0.76, 0, 0.24, 1) forwards';
        
        // Step 3: Remove and reveal dashboard (after curtain rise completes)
        setTimeout(function () {
          ws.remove();
          revealDashboard();
        }, 900);
      }, 300);
    }, 4200);
  }

  function createWelcomeParticles() {
    var overlay = document.getElementById('welcome-screen');
    if (!overlay) return;
    
    // Create 18-20 particles with enhanced randomization
    var particleCount = 18 + Math.floor(Math.random() * 3);
    
    for (var i = 0; i < particleCount; i++) {
      var p = document.createElement('span');
      
      // Randomized size: 3-11px
      var size = 3 + Math.random() * 8;
      
      // Random opacity: 0.15-0.65
      var opacity = 0.15 + Math.random() * 0.5;
      
      // Duration: 10-24s
      var duration = 10 + Math.random() * 14;
      
      // Delay: 0-3s
      var delay = Math.random() * 3;
      
      // Shape: 50% circles, 50% squares
      var isRound = Math.random() > 0.5;
      
      // Rotation: 0-45deg for non-round particles
      var rotation = isRound ? 0 : Math.random() * 45;
      
      p.style.cssText = [
        'position: absolute;',
        'left: ' + (Math.random() * 100) + '%;',
        'bottom: -20px;',
        'width: ' + size + 'px;',
        'height: ' + size + 'px;',
        'background: rgba(200, 150, 46, ' + opacity + ');',
        'border-radius: ' + (isRound ? '50%' : '2px') + ';',
        'transform: rotate(' + rotation + 'deg);',
        'animation: particleRise ' + duration + 's linear ' + delay + 's infinite;',
        'pointer-events: none;',
        'z-index: 1;'
      ].join('');
      
      overlay.appendChild(p);
    }
  }

  function revealDashboard() {
    var wrapper = document.querySelector('.wrapper');
    if (!wrapper) return;
    
    // Make wrapper visible
    wrapper.style.opacity = '1';
    wrapper.style.transition = 'opacity 0.3s ease';
    
    // Define dashboard elements with staggered entrance animations
    var elements = [
      { selector: '.sidebar', animation: 'anim-fade-left', baseDelay: 100 },
      { selector: '.top-navbar', animation: 'anim-fade-down', baseDelay: 200 },
      { selector: '.stat-card', animation: 'anim-fade-up', baseDelay: 300 },
      { selector: '.diagram-card', animation: 'anim-zoom', baseDelay: 400 },
      { selector: '.module-card', animation: 'anim-fade-up', baseDelay: 500 },
      { selector: '.ai-panel', animation: 'anim-fade-up', baseDelay: 600 }
    ];
    
    // Cascade animations with proper timing
    elements.forEach(function (item) {
      var els = document.querySelectorAll(item.selector);
      els.forEach(function (el, index) {
        el.style.opacity = '0';
        
        var delay = item.baseDelay + (index * 80);
        setTimeout(function () {
          el.style.opacity = '';
          el.classList.add(item.animation);
        }, delay);
      });
    });
  }

  function initFloatingIcons() {
    var icons = document.querySelectorAll('.feature-icon');
    icons.forEach(function (icon, i) {
      var cls = 'icon-float' + (i % 6 ? '-' + ((i % 6) + 1) : '');
      icon.classList.add(cls);
    });
  }

  window.showToast = function (message, type) {
    type = type || 'success';
    var container = document.querySelector('.uca-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'uca-toast-container';
      document.body.appendChild(container);
    }
    var icons = {
      success: '<i class="fas fa-check-circle uca-toast-icon success"></i>',
      error:   '<i class="fas fa-times-circle uca-toast-icon error"></i>',
      info:    '<i class="fas fa-info-circle uca-toast-icon info"></i>',
    };
    var toast = document.createElement('div');
    toast.className = 'uca-toast toast-' + type;
    toast.innerHTML =
      '<div class="uca-toast-body">' +
        (icons[type] || icons.info) +
        '<span>' + message + '</span>' +
      '</div>' +
      '<div class="uca-toast-progress"></div>';
    container.appendChild(toast);
    setTimeout(function () {
      toast.classList.add('toast-exit');
      setTimeout(function () { if (toast.parentNode) toast.remove(); }, 450);
    }, 3400);
  };

  /* ============================================================
     ON DOM READY
     ============================================================ */
  document.addEventListener('DOMContentLoaded', function () {

    // --- Bootstrap compat (tooltip / alert) ---
    if (typeof bootstrap !== 'undefined') {
      // Tooltips
      var tooltipEls = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltipEls.forEach(function (el) { new bootstrap.Tooltip(el); });

      // Auto dismiss Bootstrap alerts
      document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
          try { new bootstrap.Alert(alert).close(); } catch (e) {}
        }, 5000);
      });
    }

    // --- Init all modules ---
    initScrollReveal();
    initProgressBars();
    initFormFields();
    initCardAnimations();
    initNavbarScroll();
    initParticles();
    initCounters();
    initRipples();
    initCustomCursor();
    initPasswordStrength();
    initFormValidation();
    initWelcomeScreen();
    initFloatingIcons();

    // Fallback: force-reveal all scroll-reveal after 1.5s
    // in case IntersectionObserver doesn't fire (e.g. elements already in viewport)
    setTimeout(function () {
      // Force-reveal any elements still hidden after 1.5s
      document.querySelectorAll('.scroll-reveal.sr-init').forEach(function (el) {
        el.classList.add('visible');
        el.classList.remove('sr-init');
      });
    }, 1500);
  });

  /* ============================================================
     B2 — HERO TYPEWRITER (CSS-only, no JS override)
     ============================================================ */
  // Already defined above in pre-declarations section
  initTypewriterCleanup();

  /* ============================================================
     REMAINING EXECUTION CODE
     ============================================================ */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    var submitBtn = form.querySelector('[type="submit"]');
    if (!submitBtn) return;

    var originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML =
      '<span style="display:inline-flex;align-items:center;gap:8px;">' +
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1C0F07" stroke-width="2.5" style="animation:rotateSlow 0.8s linear infinite">' +
          '<circle cx="12" cy="12" r="9" stroke-dasharray="56" stroke-dashoffset="14"/>' +
        '</svg>' +
        'Chargement...' +
      '</span>';

    // Restore after 8s failsafe
    setTimeout(function () {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }, 8000);
  });

})();
