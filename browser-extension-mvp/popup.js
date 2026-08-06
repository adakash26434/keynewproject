const baseUrlEl = document.getElementById('baseUrl');
const queryEl = document.getElementById('query');
const statusEl = document.getElementById('status');
const genLenEl = document.getElementById('genLen');
const generatedEl = document.getElementById('generated');
const resultsEl = document.getElementById('results');
const exactDomainOnlyEl = document.getElementById('exactDomainOnly');
const toggleBlockDomainEl = document.getElementById('toggleBlockDomain');
const domainInfoEl = document.getElementById('domainInfo');

function setStatus(text) {
  statusEl.textContent = text;
}

function normalizeBaseUrl(url) {
  const trimmed = String(url || '').trim().replace(/\/$/, '');
  return trimmed;
}

function openTab(url) {
  chrome.tabs.create({ url });
}

function hostFromUrl(input) {
  try {
    return new URL(String(input || '')).hostname.toLowerCase();
  } catch {
    return '';
  }
}

function baseDomain(host) {
  const parts = String(host || '').split('.').filter(Boolean);
  if (parts.length <= 2) return parts.join('.');
  return parts.slice(-2).join('.');
}

async function getActiveHost() {
  const tabs = await chrome.tabs.query({ active: true, currentWindow: true });
  return hostFromUrl(tabs[0] && tabs[0].url ? tabs[0].url : '');
}

async function getSafetyState() {
  return await chrome.storage.local.get(['exactDomainOnly', 'blockedDomains']);
}

function blockedSet(list) {
  return new Set(Array.isArray(list) ? list.map((v) => String(v).toLowerCase()) : []);
}

async function refreshDomainInfo() {
  const host = await getActiveHost();
  const data = await getSafetyState();
  const blocked = blockedSet(data.blockedDomains);
  if (!host) {
    domainInfoEl.textContent = 'No active domain detected.';
    return;
  }
  const isBlocked = blocked.has(host) || blocked.has(baseDomain(host));
  domainInfoEl.textContent = isBlocked ? `Blocked: ${host}` : `Current: ${host}`;
}

function escapeHtml(input) {
  return String(input || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function randomPassword(length) {
  const letters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
  const numbers = '23456789';
  const symbols = '!@#$%^&*()-_=+[]{}';
  const all = letters + numbers + symbols;

  let out = '';
  out += letters[Math.floor(Math.random() * letters.length)];
  out += letters[Math.floor(Math.random() * letters.length)];
  out += numbers[Math.floor(Math.random() * numbers.length)];
  out += symbols[Math.floor(Math.random() * symbols.length)];

  for (let i = 0; i < Math.max(8, length - 4); i++) {
    out += all[Math.floor(Math.random() * all.length)];
  }

  return out.split('').sort(() => Math.random() - 0.5).join('');
}

async function copyText(text) {
  try {
    await navigator.clipboard.writeText(text);
    setStatus('Copied to clipboard.');
  } catch {
    setStatus('Copy failed.');
  }
}

async function fetchCredentials() {
  const base = normalizeBaseUrl(baseUrlEl.value);
  if (!base) {
    setStatus('Set Vault URL first.');
    return;
  }

  const q = queryEl.value.trim();
  if (q.length < 2) {
    setStatus('Type at least 2 characters.');
    return;
  }

  setStatus('Searching vault...');
  resultsEl.innerHTML = '';

  try {
    const res = await fetch(base + '/api/passwords/search?q=' + encodeURIComponent(q), {
      credentials: 'include'
    });

    if (!res.ok) {
      setStatus('Search failed. Ensure you are logged in to vault.');
      return;
    }

    const data = await res.json();
    let rows = Array.isArray(data.results) ? data.results : [];
    if (rows.length === 0) {
      setStatus('No credentials found.');
      return;
    }

    const activeHost = await getActiveHost();
    const safety = await getSafetyState();
    const exactOnly = safety.exactDomainOnly !== false;
    const blocked = blockedSet(safety.blockedDomains);

    if (activeHost && (blocked.has(activeHost) || blocked.has(baseDomain(activeHost)))) {
      setStatus('Current domain is blocked for autofill/search.');
      return;
    }

    rows = rows.map((r) => {
      const u = String(r.url || '').toLowerCase();
      const rowHost = hostFromUrl(u);
      const score = activeHost && rowHost === activeHost ? 3 : (activeHost && baseDomain(rowHost) === baseDomain(activeHost) ? 2 : (u.includes(queryEl.value.trim().toLowerCase()) ? 1 : 0));
      return { ...r, _score: score };
    }).filter((r) => !exactOnly || !activeHost || hostFromUrl(r.url) === activeHost)
      .sort((a, b) => b._score - a._score);

    if (rows.length === 0) {
      setStatus(exactOnly ? 'No exact-domain credentials found.' : 'No credentials found.');
      return;
    }

    resultsEl.innerHTML = rows.map((r) => {
      const title = escapeHtml(r.title || 'Untitled');
      const sub = escapeHtml(r.username || r.url || 'No username');
      const hint = r._score >= 2 ? '<span class="result-sub">Best domain match</span>' : '';
      return `
        <div class="result-item">
          <div class="result-title">${title}</div>
          <div class="result-sub">${sub}</div>
          ${hint}
          <div class="result-actions">
            <button data-act="fill" data-id="${Number(r.id)}">Autofill</button>
            <button data-act="copy" data-id="${Number(r.id)}" class="alt">Copy</button>
          </div>
        </div>
      `;
    }).join('');

    setStatus('Select an item to autofill or copy.');
  } catch {
    setStatus('Search failed.');
  }
}

async function getCredentialById(id) {
  const base = normalizeBaseUrl(baseUrlEl.value);
  const res = await fetch(base + '/passwords/' + encodeURIComponent(id) + '/reveal', {
    credentials: 'include'
  });
  if (!res.ok) {
    throw new Error('Unable to fetch credential.');
  }
  return await res.json();
}

async function autofillActiveTab(credential) {
  const tabs = await chrome.tabs.query({ active: true, currentWindow: true });
  const tab = tabs[0];
  if (!tab || !tab.id) {
    setStatus('No active tab found.');
    return;
  }

  const safety = await getSafetyState();
  const blocked = blockedSet(safety.blockedDomains);
  const host = hostFromUrl(tab.url || '');
  if (host && (blocked.has(host) || blocked.has(baseDomain(host)))) {
    setStatus('Blocked domain. Autofill prevented.');
    return;
  }

  await chrome.scripting.executeScript({
    target: { tabId: tab.id },
    args: [credential],
    func: (cred) => {
      const all = Array.from(document.querySelectorAll('input'));
      const visible = all.filter((el) => {
        const style = window.getComputedStyle(el);
        return style.display !== 'none' && style.visibility !== 'hidden' && !el.disabled;
      });

      const passwordInput = visible.find((el) => el.type === 'password') || null;
      const userInput = visible.find((el) => {
        const sig = ((el.name || '') + ' ' + (el.id || '') + ' ' + (el.placeholder || '')).toLowerCase();
        if (el.type === 'email') return true;
        if (el.type === 'text' || el.type === 'tel') {
          return sig.includes('user') || sig.includes('email') || sig.includes('login') || sig.includes('phone');
        }
        return false;
      }) || visible.find((el) => el.type === 'text') || null;

      const setValue = (el, value) => {
        if (!el || typeof value !== 'string') return;
        el.focus();
        el.value = value;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
      };

      if (userInput && cred.username) setValue(userInput, cred.username);
      if (passwordInput && cred.password) setValue(passwordInput, cred.password);
    }
  });

  setStatus('Autofill sent to active tab.');
}

chrome.storage.local.get(['baseUrl'], ({ baseUrl }) => {
  baseUrlEl.value = baseUrl || '';
});

chrome.storage.local.get(['exactDomainOnly'], ({ exactDomainOnly }) => {
  exactDomainOnlyEl.checked = exactDomainOnly !== false;
});

baseUrlEl.addEventListener('change', () => {
  const value = normalizeBaseUrl(baseUrlEl.value);
  chrome.storage.local.set({ baseUrl: value });
});

exactDomainOnlyEl.addEventListener('change', () => {
  chrome.storage.local.set({ exactDomainOnly: !!exactDomainOnlyEl.checked });
});

toggleBlockDomainEl.addEventListener('click', async () => {
  const host = await getActiveHost();
  if (!host) {
    setStatus('No active domain to block.');
    return;
  }

  const data = await getSafetyState();
  const blocked = blockedSet(data.blockedDomains);
  if (blocked.has(host)) {
    blocked.delete(host);
    setStatus('Domain unblocked.');
  } else {
    blocked.add(host);
    setStatus('Domain blocked.');
  }

  chrome.storage.local.set({ blockedDomains: Array.from(blocked) });
  refreshDomainInfo();
});

document.getElementById('openDashboard').addEventListener('click', () => {
  const base = normalizeBaseUrl(baseUrlEl.value);
  if (!base) return setStatus('Set Vault URL first.');
  openTab(base + '/dashboard');
});

document.getElementById('openPasswords').addEventListener('click', () => {
  const base = normalizeBaseUrl(baseUrlEl.value);
  if (!base) return setStatus('Set Vault URL first.');
  openTab(base + '/passwords');
});

document.getElementById('runSearch').addEventListener('click', () => {
  const base = normalizeBaseUrl(baseUrlEl.value);
  if (!base) return setStatus('Set Vault URL first.');
  const q = encodeURIComponent(queryEl.value.trim());
  openTab(base + '/passwords?q=' + q);
});

document.getElementById('searchVault').addEventListener('click', fetchCredentials);

resultsEl.addEventListener('click', async (event) => {
  const target = event.target;
  if (!(target instanceof HTMLElement)) return;

  const action = target.getAttribute('data-act');
  const id = Number(target.getAttribute('data-id'));
  if (!action || !id) return;

  try {
    const credential = await getCredentialById(id);
    if (!credential || typeof credential !== 'object') {
      setStatus('Credential not available.');
      return;
    }

    if (action === 'copy') {
      if (credential.password) {
        await copyText(credential.password);
      }
      return;
    }

    if (action === 'fill') {
      await autofillActiveTab({
        username: credential.username || '',
        password: credential.password || ''
      });
    }
  } catch {
    setStatus('Action failed. Ensure vault session is active.');
  }
});

document.getElementById('generate').addEventListener('click', () => {
  const len = Number(genLenEl.value || 16);
  generatedEl.value = randomPassword(Math.max(12, Math.min(64, len)));
  setStatus('Generated new password.');
});

document.getElementById('copyGenerated').addEventListener('click', () => {
  if (!generatedEl.value) {
    generatedEl.value = randomPassword(16);
  }
  copyText(generatedEl.value);
});

refreshDomainInfo();
