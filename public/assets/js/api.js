/* CameLog – API helper */
const API = (() => {
  async function request(method, path, body, options = {}) {
    const opts = {
      method,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' },
    };
    if (body instanceof FormData) {
      opts.body = body;
    } else if (body !== undefined && body !== null) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
    let res;
    try {
      res = await fetch(path, opts);
    } catch (e) {
      throw { offline: true, message: 'Brak połączenia z serwerem.' };
    }
    let data = null;
    const ct = res.headers.get('Content-Type') || '';
    if (ct.includes('application/json')) {
      data = await res.json().catch(() => null);
    } else {
      data = await res.text().catch(() => null);
    }
    if (!res.ok) {
      const err = (data && (data.error || data.message)) || res.statusText || 'Błąd';
      throw { status: res.status, message: err, data };
    }
    return data;
  }

  return {
    get: (p, opts) => request('GET', p, undefined, opts),
    post: (p, b, opts) => request('POST', p, b, opts),
    put: (p, b, opts) => request('PUT', p, b, opts),
    patch: (p, b, opts) => request('PATCH', p, b, opts),
    delete: (p, opts) => request('DELETE', p, undefined, opts),
    upload: (p, formData) => request('POST', p, formData),
  };
})();
