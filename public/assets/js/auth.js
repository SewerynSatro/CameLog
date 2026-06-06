/* CameLog – widoki autoryzacji oraz profil. */

const Auth = {
  async fetchUser() {
    try {
      const r = await API.get('/api/auth/me');
      window.CURRENT_USER = r.user;
      return r.user;
    } catch (e) {
      window.CURRENT_USER = null;
      return null;
    }
  },

  renderLogin() {
    const root = document.getElementById('app');
    root.className = 'app no-shell';
    root.innerHTML = `
      <div class="auth-shell">
        <div class="auth-card">
          <form class="auth-form" id="login-form" novalidate>
            <div class="auth-brand">
              <img src="/assets/images/logo.svg" alt="" />
              <div>
                <div class="name">CameLog</div>
                <div class="text-muted" style="font-size:12px">Desert Oasis Care</div>
              </div>
            </div>
            <h1>Zaloguj się do CameLog</h1>
            <p class="text-muted">Zadbaj o swoje rośliny i nie przegap żadnego podlewania.</p>

            <div class="field">
              <label class="field-label" for="email">Adres e-mail</label>
              <div class="input-wrap">
                <span class="leading-icon">${Icons.email}</span>
                <input type="email" id="email" name="email" class="input has-icon" placeholder="twoj@email.com" required autocomplete="email" />
              </div>
            </div>

            <div class="field">
              <div class="password-row">
                <label class="field-label" for="password">Hasło</label>
                <a href="#" class="muted-link" onclick="event.preventDefault();UI.toast('Skontaktuj się z administratorem konta','info')">Zapomniałeś hasła?</a>
              </div>
              <div class="input-wrap">
                <span class="leading-icon">${Icons.lock}</span>
                <input type="password" id="password" name="password" class="input has-icon" placeholder="••••••••" required autocomplete="current-password" />
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Zaloguj się ${Icons.arrowRight}</button>

            <div class="auth-link-row">Nie masz konta? <a href="/register" data-link>Zarejestruj się</a></div>
          </form>
          <div class="auth-art">
            <div class="auth-art-card">
              <div class="reminder">
                <div class="icon">${Icons.drop}</div>
                <div>
                  <div class="label-sm">Przypomnienie</div>
                  <div style="font-weight:700">Czas podlać Fikusa</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>`;
    document.getElementById('login-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      try {
        const r = await API.post('/api/auth/login', { email: fd.get('email'), password: fd.get('password') });
        UI.toast('Witaj z powrotem, ' + r.user.name + '!', 'success');
        window.CURRENT_USER = r.user;
        Router.navigate('/dashboard');
      } catch (err) {
        UI.toast(err.message || 'Nie udało się zalogować', 'error');
      }
    });
  },

  renderRegister() {
    const root = document.getElementById('app');
    root.className = 'app no-shell';
    root.innerHTML = `
      <div class="auth-shell">
        <div class="auth-card">
          <form class="auth-form" id="register-form" novalidate>
            <div class="auth-brand">
              <img src="/assets/images/logo.svg" alt="" />
              <div>
                <div class="name">CameLog</div>
                <div class="text-muted" style="font-size:12px">Desert Oasis Care</div>
              </div>
            </div>
            <h1>Załóż konto</h1>
            <p class="text-muted">Pielęgnuj swoją oazę razem z nami.</p>

            <div class="field">
              <label class="field-label" for="name">Imię</label>
              <input type="text" id="name" name="name" class="input" placeholder="Twoje imię" required minlength="2" />
            </div>
            <div class="field">
              <label class="field-label" for="email">Adres e-mail</label>
              <input type="email" id="email" name="email" class="input" placeholder="twoj@email.com" required />
            </div>
            <div class="form-grid">
              <div class="field">
                <label class="field-label" for="password">Hasło</label>
                <input type="password" id="password" name="password" class="input" required minlength="6" />
              </div>
              <div class="field">
                <label class="field-label" for="password_confirm">Powtórz hasło</label>
                <input type="password" id="password_confirm" name="password_confirm" class="input" required minlength="6" />
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Utwórz konto ${Icons.arrowRight}</button>
            <div class="auth-link-row">Masz już konto? <a href="/login" data-link>Zaloguj się</a></div>
          </form>
          <div class="auth-art">
            <div class="auth-art-card">
              <div class="reminder">
                <div class="icon">${Icons.leaf}</div>
                <div>
                  <div class="label-sm">CameLog</div>
                  <div style="font-weight:700">Twoja zielona oaza</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>`;
    document.getElementById('register-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const password = fd.get('password');
      const confirm = fd.get('password_confirm');
      if (password !== confirm) {
        UI.toast('Hasła muszą być identyczne', 'error');
        return;
      }
      try {
        const r = await API.post('/api/auth/register', {
          name: fd.get('name'), email: fd.get('email'),
          password, password_confirm: confirm,
        });
        UI.toast('Konto utworzone! Witaj w CameLog 🌱', 'success');
        window.CURRENT_USER = r.user;
        Router.navigate('/dashboard');
      } catch (err) {
        UI.toast(err.message || 'Nie udało się zarejestrować', 'error');
      }
    });
  },

  renderProfile(user) {
    const initials = (user.name || '?').split(' ').map(s => s[0]).slice(0,2).join('').toUpperCase();
    const content = `
      <section class="page-head" style="display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:20px">
          <div class="avatar" style="width:80px;height:80px;font-size:28px;border-radius:24px">${initials}</div>
          <div>
            <h1>${UI.escapeHtml(user.name)}</h1>
            <p class="text-muted">${UI.escapeHtml(user.email)} · ${user.role === 'admin' ? 'Administrator' : 'Opiekun roślin'}</p>
          </div>
        </div>
        <button class="btn btn-outline" id="btn-logout">Wyloguj się</button>
      </section>

      <div class="two-col">
        <div>
          <div class="card mb-3">
            <h2>Dane konta</h2>
            <form id="profile-form" class="mt-2">
              <div class="form-grid">
                <div class="field">
                  <label class="field-label">Imię</label>
                  <input class="input" name="name" value="${UI.escapeHtml(user.name)}" required />
                </div>
                <div class="field">
                  <label class="field-label">Email</label>
                  <input class="input" name="email" type="email" value="${UI.escapeHtml(user.email)}" required />
                </div>
              </div>
              <div class="field mt-2">
                <label class="field-label">Bio (opcjonalnie)</label>
                <textarea class="textarea" name="bio">${UI.escapeHtml(user.bio || '')}</textarea>
              </div>
              <div class="mt-3" style="display:flex;justify-content:flex-end">
                <button class="btn btn-primary" type="submit">Zapisz zmiany</button>
              </div>
            </form>
          </div>

          <div class="card">
            <h2>Preferencje powiadomień</h2>
            <p class="text-muted">Powiadomienia in-app i przypomnienia o pielęgnacji.</p>
            <div class="mt-2 flex flex-col gap-2">
              <label class="checkbox"><input type="checkbox" checked /> Powiadomienia o podlewaniu w aplikacji</label>
              <label class="checkbox"><input type="checkbox" checked /> Powiadomienia o nawożeniu w aplikacji</label>
              <label class="checkbox"><input type="checkbox" /> Newsletter CameLog (poza MVP)</label>
            </div>
          </div>
        </div>

        <aside>
          <div class="card mb-3">
            <h2>Bezpieczeństwo</h2>
            <p class="text-muted">Zmień hasło, aby zachować bezpieczeństwo konta.</p>
            <form id="password-form" class="mt-2">
              <div class="field">
                <label class="field-label">Aktualne hasło</label>
                <input class="input" type="password" name="current_password" required />
              </div>
              <div class="field mt-2">
                <label class="field-label">Nowe hasło</label>
                <input class="input" type="password" name="new_password" minlength="6" required />
              </div>
              <button class="btn btn-outline btn-block mt-3" type="submit">Aktualizuj hasło</button>
            </form>
          </div>

          <div class="card-tinted">
            <div class="text-muted" style="text-align:center">
              ${Icons.leaf}
              <h3 class="mt-2">Twoja oaza</h3>
              <p>Pod twoją czułą opieką</p>
            </div>
          </div>
        </aside>
      </div>`;
    UI.renderShell({ active: '/profile', user, content });

    document.getElementById('btn-logout').addEventListener('click', async () => {
      await API.post('/api/auth/logout');
      window.CURRENT_USER = null;
      UI.toast('Wylogowano', 'info');
      Router.navigate('/login');
    });

    document.getElementById('profile-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      try {
        await API.patch('/api/auth/profile', { name: fd.get('name'), email: fd.get('email'), bio: fd.get('bio') });
        UI.toast('Dane zaktualizowane', 'success');
        await Auth.fetchUser();
      } catch (err) { UI.toast(err.message || 'Błąd zapisu', 'error'); }
    });

    document.getElementById('password-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      try {
        await API.patch('/api/auth/password', {
          current_password: fd.get('current_password'),
          new_password: fd.get('new_password'),
        });
        UI.toast('Hasło zaktualizowane', 'success');
        e.target.reset();
      } catch (err) { UI.toast(err.message || 'Nie udało się zmienić hasła', 'error'); }
    });
  },
};
