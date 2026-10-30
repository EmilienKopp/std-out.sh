import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

/**
 * The app theme is locked to the std-out pair; only light/dark/system
 * appearance is user-selectable. The full daisyUI theme catalog stays
 * compiled in (themes: all) because deck branding samples palettes from it —
 * THEME_OPTIONS below exists solely for that picker.
 */
export type ThemeOption = {
    name: string;
    label: string;
    scheme: ResolvedAppearance;
};

const DARK_THEMES = new Set([
    'stdout-dark',
    'dark',
    'synthwave',
    'halloween',
    'forest',
    'aqua',
    'black',
    'luxury',
    'dracula',
    'business',
    'night',
    'coffee',
    'dim',
    'sunset',
    'abyss',
]);

const BUILTIN_THEMES = [
    'light',
    'dark',
    'cupcake',
    'bumblebee',
    'emerald',
    'corporate',
    'synthwave',
    'retro',
    'cyberpunk',
    'valentine',
    'halloween',
    'garden',
    'forest',
    'aqua',
    'lofi',
    'pastel',
    'fantasy',
    'wireframe',
    'black',
    'luxury',
    'dracula',
    'cmyk',
    'autumn',
    'business',
    'acid',
    'lemonade',
    'night',
    'coffee',
    'winter',
    'dim',
    'nord',
    'sunset',
    'caramellatte',
    'abyss',
    'silk',
];

/* Palette sources for deck branding — not app themes. */
export const THEME_OPTIONS: ThemeOption[] = BUILTIN_THEMES.map((name) => ({
    name,
    label: name.charAt(0).toUpperCase() + name.slice(1),
    scheme: (DARK_THEMES.has(name) ? 'dark' : 'light') as ResolvedAppearance,
}));

export type ThemeState = {
    appearance: {
        value: Appearance;
    };
    resolvedAppearance: () => ResolvedAppearance;
    resolvedTheme: () => string;
    updateAppearance: (value: Appearance) => void;
};

const appearance = $state<{ value: Appearance }>({ value: 'system' });

let themeChangeMediaQuery: MediaQueryList | null = null;

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const isDarkMode = (value: Appearance): boolean => {
    return value === 'dark' || (value === 'system' && prefersDark());
};

const getResolvedTheme = (): string => {
    return isDarkMode(appearance.value) ? 'stdout-dark' : 'stdout';
};

const getResolvedAppearance = (): ResolvedAppearance => {
    return isDarkMode(appearance.value) ? 'dark' : 'light';
};

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const applyTheme = (): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const isDark = isDarkMode(appearance.value);

    document.documentElement.dataset.theme = getResolvedTheme();
    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
};

const getStoredAppearance = (): Appearance => {
    if (typeof window === 'undefined') {
        return 'system';
    }

    const stored = localStorage.getItem('appearance');

    return stored === 'light' || stored === 'dark' || stored === 'system'
        ? stored
        : 'system';
};

const handleSystemThemeChange = (): void => {
    applyTheme();
};

const detachThemeChangeListener = (): void => {
    if (!themeChangeMediaQuery) {
        return;
    }

    themeChangeMediaQuery.removeEventListener(
        'change',
        handleSystemThemeChange,
    );
    themeChangeMediaQuery = null;
};

export function initializeTheme(): () => void {
    if (typeof window === 'undefined') {
        return () => {};
    }

    if (!localStorage.getItem('appearance')) {
        localStorage.setItem('appearance', 'system');
        setCookie('appearance', 'system');
    }

    /* Pinned daisyUI app themes were retired with the std-out rebrand. */
    if (localStorage.getItem('theme')) {
        localStorage.removeItem('theme');
        setCookie('theme', '', -1);
    }

    appearance.value = getStoredAppearance();
    applyTheme();

    detachThemeChangeListener();
    themeChangeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    themeChangeMediaQuery.addEventListener('change', handleSystemThemeChange);

    return detachThemeChangeListener;
}

export function updateAppearance(value: Appearance): void {
    appearance.value = value;

    if (typeof window !== 'undefined') {
        localStorage.setItem('appearance', value);
    }

    setCookie('appearance', value);
    applyTheme();
}

export function themeState(): ThemeState {
    return {
        appearance,
        resolvedAppearance: getResolvedAppearance,
        resolvedTheme: getResolvedTheme,
        updateAppearance,
    };
}
