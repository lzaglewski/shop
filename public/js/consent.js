(function () {
  'use strict';

  const STORAGE_KEY = 'shop_cookie_consent_state';
  const COOKIE_NAME = 'cookie_consent_state';
  const STORAGE_VERSION = 1;
  const DEFAULT_PREFERENCES = Object.freeze({
    necessary: true,
    analytics: false,
    marketing: false,
    personalization: false
  });
  const CONSENT_CATEGORIES = ['necessary', 'analytics', 'marketing', 'personalization'];
  const ALWAYS_KEEP_COOKIES = new Set([
    'PHPSESSID',
    'REMEMBERME',
    COOKIE_NAME,
    'XSRF-TOKEN',
    'csrf_token',
    'sf_redirect'
  ]);
  const ANALYTICS_PREFIXES = ['_ga', '_gid', '_gcl', '_utm', '_gtm'];
  const MARKETING_PREFIXES = ['_fb', 'fbp', 'fbc', 'fr', 'ide', '_ttp', 'tt_'];

  const userId = typeof window.__USER_ID__ === 'number' ? window.__USER_ID__ : null;

  document.addEventListener('DOMContentLoaded', () => {
    const elements = {
      banner: document.getElementById('cookie-banner'),
      modal: document.getElementById('cc-modal'),
      accept: document.getElementById('cc-accept'),
      reject: document.getElementById('cc-reject'),
      settings: document.getElementById('cc-settings'),
      cancel: document.getElementById('cc-cancel'),
      form: document.getElementById('cc-form')
    };

    if (!elements.banner || !elements.modal || !elements.accept || !elements.reject || !elements.settings || !elements.form) {
      return;
    }

    let lastFocusedElement = null;
    let currentState = loadState();

    seedGoogleConsentDefaults();
    applyPreferencesToForm(currentState.preferences);
    updateBannerVisibility();
    exposePublicApi();
    attachEventListeners();

    // Broadcast initial state to integrations
    pushPreferencesToIntegrations(null);
    emitStateChange(null);

    synchronizeWithServer()
      .then(previous => {
        if (previous) {
          pushPreferencesToIntegrations(previous);
          emitStateChange(previous);
        }
      })
      .catch(error => {
        console.warn('Cookie consent sync failed', error);
      });

    function attachEventListeners() {
      elements.accept.addEventListener('click', () => {
        handleConsentChange({
          necessary: true,
          analytics: true,
          marketing: true,
          personalization: true
        });
      });

      elements.reject.addEventListener('click', () => {
        handleConsentChange({
          necessary: true,
          analytics: false,
          marketing: false,
          personalization: false
        });
      });

      elements.settings.addEventListener('click', () => {
        openModal();
      });

      elements.cancel?.addEventListener('click', () => {
        closeModal();
      });

      elements.modal.addEventListener('click', event => {
        if (event.target === elements.modal) {
          closeModal();
        }
      });

      elements.form.addEventListener('submit', event => {
        event.preventDefault();
        const data = new FormData(elements.form);
        handleConsentChange({
          necessary: true,
          analytics: data.has('analytics'),
          marketing: data.has('marketing'),
          personalization: data.has('personalization')
        });
        closeModal();
      });

      document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !elements.modal.classList.contains('hidden')) {
          event.preventDefault();
          closeModal();
        }
      });
    }

    function openModal() {
      lastFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
      elements.modal.classList.remove('hidden');
      const firstInput = elements.modal.querySelector('input[name="analytics"]');
      if (firstInput instanceof HTMLElement) {
        firstInput.focus();
      }
    }

    function closeModal() {
      elements.modal.classList.add('hidden');
      if (lastFocusedElement instanceof HTMLElement) {
        lastFocusedElement.focus();
      }
    }

    function handleConsentChange(preferences) {
      const normalized = normalizePreferences(preferences);
      const previous = clonePreferences(currentState.preferences);

      currentState.preferences = normalized;
      currentState.acknowledged = true;
      currentState.source = userId ? 'remote' : 'local';
      currentState.updatedAt = Date.now();

      saveState();
      applyPreferencesToForm(normalized);
      updateBannerVisibility();
      pushPreferencesToIntegrations(previous);
      emitStateChange(previous);

      if (userId) {
        persistRemotePreferences(normalized).catch(error => {
          console.warn('Cookie consent update failed', error);
        });
      }
    }

    function updateBannerVisibility() {
      if (currentState.acknowledged) {
        elements.banner.classList.add('hidden');
      } else {
        elements.banner.classList.remove('hidden');
      }
    }

    function applyPreferencesToForm(preferences) {
      const analytics = elements.form.querySelector('input[name="analytics"]');
      const marketing = elements.form.querySelector('input[name="marketing"]');
      const personalization = elements.form.querySelector('input[name="personalization"]');

      if (analytics instanceof HTMLInputElement) {
        analytics.checked = Boolean(preferences.analytics);
      }
      if (marketing instanceof HTMLInputElement) {
        marketing.checked = Boolean(preferences.marketing);
      }
      if (personalization instanceof HTMLInputElement) {
        personalization.checked = Boolean(preferences.personalization);
      }
    }

    function pushPreferencesToIntegrations(previousPreferences) {
      updateGoogleConsent();
      removeNonEssentialCookies(previousPreferences);
    }

    function emitStateChange(previousPreferences) {
      const detail = {
        preferences: clonePreferences(currentState.preferences),
        acknowledged: currentState.acknowledged,
        source: currentState.source,
        previous: previousPreferences ? clonePreferences(previousPreferences) : null
      };

      const event = new CustomEvent('cookieConsentUpdated', { detail });
      document.dispatchEvent(event);
    }

    function updateGoogleConsent() {
      ensureGtag();

      const payload = {
        analytics_storage: currentState.preferences.analytics ? 'granted' : 'denied',
        ad_storage: currentState.preferences.marketing ? 'granted' : 'denied',
        ad_user_data: currentState.preferences.marketing ? 'granted' : 'denied',
        ad_personalization: currentState.preferences.personalization ? 'granted' : 'denied',
        functionality_storage: currentState.preferences.personalization ? 'granted' : 'denied',
        security_storage: 'granted'
      };

      window.gtag('consent', 'update', payload);
    }

    function removeNonEssentialCookies(previousPreferences) {
      if (!previousPreferences) {
        return;
      }

      const revoked = CONSENT_CATEGORIES.filter(category =>
        category !== 'necessary' &&
        previousPreferences[category] &&
        !currentState.preferences[category]
      );

      if (!revoked.length) {
        return;
      }

      const cookies = document.cookie ? document.cookie.split(';') : [];

      cookies.forEach(cookieString => {
        const [rawName] = cookieString.split('=');
        const name = rawName ? rawName.trim() : '';

        if (!name || ALWAYS_KEEP_COOKIES.has(name)) {
          return;
        }

        const lowerName = name.toLowerCase();
        const analyticsRevoked = revoked.includes('analytics') && ANALYTICS_PREFIXES.some(prefix => lowerName.startsWith(prefix));
        const marketingRevoked = (revoked.includes('marketing') || revoked.includes('personalization')) &&
          (MARKETING_PREFIXES.some(prefix => lowerName.startsWith(prefix)) || lowerName.includes('ad'));

        if (analyticsRevoked || marketingRevoked) {
          document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;SameSite=Lax`;
        }
      });
    }

    function exposePublicApi() {
      const api = {
        getPreferences: () => clonePreferences(currentState.preferences),
        isAccepted: category => Boolean(currentState.preferences[category]),
        onChange: callback => {
          if (typeof callback === 'function') {
            document.addEventListener('cookieConsentUpdated', callback);
          }
        },
        openSettings: () => openModal()
      };

      window.CookieConsent = api;
    }

    function seedGoogleConsentDefaults() {
      ensureGtag();

      window.gtag('consent', 'default', {
        analytics_storage: 'denied',
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        functionality_storage: 'denied',
        security_storage: 'granted'
      });
    }

    function ensureGtag() {
      window.dataLayer = window.dataLayer || [];
      if (typeof window.gtag !== 'function') {
        window.gtag = function () {
          window.dataLayer.push(arguments);
        };
      }
    }

    async function synchronizeWithServer() {
      if (!userId) {
        return null;
      }

      let changed = false;
      let previous = clonePreferences(currentState.preferences);

      if (currentState.source === 'local' && currentState.acknowledged) {
        const migrated = await migratePreferences(currentState.preferences);
        if (migrated) {
          previous = clonePreferences(currentState.preferences);
          currentState.preferences = normalizePreferences(migrated);
          currentState.source = 'remote';
          currentState.acknowledged = true;
          currentState.updatedAt = Date.now();
          saveState();
          applyPreferencesToForm(currentState.preferences);
          updateBannerVisibility();
          changed = true;
        }
      }

      const remote = await fetchRemotePreferences();
      if (remote) {
        const normalizedRemote = normalizePreferences(remote);
        if (hasDifference(normalizedRemote, currentState.preferences) || currentState.source !== 'remote') {
          previous = clonePreferences(currentState.preferences);
          currentState.preferences = normalizedRemote;
          currentState.source = 'remote';
          currentState.acknowledged = true;
          currentState.updatedAt = Date.now();
          saveState();
          applyPreferencesToForm(currentState.preferences);
          updateBannerVisibility();
          changed = true;
        }
      }

      return changed ? previous : null;
    }

    async function migratePreferences(preferences) {
      try {
        const response = await fetch('/api/consents/migrate', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(preferences)
        });

        if (!response.ok) {
          return null;
        }

        const payload = await response.json();

        return payload?.data ?? null;
      } catch (error) {
        console.warn('Cookie consent migration failed', error);
        return null;
      }
    }

    async function fetchRemotePreferences() {
      try {
        const response = await fetch('/api/consents', {
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
          return null;
        }

        const payload = await response.json();

        return payload?.data ?? null;
      } catch (error) {
        console.warn('Cookie consent fetch failed', error);
        return null;
      }
    }

    async function persistRemotePreferences(preferences) {
      try {
        const response = await fetch('/api/consents', {
          method: 'PATCH',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(preferences)
        });

        if (!response.ok) {
          throw new Error(`Request failed with status ${response.status}`);
        }

        const payload = await response.json();
        const serverPreferences = payload?.data ? normalizePreferences(payload.data) : preferences;

        if (hasDifference(serverPreferences, currentState.preferences)) {
          const previous = clonePreferences(currentState.preferences);
          currentState.preferences = serverPreferences;
          currentState.updatedAt = Date.now();
          saveState();
          pushPreferencesToIntegrations(previous);
          emitStateChange(previous);
        } else {
          currentState.updatedAt = Date.now();
          saveState();
        }
      } catch (error) {
        throw error;
      }
    }

    function loadState() {
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
          return createDefaultState();
        }

        const parsed = JSON.parse(raw);
        if (!parsed || parsed.version !== STORAGE_VERSION) {
          return createDefaultState();
        }

        const preferences = normalizePreferences(parsed.preferences);

        return {
          preferences,
          acknowledged: Boolean(parsed.acknowledged),
          source: typeof parsed.source === 'string' ? parsed.source : (userId ? 'remote' : 'local'),
          updatedAt: typeof parsed.updatedAt === 'number' ? parsed.updatedAt : Date.now()
        };
      } catch (error) {
        return createDefaultState();
      }
    }

    function saveState() {
      const payload = {
        version: STORAGE_VERSION,
        preferences: currentState.preferences,
        acknowledged: currentState.acknowledged,
        source: currentState.source,
        updatedAt: currentState.updatedAt
      };

      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
      } catch (error) {
        // Ignore quota errors silently
      }

      setConsentCookie(currentState.preferences, currentState.acknowledged);
    }

    function createDefaultState() {
      return {
        preferences: clonePreferences(DEFAULT_PREFERENCES),
        acknowledged: false,
        source: userId ? 'remote' : 'local',
        updatedAt: Date.now()
      };
    }

    function normalizePreferences(preferences) {
      const normalized = clonePreferences(DEFAULT_PREFERENCES);

      if (preferences && typeof preferences === 'object') {
        CONSENT_CATEGORIES.forEach(category => {
          if (category === 'necessary') {
            return;
          }
          if (category in preferences) {
            normalized[category] = Boolean(preferences[category]);
          }
        });
      }

      return normalized;
    }

    function clonePreferences(preferences) {
      return {
        necessary: Boolean(preferences?.necessary),
        analytics: Boolean(preferences?.analytics),
        marketing: Boolean(preferences?.marketing),
        personalization: Boolean(preferences?.personalization)
      };
    }

    function hasDifference(next, previous) {
      return CONSENT_CATEGORIES.some(category => Boolean(next[category]) !== Boolean(previous[category]));
    }

    function setConsentCookie(preferences, acknowledged) {
      const payload = {
        preferences,
        acknowledged,
        updatedAt: currentState.updatedAt
      };

      const value = encodeURIComponent(JSON.stringify(payload));
      const maxAge = 60 * 60 * 24 * 365;

      document.cookie = `${COOKIE_NAME}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
    }
  });
})();
