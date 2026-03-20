<template>
  <div class="app home-root">
    <header class="topbar">
      <div class="wrap topbar__in">
        <button class="menuBtn" type="button" @click="openMenu" aria-label="Open menu">
          <span class="menuI"></span>
          <span class="menuI"></span>
          <span class="menuI"></span>
        </button>

        <div class="brand" @click="scrollToTop">DocBox</div>

        <nav class="topLinks">
          <button class="topLink" type="button" @click="scrollToSection('features')">Features</button>
          <button class="topLink" type="button" @click="scrollToSection('preview')">Preview</button>
          <button class="topLink" type="button" @click="scrollToSection('support')">Support</button>
        </nav>

        <div class="auth">
          <template v-if="auth.isAuthenticated">
            <button class="authLink" type="button" @click="goDashboard">{{ t('nav.dashboard') }}</button>
            <button class="authLink" type="button" @click="logout">{{ t('nav.logout') }}</button>
          </template>
          <template v-else>
            <button class="authLink" type="button" @click="goLogin">{{ t('nav.login') }}</button>
            <button class="authLink" type="button" @click="goRegister">{{ t('nav.register') }}</button>
          </template>
        </div>
      </div>
    </header>

    <div class="overlay" :class="{ show: menuOpen }" @click="closeMenu"></div>

    <aside class="side" :class="{ show: menuOpen }">
      <div class="sideTop">
        <div class="sideTitle">DocBox</div>
        <button class="closeBtn" type="button" @click="closeMenu">×</button>
      </div>
      <div class="sideLinks">
        <button class="sideLink" type="button" @click="mobileNav('features')">Features</button>
        <button class="sideLink" type="button" @click="mobileNav('preview')">Preview</button>
        <button class="sideLink" type="button" @click="mobileNav('support')">Support</button>
      </div>
      <div class="sideAuth">
        <template v-if="auth.isAuthenticated">
          <button class="sideAuthBtn" type="button" @click="goDashboard">{{ t('nav.dashboard') }}</button>
          <button class="sideAuthBtn" type="button" @click="logout">{{ t('nav.logout') }}</button>
        </template>
        <template v-else>
          <button class="sideAuthBtn" type="button" @click="goLogin">{{ t('nav.login') }}</button>
          <button class="sideAuthBtn" type="button" @click="goRegister">{{ t('nav.register') }}</button>
        </template>
      </div>
    </aside>

    <main>
      <section class="bg hero up show">
        <div class="wrap hero__in">
          <div class="kicker">Store documents. Simply.</div>
          <h1 class="title">DocBox</h1>
          <p class="subtitle">Upload, organize and find files fast and safely.</p>
          <div class="actions">
            <button class="btnSolid" type="button" @click="scrollToSection('features')">Learn more</button>
            <button class="btnOutline" type="button" @click="goRegister">Get started</button>
          </div>
        </div>
      </section>

      <section id="features" class="section up show">
        <div class="wrap">
          <h2 class="cardsMainTitle">Features</h2>
          <div class="cards">
            <article class="card glass">
              <div class="cardTitle">Folders</div>
              <div class="cardText">Simple structure without clutter.</div>
            </article>
            <article class="card glass">
              <div class="cardTitle">Search</div>
              <div class="cardText">Find documents in seconds.</div>
            </article>
            <article class="card glass">
              <div class="cardTitle">Secure</div>
              <div class="cardText">Clear access and privacy-first.</div>
            </article>
            <article class="card glass">
              <div class="cardTitle">Simple UI</div>
              <div class="cardText">User friendly design for everyone.</div>
            </article>
          </div>
        </div>
      </section>

      <section id="preview" class="section up show">
        <div class="wrap" style="padding: 0 16px 100px;">
          <div class="preview glass">
            <div class="previewTop">
              <div>
                <div class="previewTitle">Workspace Preview</div>
                <div class="previewSub">Version history, sharing and audit timeline</div>
              </div>
              <div class="chips">
                <span class="chip">Private Storage</span>
                <span class="chip">Role Access</span>
                <span class="chip">Backups</span>
              </div>
            </div>
            <div class="list">
              <div class="item">
                <div class="fileIco">PDF</div>
                <div class="itemText">
                  <div class="itemTitle">Contract_v4.pdf</div>
                  <div class="itemSub">Updated 2m ago · Shared with legal team</div>
                </div>
                <div class="dots">•••</div>
              </div>
              <div class="item">
                <div class="fileIco">DOC</div>
                <div class="itemText">
                  <div class="itemTitle">Project_scope.docx</div>
                  <div class="itemSub">3 versions · Owner access only</div>
                </div>
                <div class="dots">•••</div>
              </div>
              <div class="item">
                <div class="fileIco">XLS</div>
                <div class="itemText">
                  <div class="itemTitle">Quarter_report.xlsx</div>
                  <div class="itemSub">Backup completed today at 02:30</div>
                </div>
                <div class="dots">•••</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="support" class="section">
        <div class="wrap footer__in footer">DocBox © 2026 · Secure documents without complexity</div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useI18n } from '../i18n'

const menuOpen = ref(false)
const router = useRouter()
const auth = useAuthStore()
const { t } = useI18n()

const openMenu = () => {
  menuOpen.value = true
}

const closeMenu = () => {
  menuOpen.value = false
}

const scrollToSection = (id) => {
  const el = document.getElementById(id)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const mobileNav = (id) => {
  closeMenu()
  scrollToSection(id)
}

const goLogin = () => {
  closeMenu()
  router.push({ name: 'login' })
}

const goRegister = () => {
  closeMenu()
  router.push({ name: 'register' })
}

const goDashboard = () => {
  closeMenu()
  router.push({ name: 'dashboard' })
}

const logout = async () => {
  closeMenu()
  await auth.logout()
  router.push({ name: 'home' })
}
</script>

<style scoped>
.home-root{
  --bg:#f5f5f7;
  --text:#1d1d1f;
  --muted:#6e6e73;
  --line:rgba(0,0,0,.10);
  --glass:rgba(255,255,255,.72);
  --shadow:0 18px 60px rgba(0,0,0,.12);
  --wine1:rgba(255, 0, 0, 0.14);
  --wine2:rgba(243, 6, 6, 0.24);
  --red:#e30000;
}

.app{
  background:var(--bg);
  color:var(--text);
  min-height:100vh;
}

.wrap{
  width: min(1120px, 92vw);
  margin: 0 auto;
}

.topbar{
  background: rgba(255,255,255,.72);
  border-bottom: 1px solid var(--line);
  backdrop-filter: blur(14px);
  position: sticky;
  top: 0;
  z-index: 24;
}

.topbar__in{
  display: flex;
  align-items: center;
  gap: 14px;
  height: 52px;
  justify-content: center;
}

#features {
  padding-top: 58px;
  padding-bottom: 42px;
  background: #f2f2f4;
}

.menuBtn{
  width:34px;
  height:34px;
  border:0;
  background:transparent;
  border-radius:12px;
  display:grid;
  place-items:center;
  cursor:pointer;
}

.menuBtn:hover{ background:rgba(0,0,0,.04); }

.menuI{
  width:16px;
  height:2px;
  background:rgba(0,0,0,.70);
  border-radius:999px;
  display:block;
  margin:2px 0;
}

.brand{
  font-weight:700;
  font-size:13px;
  letter-spacing:.2px;
  cursor:pointer;
  user-select:none;
}

.topLinks{ display:flex; gap:6px; }

.topLink{
  border:0;
  background:transparent;
  cursor:pointer;
  padding:8px 10px;
  border-radius:999px;
  color:var(--text);
  opacity:.82;
  font-size:13px;
}

.topLink:hover{ opacity:1; background:rgba(0,0,0,.04); }

.auth{ display:flex; gap:14px; align-items:center; }

.authLink{
  border:0;
  background:transparent;
  cursor:pointer;
  padding:8px 10px;
  border-radius:999px;
  color:var(--text);
  opacity:.82;
  font-size:13px;
}

.authLink:hover{ opacity:1; background:rgba(0,0,0,.04); }

.overlay{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.25);
  opacity:0;
  pointer-events:none;
  transition:opacity .2s ease;
  z-index:30;
}

.overlay.show{
  opacity:1;
  pointer-events:auto;
}

.side{
  position:fixed;
  top:0;
  right:0;
  height:100%;
  width:340px;
  background:rgba(255,255,255,.92);
  border-left:1px solid var(--line);
  transform:translateX(100%);
  transition:transform .25s ease;
  z-index:40;
  display:flex;
  flex-direction:column;
  padding:12px;
  backdrop-filter: blur(14px);
}

.side.show{ transform:translateX(0); }

.sideTop{
  display:flex;
  align-items:center;
  justify-content:space-between;
  height:48px;
}

.sideTitle{ font-weight:700; }

.closeBtn{
  width:34px;
  height:34px;
  border:0;
  background:transparent;
  border-radius:12px;
  cursor:pointer;
  font-size:18px;
}

.closeBtn:hover{ background:rgba(0,0,0,.04); }

.sideLinks{
  padding-top:6px;
  display:grid;
  gap:8px;
}

.sideLink{
  border:0;
  background:transparent;
  text-align:left;
  cursor:pointer;
  font-size:22px;
  font-weight:700;
  padding:10px 10px;
  border-radius:14px;
  color:var(--text);
}

.sideLink:hover{ background:rgba(0,0,0,.04); }

.sideAuth{
  margin-top:auto;
  display:grid;
  gap:10px;
  padding-top:12px;
}

.sideAuthBtn{
  border:1px solid var(--line);
  background:rgba(255,255,255,.70);
  border-radius:14px;
  padding:12px 12px;
  cursor:pointer;
  font-size:14px;
  font-weight:600;
  color:var(--text);
}

.sideAuthBtn:hover{ background:rgba(0,0,0,.03); }

.bg{
  background: #f4f4f6;
}

.hero{
  padding: 0;
  min-height: 740px;
}

.hero__in{
  width: min(1800px, calc(100vw - 32px));
  min-height: 560px;
  margin: 0 auto;
  display: grid;
  place-content: center;
  text-align: center;
  background:
    radial-gradient(950px 540px at 50% 0%, var(--wine2), transparent 57%),
    radial-gradient(700px 420px at 20% 30%, var(--wine1), transparent 60%),
    linear-gradient(180deg, rgba(255,255,255,.80), var(--bg));
}

.kicker{ color:var(--muted); font-weight:600; font-size:14px; }

.title{
  margin: 10px 0 2px;
  font-size: clamp(56px, 6vw, 82px);
  line-height:1.05;
  letter-spacing:-1px;
  font-weight: 800;
}

.subtitle{
  margin:10px auto 0;
  max-width:720px;
  color:var(--muted);
  line-height:1.65;
  font-size: 16px;
  font-weight: 500;
  padding:0 10px;
}

.actions{
  margin-top: 18px;
  display:flex;
  justify-content:center;
  gap: 12px;
  flex-wrap:wrap;
}

.btnSolid:hover{
  color: black;
  background:
    radial-gradient(300px 160px at 20% 0%, var(--wine2), transparent 55%),
    radial-gradient(250px 120px at 10% 20%, var(--wine1), transparent 60%),
    linear-gradient(180deg, rgba(255,255,255,.80), var(--bg));
}

.btnSolid{
  border:0;
  cursor:pointer;
  border-radius:999px;
  padding: 13px 24px;
  font-size: 14px;
  font-weight: 700;
  background:var(--red);
  color:#fff;
  transition: 0.8s;
  box-shadow: 0 10px 26px rgba(227, 0, 0, .28);
}

.btnOutline{
  border:1px solid rgba(0,0,0,.22);
  cursor:pointer;
  border-radius:999px;
  padding: 13px 24px;
  font-size: 14px;
  font-weight: 700;
  background:rgba(255,255,255,.9);
  color:var(--text);
}

.section{ padding: 30px 0; }

.glass{
  background:var(--glass);
  border:1px solid var(--line);
  box-shadow:var(--shadow);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-radius:18px;
}

.cards{
  display: flex;
  gap: 18px;
  justify-content: center;
  flex-wrap: nowrap;
}

.cardsMainTitle {
  justify-content: center;
  display: flex;
  margin-bottom: 24px;
  font-size: 56px;
  letter-spacing: -0.8px;
}

.card{
  padding: 18px 16px;
  background: rgba(255,255,255,.8);
  width: 238px;
  min-height: 116px;
}

.cardTitle{ font-weight:700; }

.cardText{ margin-top:6px; color:var(--muted); line-height:1.6; }

#preview {
  background: #f2f2f4;
  padding-top: 40px;
}

.preview{
  padding: 18px;
  background: rgba(255,255,255,.8);
}

.previewTop{
  display:flex;
  justify-content:space-between;
  gap:12px;
  align-items:flex-start;
  flex-wrap:wrap;
}

.previewTitle{ font-weight:700; }

.previewSub{ margin-top:4px; color:var(--muted); font-size:13px; }

.chips{ display:flex; gap:8px; flex-wrap:wrap; }

.chip{
  padding:6px 10px;
  border-radius:999px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.85);
  font-size:12px;
}

.list{ margin-top:12px; display:grid; gap:10px; }

.item{
  display:flex;
  align-items:center;
  gap:12px;
  padding:12px;
  border-radius:14px;
  border:1px solid rgba(0,0,0,.08);
  background:rgba(255,255,255,.92);
}

.fileIco{
  width:34px;
  height:34px;
  border-radius:12px;
  display:grid;
  place-items:center;
  border:1px solid rgba(0,0,0,.10);
  background:rgba(0,0,0,.03);
  font-size:11px;
  font-weight:700;
  color:rgba(0,0,0,.70);
}

.itemText{ flex:1; min-width:0; }

.itemTitle{ font-weight:700; }

.itemSub{ margin-top:2px; font-size:12px; color:var(--muted); }

.dots{ opacity:.55; }

.footer{
  padding: 28px 0 30px;
  color:var(--muted);
  font-size:12px;
  background: #f2f2f4;
}

.footer__in{
  display:flex;
  justify-content:center;
}

.up{ opacity:1; transform:translateY(0); transition:opacity .5s ease, transform .5s ease; }

@media (min-width: 961px) {
  .menuBtn { display: none; }
}

@media (max-width: 960px){
  .topLinks{ display:none; }
  .auth{ display:none; }
  .hero {
    min-height: auto;
  }
  .hero__in {
    min-height: 420px;
    width: calc(100vw - 20px);
    padding: 40px 14px;
  }
  .title {
    font-size: clamp(44px, 9vw, 56px);
  }
  .subtitle {
    font-size: 20px;
    font-weight: 600;
  }
  .cardsMainTitle { font-size: 42px; }
  .cards {
    flex-wrap: wrap;
  }
}

@media (max-width: 380px){
  .side{ width: 100%; }
}
</style>
