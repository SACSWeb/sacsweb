/*!
 * SACSWeb Preferences Manager
 * Aplica tema, acessibilidade e densidade em todas as páginas.
 */
(function () {
    'use strict';

    const STORAGE_KEY = 'sacsweb_preferencias';
    const root = document.documentElement;

    const DEFAULT_PREFERENCES = {
        tema: 'dark',
        tamanho_fonte: 'medio',
        alto_contraste: 0,
        reduzir_animacoes: 0,
        leitor_tela: 0,
        espacamento: 'normal',
        densidade_info: 'media',
        notificacoes_email: 1,
        notificacoes_push: 0
    };

    const BOOL_FIELDS = [
        'alto_contraste',
        'reduzir_animacoes',
        'leitor_tela',
        'notificacoes_email',
        'notificacoes_push'
    ];

    const FONT_SIZE_MAP = {
        pequeno: { className: 'font-size-small', size: '14px' },
        medio: { className: 'font-size-medium', size: '16px' },
        grande: { className: 'font-size-large', size: '18px' }
    };

    const SPACING_CLASS_MAP = {
        compacto: 'spacing-compact',
        normal: 'spacing-normal',
        amplo: 'spacing-wide'
    };

    const DENSITY_CLASS_MAP = {
        baixa: 'density-baixa',
        media: 'density-media',
        alta: 'density-alta'
    };

    const state = {
        prefs: { ...DEFAULT_PREFERENCES }
    };

    const systemThemeQuery = window.matchMedia ? window.matchMedia('(prefers-color-scheme: light)') : null;
    const systemMotionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

    function truthy(value) {
        return value === true || value === 'true' || value === 1 || value === '1' || value === 'on';
    }

    function parsePreferences(payload) {
        if (!payload || typeof payload !== 'object') {
            return {};
        }

        const cleaned = {};

        if (['dark', 'light', 'auto'].includes(payload.tema)) {
            cleaned.tema = payload.tema;
        }

        if (['pequeno', 'medio', 'grande'].includes(payload.tamanho_fonte)) {
            cleaned.tamanho_fonte = payload.tamanho_fonte;
        }

        if (['compacto', 'normal', 'amplo'].includes(payload.espacamento)) {
            cleaned.espacamento = payload.espacamento;
        }

        if (['baixa', 'media', 'alta'].includes(payload.densidade_info)) {
            cleaned.densidade_info = payload.densidade_info;
        }

        BOOL_FIELDS.forEach((flag) => {
            if (payload[flag] !== undefined) {
                cleaned[flag] = truthy(payload[flag]) ? 1 : 0;
            }
        });

        return cleaned;
    }

    function readLocalStorage() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (error) {
            console.warn('SACSWeb: não foi possível ler preferências locais.', error);
            return {};
        }
    }

    function writeLocalStorage(preferences) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences));
        } catch (error) {
            console.warn('SACSWeb: não foi possível salvar preferências locais.', error);
        }
    }

    function toggleClass(className, condition) {
        if (!className) return;
        root.classList[condition ? 'add' : 'remove'](className);
    }

    function clearClassGroup(classNames) {
        classNames.forEach((className) => root.classList.remove(className));
    }

    function setResolvedTheme(theme) {
        clearClassGroup(['theme-dark', 'theme-light']);
        root.classList.add(theme === 'light' ? 'theme-light' : 'theme-dark');
    }

    function applyTheme(preferences) {
        const theme = preferences.tema || 'dark';
        toggleClass('theme-auto', theme === 'auto');

        if (theme === 'auto') {
            const prefersLight = systemThemeQuery ? systemThemeQuery.matches : false;
            setResolvedTheme(prefersLight ? 'light' : 'dark');
        } else {
            setResolvedTheme(theme === 'light' ? 'light' : 'dark');
        }
    }

    function applyFontSize(preferences) {
        const config = FONT_SIZE_MAP[preferences.tamanho_fonte] || FONT_SIZE_MAP.medio;
        clearClassGroup(Object.values(FONT_SIZE_MAP).map((item) => item.className));
        root.classList.add(config.className);
        root.style.setProperty('--font-size-base', config.size);
    }

    function applySpacing(preferences) {
        const className = SPACING_CLASS_MAP[preferences.espacamento] || SPACING_CLASS_MAP.normal;
        clearClassGroup(Object.values(SPACING_CLASS_MAP));
        root.classList.add(className);
    }

    function applyDensity(preferences) {
        const className = DENSITY_CLASS_MAP[preferences.densidade_info] || DENSITY_CLASS_MAP.media;
        clearClassGroup(Object.values(DENSITY_CLASS_MAP));
        root.classList.add(className);
    }

    function applyContrast(preferences) {
        toggleClass('high-contrast', !!preferences.alto_contraste);
    }

    function applyReaderMode(preferences) {
        toggleClass('screen-reader-mode', !!preferences.leitor_tela);
    }

    function applyMotion(preferences) {
        const shouldReduce = !!preferences.reduzir_animacoes || (systemMotionQuery ? systemMotionQuery.matches : false);
        toggleClass('reduce-motion', shouldReduce);
    }

    function applyAll(options = {}) {
        applyTheme(state.prefs);
        applyFontSize(state.prefs);
        applySpacing(state.prefs);
        applyDensity(state.prefs);
        applyContrast(state.prefs);
        applyMotion(state.prefs);
        applyReaderMode(state.prefs);

        if (!options.silent) {
            document.dispatchEvent(new CustomEvent('sacsweb:preferences-change', {
                detail: { preferences: { ...state.prefs } }
            }));
        }
    }

    function updatePreferences(newPrefs = {}, options = {}) {
        state.prefs = { ...state.prefs, ...newPrefs };
        applyAll({ silent: options.silent });

        if (options.persist !== false) {
            writeLocalStorage(state.prefs);
        }

        return { ...state.prefs };
    }

    function handleSystemThemeChange(event) {
        if (state.prefs.tema === 'auto') {
            setResolvedTheme(event.matches ? 'light' : 'dark');
        }
    }

    function handleSystemMotionChange() {
        applyMotion(state.prefs);
    }

    function handleStorageEvent(event) {
        if (event.key !== STORAGE_KEY || !event.newValue) {
            return;
        }

        try {
            const incoming = parsePreferences(JSON.parse(event.newValue));
            updatePreferences(incoming, { persist: false });
        } catch (error) {
            console.warn('SACSWeb: falha ao sincronizar preferências entre abas.', error);
        }
    }

    function init() {
        const stored = parsePreferences(readLocalStorage());
        const server = parsePreferences(window.SACSWEB_PREFERENCES || {});
        state.prefs = { ...DEFAULT_PREFERENCES, ...stored, ...server };

        writeLocalStorage(state.prefs);
        applyAll({ silent: true });

        window.SACSWEB_PREFERENCES_API = {
            get: () => ({ ...state.prefs }),
            apply: (prefs, options) => updatePreferences(parsePreferences(prefs), options),
            reset: () => updatePreferences({ ...DEFAULT_PREFERENCES }),
            syncFromServer: (prefs) => updatePreferences(parsePreferences(prefs))
        };

        document.dispatchEvent(new CustomEvent('sacsweb:preferences-ready', {
            detail: { preferences: { ...state.prefs } }
        }));

        if (systemThemeQuery) {
            const listener = handleSystemThemeChange;
            if (systemThemeQuery.addEventListener) {
                systemThemeQuery.addEventListener('change', listener);
            } else if (systemThemeQuery.addListener) {
                systemThemeQuery.addListener(listener);
            }
        }

        if (systemMotionQuery) {
            const motionListener = handleSystemMotionChange;
            if (systemMotionQuery.addEventListener) {
                systemMotionQuery.addEventListener('change', motionListener);
            } else if (systemMotionQuery.addListener) {
                systemMotionQuery.addListener(motionListener);
            }
        }

        window.addEventListener('storage', handleStorageEvent);
        delete window.SACSWEB_PREFERENCES;
    }

    init();
})();

