{{--
    Home page opening, about 12 seconds (timings in _intro.css):
      0 – 3s     film-leader countdown 3 · 2 · 1
      3 – 6s     "Welcome to FilmFolio · Your Life in Film"
      6 – 7s     the curtains part
      6.3 – 11s  the headline letters fly in from all over the screen to their places
      10 – 12s   the rest of the hero fades in
    Plays on every visit to Home; click, tap or Esc skips straight to the letters. CSS alone ends the
    overlay, so the page is never left covered, and nothing plays for visitors who prefer reduced motion.
--}}
<script>
    if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.documentElement.classList.add('intro-on');
    }
</script>
<div class="intro" id="intro" aria-hidden="true">
    <div class="intro-curtain left"></div>
    <div class="intro-curtain right"></div>

    <div class="intro-stage">
        <div class="intro-leader">
            <span class="intro-ring"></span>
            <span class="intro-cross"></span>
            <span class="intro-count"><b>3</b><b>2</b><b>1</b></span>
        </div>

        <div class="intro-message">
            <span class="intro-welcome">Welcome to</span>
            <span class="intro-title">
                @include('partials.logo')
                <span class="intro-beam"></span>
            </span>
            <span class="intro-tagline">Your Life in Film</span>
        </div>
    </div>

    <span class="intro-skip-hint">Click or press Esc to skip</span>
</div>
<script>
    (() => {
        const html = document.documentElement;
        const intro = document.getElementById('intro');
        if (! html.classList.contains('intro-on')) {
            intro.remove();
            return;
        }

        let skipped = false;
        const skip = () => { skipped = true; html.classList.add('intro-skip'); };
        intro.addEventListener('click', skip);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') skip(); }, { once: true });

        // Gone once it has finished (by itself or skipped). When the letters and the rest of the hero
        // have settled too, mark it done, so content swapped in later by live updates simply shows.
        intro.addEventListener('animationend', (e) => {
            if (e.target !== intro) return;
            intro.remove();
            setTimeout(() => html.classList.add('intro-done'), skipped ? 4500 : 6500);
        });

        // ---- Split each data-scatter heading into letters that start scattered across the screen ----
        const hexToRgb = (hex) => hex.trim().replace('#', '').match(/../g).map((part) => parseInt(part, 16));
        const colorAt = (stops, t) => {
            const position = t * (stops.length - 1);
            const index = Math.min(Math.floor(position), stops.length - 2);
            const [from, to] = [hexToRgb(stops[index]), hexToRgb(stops[index + 1])];
            const mix = position - index;
            return `rgb(${from.map((value, c) => Math.round(value + (to[c] - value) * mix)).join(',')})`;
        };
        const random = (min, max) => min + Math.random() * (max - min);

        const scatter = (root) => {
            const stops = root.dataset.scatterColors?.split(',');
            const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
            const textNodes = [];
            while (walker.nextNode()) textNodes.push(walker.currentNode);
            const total = Math.max(1, textNodes.reduce((sum, node) => sum + node.textContent.replace(/\s/g, '').length, 0) - 1);
            let count = 0;

            // Screen readers still get the words: the element (and any link inside) is labelled with its text.
            root.setAttribute('aria-label', root.textContent.replace(/\s+/g, ' ').trim());
            root.querySelectorAll('a').forEach((link) => link.setAttribute('aria-label', link.textContent.trim()));

            textNodes.forEach((node) => {
                const fragment = document.createDocumentFragment();
                node.textContent.split(/(\s+)/).forEach((part) => {
                    if (part === '') return;
                    if (/^\s+$/.test(part)) { fragment.append(' '); return; }
                    const word = document.createElement('span');
                    word.className = 'scatter-word';
                    word.setAttribute('aria-hidden', 'true');
                    [...part].forEach((letter) => {
                        const char = document.createElement('span');
                        char.className = 'scatter-char';
                        char.textContent = letter;
                        char.style.setProperty('--x', `${Math.round(random(-0.55, 0.55) * window.innerWidth)}px`);
                        char.style.setProperty('--y', `${Math.round(random(-0.6, 0.6) * window.innerHeight)}px`);
                        char.style.setProperty('--r', `${Math.round(random(-360, 360))}deg`);
                        char.style.setProperty('--s', random(0.3, 1.9).toFixed(2));
                        char.style.setProperty('--d', `${random(0, 2.6).toFixed(2)}s`);
                        if (stops) char.style.color = colorAt(stops, count / total);
                        count++;
                        word.append(char);
                    });
                    fragment.append(word);
                });
                node.replaceWith(fragment);
            });
            root.classList.add('is-scattered');
        };

        const start = () => document.querySelectorAll('[data-scatter]').forEach(scatter);
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
        else start();
    })();
</script>
