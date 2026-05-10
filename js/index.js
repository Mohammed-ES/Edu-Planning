document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                var href = this.getAttribute('href');
                if (href !== '#') {
                    e.preventDefault();
                    var target = document.querySelector(href);
                    if (target) {
                        var offset = 80;
                        var top = target.getBoundingClientRect().top + window.scrollY - offset;
                        window.scrollTo({ top: top, behavior: 'smooth' });
                    }
                }
            });
        });

        // Override stat-number "24/7" — don't animate since it's not a numeric counter
        var el247 = document.querySelector('[data-target="0"]');
        if (el247) {
            el247.textContent = '24/7';
            el247.removeAttribute('data-target');
        }