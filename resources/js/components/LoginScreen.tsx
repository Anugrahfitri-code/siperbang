import React, { useState, useEffect, useRef } from "react";
import { UserRole } from "../types";
import { LogIn, Loader2, Eye, EyeOff, Menu, X, Shield, MapPin, Phone, Mail, Globe } from "lucide-react";
import { SiperbangLogo, KomdigiLogo } from "./Logos";
import { apiFetch } from "../api";

/* ────────────────────────────────────────────────────────────
   TYPES
──────────────────────────────────────────────────────────── */
interface AuthenticatedUser {
  id: number | string;
  name: string;
  username: string;
  role: UserRole;
  section?: string | null;
}

interface LoginScreenProps {
  onLogin: (user: AuthenticatedUser) => void;
}

const NAV_LINKS = ["Beranda", "Tentang", "Fitur", "Panduan", "FAQ", "Kontak"] as const;
type NavLink = (typeof NAV_LINKS)[number];

/* ────────────────────────────────────────────────────────────
   HEADER
──────────────────────────────────────────────────────────── */
function Header({
  active,
  onNav,
  onLoginClick,
}: {
  active: NavLink;
  onNav: (s: NavLink) => void;
  onLoginClick: () => void;
}) {
  const [open, setOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const handler = () => setScrolled(window.scrollY > 10);
    window.addEventListener("scroll", handler, { passive: true });
    return () => window.removeEventListener("scroll", handler);
  }, []);

  return (
    <header
      className="fixed top-0 left-0 right-0 z-50 transition-shadow duration-300"
      style={{
        background: "rgba(255,255,255,0.95)",
        backdropFilter: "blur(12px)",
        boxShadow: scrolled ? "0 1px 12px rgba(0,0,0,0.08)" : "0 1px 0 #e2e8f0",
      }}
    >
      <div
        className="mx-auto px-6 h-16 flex items-center justify-between gap-4"
        style={{ maxWidth: 1280 }}
      >
        {/* Brand */}
        <div className="flex items-center gap-4 flex-shrink-0">
          <SiperbangLogo />
          <div className="hidden sm:block w-px h-10 bg-slate-200" />
          <KomdigiLogo className="hidden sm:flex" />
        </div>

        {/* Desktop nav */}
        <nav className="hidden lg:flex items-center gap-1">
          {NAV_LINKS.map((link) => (
            <button
              key={link}
              onClick={() => onNav(link)}
              className="relative px-4 py-2 text-sm font-semibold transition-colors rounded-lg"
              style={{
                color: active === link ? "#0055A5" : "#475569",
              }}
            >
              {link}
              {active === link && (
                <span
                  className="absolute bottom-0 left-4 right-4 h-0.5 rounded-full"
                  style={{ background: "#0055A5" }}
                />
              )}
            </button>
          ))}
        </nav>

        {/* Masuk button */}
        <button
          onClick={onLoginClick}
          className="hidden lg:flex items-center gap-2 text-sm font-bold px-5 py-2.5 rounded-xl transition-all"
          style={{
            background: "#0055A5",
            color: "#fff",
            boxShadow: "0 2px 8px rgba(0,85,165,0.3)",
          }}
          onMouseEnter={(e) =>
            (e.currentTarget.style.background = "#004494")
          }
          onMouseLeave={(e) =>
            (e.currentTarget.style.background = "#0055A5")
          }
        >
          <LogIn size={15} />
          Masuk
        </button>

        {/* Hamburger */}
        <button
          className="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors"
          onClick={() => setOpen(!open)}
          aria-label="Toggle menu"
        >
          {open ? <X size={22} /> : <Menu size={22} />}
        </button>
      </div>

      {/* Mobile menu */}
      {open && (
        <div className="lg:hidden bg-white border-t border-slate-100 px-4 pt-2 pb-4 space-y-1">
          {NAV_LINKS.map((link) => (
            <button
              key={link}
              onClick={() => {
                onNav(link);
                setOpen(false);
              }}
              className="w-full text-left px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors"
              style={{
                background: active === link ? "#eff6ff" : "transparent",
                color: active === link ? "#0055A5" : "#475569",
              }}
            >
              {link}
            </button>
          ))}
          <button
            onClick={() => {
              onLoginClick();
              setOpen(false);
            }}
            className="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl mt-2 text-sm font-bold text-white"
            style={{ background: "#0055A5" }}
          >
            <LogIn size={15} />
            Masuk
          </button>
        </div>
      )}
    </header>
  );
}

/* ────────────────────────────────────────────────────────────
   LOGIN FORM (right panel of hero)
──────────────────────────────────────────────────────────── */
function LoginForm({ onLogin }: { onLogin: (u: AuthenticatedUser) => void }) {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [showPw, setShowPw] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      const res = await apiFetch("/api/login", {
        method: "POST",
        body: JSON.stringify({ username, password }),
      });
      const data = await res.json();
      if (res.ok) {
        onLogin(data.user as AuthenticatedUser);
      } else {
        setError(data.message || "Login gagal. Periksa kembali username dan password Anda.");
      }
    } catch {
      setError("Terjadi kesalahan jaringan.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="w-full" style={{ maxWidth: 420 }}>
      {/* Heading */}
      <div className="mb-8 text-center">
        <h2 className="text-2xl font-extrabold" style={{ color: "#1e293b" }}>
          Masuk ke Sistem
        </h2>
        <p className="text-sm mt-2" style={{ color: "#64748b" }}>
          Silakan isi username dan password Anda.
        </p>
      </div>

      {/* Error */}
      {error && (
        <div
          className="mb-4 px-4 py-3 rounded-xl text-xs font-medium border"
          style={{ background: "#fff1f2", color: "#e11d48", borderColor: "#fecdd3" }}
        >
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-5">
        {/* Username */}
        <div>
          <label
            className="block text-xs font-bold mb-1.5 uppercase tracking-wide"
            style={{ color: "#374151" }}
          >
            Username
          </label>
          <input
            type="text"
            value={username}
            onChange={(e) => setUsername(e.target.value)}
            placeholder="Masukkan username"
            required
            className="w-full px-4 py-3 rounded-xl text-sm font-medium transition-colors"
            style={{
              border: "2px solid #e2e8f0",
              background: "#fff",
              color: "#1e293b",
              outline: "none",
            }}
            onFocus={(e) => (e.currentTarget.style.borderColor = "#0055A5")}
            onBlur={(e) => (e.currentTarget.style.borderColor = "#e2e8f0")}
          />
        </div>

        {/* Password */}
        <div>
          <label
            className="block text-xs font-bold mb-1.5 uppercase tracking-wide"
            style={{ color: "#374151" }}
          >
            Password
          </label>
          <div className="relative">
            <input
              type={showPw ? "text" : "password"}
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              required
              className="w-full px-4 py-3 pr-12 rounded-xl text-sm font-medium transition-colors"
              style={{
                border: "2px solid #e2e8f0",
                background: "#fff",
                color: "#1e293b",
                outline: "none",
              }}
              onFocus={(e) => (e.currentTarget.style.borderColor = "#0055A5")}
              onBlur={(e) => (e.currentTarget.style.borderColor = "#e2e8f0")}
            />
            <button
              type="button"
              onClick={() => setShowPw(!showPw)}
              className="absolute inset-y-0 right-4 flex items-center transition-colors"
              style={{ color: "#94a3b8" }}
              onMouseEnter={(e) => (e.currentTarget.style.color = "#0055A5")}
              onMouseLeave={(e) => (e.currentTarget.style.color = "#94a3b8")}
            >
              {showPw ? <EyeOff size={20} /> : <Eye size={20} />}
            </button>
          </div>
        </div>

        {/* Submit */}
        <button
          type="submit"
          disabled={loading}
          className="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl font-bold text-sm text-white transition-all mt-2"
          style={{
            background: loading ? "#93c5fd" : "#0055A5",
            boxShadow: "0 4px 14px rgba(0,85,165,0.35)",
          }}
        >
          {loading ? <Loader2 size={17} className="animate-spin" /> : <LogIn size={17} />}
          {loading ? "Memverifikasi..." : "Masuk"}
        </button>
      </form>

      {/* Sub text */}
      <p className="mt-5 text-sm text-center" style={{ color: "#64748b" }}>
        Belum memiliki akun?{" "}
        <span className="font-semibold" style={{ color: "#0055A5" }}>
          Hubungi administrator
        </span>{" "}
        untuk mendapatkan akses.
      </p>

      {/* Akses Aman box */}
      <div
        className="mt-7 flex items-start gap-4 px-5 py-4 rounded-2xl"
        style={{
          background: "rgba(255,255,255,0.65)",
          backdropFilter: "blur(8px)",
          border: "1px solid #b4cce8",
        }}
      >
        <Shield size={26} className="flex-shrink-0 mt-0.5" style={{ color: "#0055A5" }} />
        <div>
          <h3
            className="text-xs font-bold uppercase tracking-wide"
            style={{ color: "#1e4d8c" }}
          >
            Akses Aman
          </h3>
          <p className="mt-1 text-xs leading-snug" style={{ color: "#475569" }}>
            Sistem menggunakan autentikasi terenkripsi. Silakan masuk dengan
            kredensial Anda untuk mengakses fitur SIPERBANG.
          </p>
        </div>
      </div>

      {/* Footer label */}
      <div
        className="mt-6 pt-4 text-center text-xs font-semibold uppercase tracking-widest"
        style={{ borderTop: "1px solid rgba(203,213,225,0.5)", color: "#94a3b8" }}
      >
        SIPERBANG – Authentication Mode
      </div>
    </div>
  );
}

/* ────────────────────────────────────────────────────────────
   FOOTER
──────────────────────────────────────────────────────────── */
function Footer() {
  const socials = [
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" /></svg>,
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z" /></svg>,
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5" /><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" /><line x1="17.5" y1="6.5" x2="17.51" y2="6.5" /></svg>,
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.4a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z" /><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="white" /></svg>,
  ];

  const menuLinks = ["Beranda", "Tentang", "Fitur", "Panduan", "FAQ", "Kontak"];
  const quickLinks = ["Login Sistem", "Panduan Pengguna", "Kebijakan Privasi", "Syarat & Ketentuan"];
  const contacts = [
    { icon: <MapPin size={14} />, text: "Kementerian Komunikasi dan Digital Republik Indonesia" },
    { icon: <Phone size={14} />, text: "(021) 1234 5678" },
    { icon: <Mail size={14} />, text: "support@siperbang.komdigi.go.id" },
    { icon: <Globe size={14} />, text: "www.siperbang.komdigi.go.id" },
  ];

  return (
    <footer style={{ background: "#0c2461" }} className="text-white">
      <div className="mx-auto px-6 py-14" style={{ maxWidth: 1280 }}>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">

          {/* Col 1 – Brand */}
          <div>
            <div className="flex items-center gap-3 mb-4">
              <img
                src="/images/siperbang-logo.png"
                alt="SIPERBANG"
                className="w-10 h-10 object-contain flex-shrink-0"
              />
              <div>
                <p className="font-extrabold text-base tracking-tight leading-none">SIPERBANG</p>
                <p className="text-[10px] font-medium mt-0.5 uppercase tracking-wide" style={{ color: "#93c5fd" }}>
                  Sistem Informasi Penyediaan Barang
                </p>
              </div>
            </div>
            <p className="text-sm leading-relaxed" style={{ color: "#bfdbfe" }}>
              Sistem informasi terintegrasi untuk pengelolaan persediaan barang
              secara digital, akurat, dan transparan.
            </p>
            <div className="flex items-center gap-2 mt-5">
              {socials.map((icon, i) => (
                <a
                  key={i}
                  href="#"
                  className="w-8 h-8 rounded-full flex items-center justify-center transition-colors"
                  style={{ background: "rgba(255,255,255,0.1)", color: "#bfdbfe" }}
                  onMouseEnter={(e) => {
                    (e.currentTarget as HTMLElement).style.background = "rgba(255,255,255,0.2)";
                    (e.currentTarget as HTMLElement).style.color = "#fff";
                  }}
                  onMouseLeave={(e) => {
                    (e.currentTarget as HTMLElement).style.background = "rgba(255,255,255,0.1)";
                    (e.currentTarget as HTMLElement).style.color = "#bfdbfe";
                  }}
                >
                  {icon}
                </a>
              ))}
            </div>
          </div>

          {/* Col 2 – Menu */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-4 text-white">Menu</h4>
            <ul className="space-y-2.5">
              {menuLinks.map((item) => (
                <li key={item}>
                  <a
                    href="#"
                    className="text-sm transition-colors"
                    style={{ color: "#bfdbfe" }}
                    onMouseEnter={(e) => (e.currentTarget.style.color = "#fff")}
                    onMouseLeave={(e) => (e.currentTarget.style.color = "#bfdbfe")}
                  >
                    {item}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          {/* Col 3 – Tautan Cepat */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-4 text-white">Tautan Cepat</h4>
            <ul className="space-y-2.5">
              {quickLinks.map((item) => (
                <li key={item}>
                  <a
                    href="#"
                    className="text-sm transition-colors"
                    style={{ color: "#bfdbfe" }}
                    onMouseEnter={(e) => (e.currentTarget.style.color = "#fff")}
                    onMouseLeave={(e) => (e.currentTarget.style.color = "#bfdbfe")}
                  >
                    {item}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          {/* Col 4 – Kontak */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-4 text-white">Kontak Kami</h4>
            <ul className="space-y-3">
              {contacts.map((c, i) => (
                <li key={i} className="flex items-start gap-3 text-sm" style={{ color: "#bfdbfe" }}>
                  <span className="flex-shrink-0 mt-0.5" style={{ color: "#93c5fd" }}>
                    {c.icon}
                  </span>
                  <span>{c.text}</span>
                </li>
              ))}
            </ul>
          </div>
        </div>

        {/* Bottom bar */}
        <div
          className="mt-12 pt-6 text-center text-xs"
          style={{ borderTop: "1px solid rgba(255,255,255,0.1)", color: "#93c5fd" }}
        >
          © 2024 Kementerian Komunikasi dan Digital Republik Indonesia. All rights reserved.
        </div>
      </div>
    </footer>
  );
}

/* ────────────────────────────────────────────────────────────
   SECTION WRAPPER (fade-in on scroll)
──────────────────────────────────────────────────────────── */
function Section({ id, bg, children }: { id: string; bg: string; children: React.ReactNode }) {
  const ref = useRef<HTMLElement>(null);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const obs = new IntersectionObserver(
      ([entry]) => { if (entry.isIntersecting) setVisible(true); },
      { threshold: 0.1 }
    );
    obs.observe(el);
    return () => obs.disconnect();
  }, []);

  return (
    <section
      id={id}
      ref={ref}
      style={{ background: bg, scrollMarginTop: 64 }}
      className={`py-20 transition-all duration-700 ${visible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-6"}`}
    >
      {children}
    </section>
  );
}

/* ────────────────────────────────────────────────────────────
   MAIN EXPORT
──────────────────────────────────────────────────────────── */
export function LoginScreen({ onLogin }: LoginScreenProps) {
  const [active, setActive] = useState<NavLink>("Beranda");

  /* Scroll-spy: update active nav when scrolling */
  useEffect(() => {
    const ids = NAV_LINKS.map((l) => `section-${l.toLowerCase()}`);
    const handler = () => {
      for (const id of [...ids].reverse()) {
        const el = document.getElementById(id);
        if (el && window.scrollY >= el.offsetTop - 100) {
          const label = id.replace("section-", "");
          const matched = NAV_LINKS.find(
            (l) => l.toLowerCase() === label
          );
          if (matched) setActive(matched);
          break;
        }
      }
    };
    window.addEventListener("scroll", handler, { passive: true });
    return () => window.removeEventListener("scroll", handler);
  }, []);

  const scrollTo = (id: string) =>
    document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });

  const handleNav = (section: NavLink) => {
    setActive(section);
    scrollTo(`section-${section.toLowerCase()}`);
  };

  const handleLoginClick = () => scrollTo("section-beranda");

  return (
    <div className="min-h-screen flex flex-col" style={{ fontFamily: "Inter, sans-serif" }}>
      {/* ── NAVBAR ── */}
      <Header active={active} onNav={handleNav} onLoginClick={handleLoginClick} />

      {/* ══════════════════════════════════════════
          HERO / BERANDA
      ══════════════════════════════════════════ */}
      <section
        id="section-beranda"
        style={{
          scrollMarginTop: 0,
          paddingTop: 64, /* compensate for fixed nav */
          minHeight: "100vh",
          backgroundImage: "url('/images/login-bg.png')",
          backgroundSize: "cover",
          backgroundPosition: "center",
        }}
      >
        <div
          className="mx-auto px-6 flex flex-col lg:flex-row items-center"
          style={{ maxWidth: 1280, minHeight: "calc(100vh - 64px)" }}
        >
          {/* Left: headline + illustration */}
          <div className="hidden lg:flex lg:w-[55%] flex-col justify-center py-16 pr-12">
            <h1
              className="text-4xl xl:text-5xl font-extrabold leading-tight"
              style={{ color: "#183b63" }}
            >
              Selamat Datang di
              <br />
              Portal SIPERBANG.
            </h1>
            <p
              className="mt-5 text-[15px] leading-relaxed"
              style={{ color: "#475569", maxWidth: 460 }}
            >
              Pusat pengelolaan persediaan barang secara digital, dilengkapi
              fitur verifikasi nota otomatis menggunakan teknologi OCR AI, dan
              pemantauan stok real-time.
            </p>
            <img
              src="/images/login-illustration.png"
              alt="Ilustrasi portal SIPERBANG"
              className="mt-12 w-full object-contain select-none pointer-events-none"
              style={{
                maxWidth: 480,
                filter: "drop-shadow(6px 8px 6px rgba(0,0,0,0.14))",
              }}
            />
          </div>

          {/* Right: login form */}
          <div className="w-full lg:w-[45%] flex items-center justify-center py-16">
            <LoginForm onLogin={onLogin} />
          </div>
        </div>
      </section>

      {/* ══════════════════════════════════════════
          TENTANG
      ══════════════════════════════════════════ */}
      <Section id="section-tentang" bg="#ffffff">
        <div className="mx-auto px-6 text-center" style={{ maxWidth: 1100 }}>
          <p className="text-xs font-bold uppercase tracking-widest mb-3" style={{ color: "#0055A5" }}>
            Tentang Kami
          </p>
          <h2 className="text-3xl font-extrabold mb-4" style={{ color: "#183b63" }}>
            Tentang SIPERBANG
          </h2>
          <p className="text-[15px] leading-relaxed mx-auto mb-12" style={{ color: "#64748b", maxWidth: 640 }}>
            SIPERBANG adalah sistem informasi manajemen persediaan barang milik Kementerian
            Komunikasi dan Digital Republik Indonesia. Dibangun untuk mendukung tata kelola
            barang yang efisien, transparan, dan berbasis data.
          </p>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
            {[
              {
                icon: "📦",
                title: "Manajemen Stok",
                desc: "Pantau persediaan barang secara real-time di seluruh unit kerja.",
              },
              {
                icon: "🤖",
                title: "OCR AI Otomatis",
                desc: "Verifikasi nota pembelian secara otomatis menggunakan kecerdasan buatan.",
              },
              {
                icon: "📊",
                title: "Laporan Digital",
                desc: "Ekspor laporan persediaan dalam berbagai format untuk kemudahan audit.",
              },
            ].map((item) => (
              <div
                key={item.title}
                className="rounded-2xl p-8 text-left transition-shadow hover:shadow-md"
                style={{ background: "#f0f6ff", border: "1px solid #dbeafe" }}
              >
                <div className="text-4xl mb-4">{item.icon}</div>
                <h3 className="font-bold mb-2" style={{ color: "#183b63" }}>{item.title}</h3>
                <p className="text-sm leading-relaxed" style={{ color: "#64748b" }}>{item.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </Section>

      {/* ══════════════════════════════════════════
          FITUR
      ══════════════════════════════════════════ */}
      <Section id="section-fitur" bg="#f0f4fa">
        <div className="mx-auto px-6 text-center" style={{ maxWidth: 1100 }}>
          <p className="text-xs font-bold uppercase tracking-widest mb-3" style={{ color: "#0055A5" }}>
            Kemampuan Sistem
          </p>
          <h2 className="text-3xl font-extrabold mb-4" style={{ color: "#183b63" }}>
            Fitur Unggulan
          </h2>
          <p className="text-[15px] leading-relaxed mx-auto mb-12" style={{ color: "#64748b", maxWidth: 560 }}>
            Berbagai fitur canggih tersedia untuk mendukung kegiatan pengelolaan barang Anda.
          </p>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
            {[
              { title: "Dashboard Real-Time", desc: "Pantau status stok, permintaan, dan pengadaan dalam satu tampilan terintegrasi.", icon: "📈" },
              { title: "Bon Digital", desc: "Buat dan kelola bon permintaan barang secara digital tanpa kertas.", icon: "📝" },
              { title: "Verifikasi Nota OCR", desc: "Unggah nota fisik dan sistem akan memverifikasi data secara otomatis.", icon: "🔍" },
              { title: "Manajemen Pengguna", desc: "Atur hak akses pengguna berdasarkan peran dan unit kerja.", icon: "👥" },
            ].map((f) => (
              <div
                key={f.title}
                className="flex items-start gap-4 rounded-2xl p-6 text-left transition-shadow hover:shadow-md"
                style={{ background: "#fff", border: "1px solid #e2e8f0" }}
              >
                <div className="text-3xl flex-shrink-0">{f.icon}</div>
                <div>
                  <h3 className="font-bold mb-1.5" style={{ color: "#183b63" }}>{f.title}</h3>
                  <p className="text-sm leading-relaxed" style={{ color: "#64748b" }}>{f.desc}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </Section>

      {/* ══════════════════════════════════════════
          PANDUAN
      ══════════════════════════════════════════ */}
      <Section id="section-panduan" bg="#ffffff">
        <div className="mx-auto px-6 text-center" style={{ maxWidth: 900 }}>
          <p className="text-xs font-bold uppercase tracking-widest mb-3" style={{ color: "#0055A5" }}>
            Cara Penggunaan
          </p>
          <h2 className="text-3xl font-extrabold mb-4" style={{ color: "#183b63" }}>
            Panduan Penggunaan
          </h2>
          <p className="text-[15px] leading-relaxed mx-auto mb-14" style={{ color: "#64748b", maxWidth: 500 }}>
            Ikuti langkah-langkah berikut untuk mulai menggunakan SIPERBANG.
          </p>

          {/* Steps */}
          <div className="relative flex flex-col sm:flex-row items-start gap-8 sm:gap-0">
            {/* Connector line (desktop only) */}
            <div
              className="hidden sm:block absolute top-6 left-[12.5%] right-[12.5%] h-0.5"
              style={{ background: "#dbeafe" }}
            />

            {[
              { step: "01", title: "Login", desc: "Masuk menggunakan akun yang diberikan oleh administrator." },
              { step: "02", title: "Dashboard", desc: "Lihat ringkasan stok, permintaan, dan notifikasi terbaru." },
              { step: "03", title: "Kelola Barang", desc: "Tambah, edit, atau ajukan permintaan barang sesuai kebutuhan." },
              { step: "04", title: "Laporan", desc: "Ekspor laporan bulanan atau tahunan ke format yang diinginkan." },
            ].map((s) => (
              <div key={s.step} className="flex-1 flex flex-col items-center text-center px-4 relative z-10">
                <div
                  className="w-12 h-12 rounded-full flex items-center justify-center font-extrabold text-sm text-white mb-4"
                  style={{ background: "#0055A5", boxShadow: "0 4px 14px rgba(0,85,165,0.35)" }}
                >
                  {s.step}
                </div>
                <h3 className="font-bold mb-2" style={{ color: "#183b63" }}>{s.title}</h3>
                <p className="text-sm leading-relaxed" style={{ color: "#64748b" }}>{s.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </Section>

      {/* ══════════════════════════════════════════
          FAQ
      ══════════════════════════════════════════ */}
      <Section id="section-faq" bg="#f0f4fa">
        <div className="mx-auto px-6" style={{ maxWidth: 760 }}>
          <div className="text-center mb-12">
            <p className="text-xs font-bold uppercase tracking-widest mb-3" style={{ color: "#0055A5" }}>
              Bantuan
            </p>
            <h2 className="text-3xl font-extrabold" style={{ color: "#183b63" }}>
              Pertanyaan Umum (FAQ)
            </h2>
          </div>
          <div className="space-y-3">
            {[
              { q: "Siapa yang bisa mengakses SIPERBANG?", a: "Hanya pengguna yang terdaftar dan memiliki akun dari administrator yang dapat mengakses sistem ini." },
              { q: "Bagaimana cara mendapatkan akun?", a: "Hubungi administrator sistem di unit kerja Anda untuk mendapatkan akun dan hak akses yang sesuai." },
              { q: "Apakah data tersimpan dengan aman?", a: "Ya, sistem menggunakan enkripsi dan autentikasi berlapis untuk menjaga keamanan data Anda." },
              { q: "Bagaimana jika lupa password?", a: "Silakan hubungi administrator untuk melakukan reset password. Fitur self-service akan segera tersedia." },
            ].map((item) => (
              <details
                key={item.q}
                className="group rounded-2xl px-6 py-4 transition-shadow hover:shadow-sm"
                style={{ background: "#fff", border: "1px solid #e2e8f0" }}
              >
                <summary
                  className="font-semibold text-sm flex items-center justify-between gap-3"
                  style={{ color: "#183b63", listStyle: "none", cursor: "pointer" }}
                >
                  <span>{item.q}</span>
                  <svg
                    width="18" height="18" viewBox="0 0 24 24"
                    fill="none" stroke="#0055A5" strokeWidth="2.5"
                    strokeLinecap="round" strokeLinejoin="round"
                    className="flex-shrink-0 group-open:rotate-180 transition-transform duration-200"
                  >
                    <polyline points="6 9 12 15 18 9" />
                  </svg>
                </summary>
                <p className="mt-3 text-sm leading-relaxed" style={{ color: "#475569" }}>
                  {item.a}
                </p>
              </details>
            ))}
          </div>
        </div>
      </Section>

      {/* ══════════════════════════════════════════
          KONTAK
      ══════════════════════════════════════════ */}
      <Section id="section-kontak" bg="#ffffff">
        <div className="mx-auto px-6 text-center" style={{ maxWidth: 900 }}>
          <p className="text-xs font-bold uppercase tracking-widest mb-3" style={{ color: "#0055A5" }}>
            Dukungan
          </p>
          <h2 className="text-3xl font-extrabold mb-4" style={{ color: "#183b63" }}>
            Hubungi Kami
          </h2>
          <p className="text-[15px] leading-relaxed mx-auto mb-12" style={{ color: "#64748b", maxWidth: 480 }}>
            Ada pertanyaan atau kendala? Tim kami siap membantu Anda.
          </p>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
            {[
              { icon: "📍", label: "Alamat", value: "Kementerian Komunikasi dan Digital RI, Jakarta" },
              { icon: "📞", label: "Telepon", value: "(021) 1234 5678" },
              { icon: "✉️", label: "Email", value: "support@siperbang.komdigi.go.id" },
            ].map((c) => (
              <div
                key={c.label}
                className="rounded-2xl p-8 transition-shadow hover:shadow-md"
                style={{ background: "#f0f6ff", border: "1px solid #dbeafe" }}
              >
                <div className="text-4xl mb-3">{c.icon}</div>
                <p className="font-bold text-sm mb-1" style={{ color: "#183b63" }}>{c.label}</p>
                <p className="text-sm" style={{ color: "#64748b" }}>{c.value}</p>
              </div>
            ))}
          </div>
        </div>
      </Section>

      {/* ══════════════════════════════════════════
          FOOTER
      ══════════════════════════════════════════ */}
      <Footer />
    </div>
  );
}
