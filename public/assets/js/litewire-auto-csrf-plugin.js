/**
 * Litewire Auto CSRF Plugin
 * Automatically injects hidden CSRF token inputs into standard HTML forms,
 * while ignoring forms handled via Litewire directives (lw-post, lw-put, etc.).
 */
(function (global) {
  const DEFAULT_OPTIONS = {
    fieldName: '_csrf_token',
    metaName: 'csrf-token'
  };

  function isLitewireForm(form) {
    // Check if form itself has attributes starting with lw-
    const hasFormDirective = Array.from(form.attributes).some(attr => attr.name.startsWith('lw-'));
    if (hasFormDirective) return true;

    // Check if any element inside the form triggers Litewire request directives
    const hasChildDirective = form.querySelector('[lw-post], [lw-put], [lw-delete], [lw-get]');
    return Boolean(hasChildDirective);
  }

  function injectCsrfToken(root, options) {
    const metaTag = document.querySelector(`meta[name="${options.metaName}"]`);
    const csrfToken = metaTag?.getAttribute('content');
    if (!csrfToken) return;

    const forms = root.querySelectorAll ? root.querySelectorAll('form') : [];
    if (root.nodeType === Node.ELEMENT_NODE && root.tagName === 'FORM') {
      forms.push(root);
    }

    forms.forEach(form => {
      const method = (form.getAttribute('method') || 'GET').toUpperCase();
      if (method === 'GET') return;

      // Skip Litewire-handled forms and forms that already have the token
      if (isLitewireForm(form)) return;
      if (form.querySelector(`input[name="${options.fieldName}"]`)) return;

      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = options.fieldName;
      input.value = csrfToken;
      form.appendChild(input);
    });
  }

  function applyPlugin(litewireInstance, customOptions = {}) {
    if (!litewireInstance || litewireInstance._csrfPluginInstalled) return;

    const options = { ...DEFAULT_OPTIONS, ...customOptions };
    litewireInstance._csrfPluginInstalled = true;

    // Hook into Litewire's scan method
    const originalScan = litewireInstance.scan.bind(litewireInstance);
    litewireInstance.scan = function (root) {
      injectCsrfToken(root, options);
      return originalScan(root);
    };

    // Run initial scan on current DOM
    injectCsrfToken(document, options);
  }

  function initPlugin(options) {
    if (window.litewire) {
      applyPlugin(window.litewire, options);
      return;
    }

    // If Litewire hasn't initialized yet, poll briefly until ready
    const checkInterval = setInterval(() => {
      if (window.litewire) {
        clearInterval(checkInterval);
        applyPlugin(window.litewire, options);
      }
    }, 10);

    // Stop checking after 5 seconds if Litewire isn't found
    setTimeout(() => clearInterval(checkInterval), 5000);
  }

  // Auto-start when DOM is ready
  if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => initPlugin());
    } else {
      initPlugin();
    }
  }

  // Export for module support if needed
  global.LitewireAutoCsrfPlugin = { init: initPlugin, apply: applyPlugin };
})(typeof window !== 'undefined' ? window : this);