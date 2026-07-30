import React, { useState, useEffect, useRef } from "react";
import { UserRole } from "../types";
import { LogIn, Loader2, Eye, EyeOff, Menu, X, Shield, ArrowRight, ScanLine, FileText, CheckCircle2, Package, Monitor, Facebook, Instagram, Youtube, Twitter, Linkedin, Github, MapPin, Phone, Mail, Bell } from "lucide-react";
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
      className="fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-white"
      style={{
        boxShadow: scrolled ? "0 4px 20px rgba(0,0,0,0.05)" : "0 1px 0 #f1f5f9",
      }}
    >
      <div
        className="mx-auto px-6 h-20 flex items-center justify-between gap-4"
        style={{ maxWidth: 1280 }}
      >
        {/* Brand */}
        <div className="flex items-center gap-6 flex-shrink-0">
          <div className="flex items-center gap-3">
            <SiperbangLogo />
          </div>
          <div className="hidden sm:block w-px h-8 bg-slate-200" />
          <div className="hidden sm:flex items-center gap-2">
            <KomdigiLogo />
          </div>
        </div>

        {/* Desktop nav */}
        <nav className="hidden lg:flex items-center gap-6">
          {NAV_LINKS.map((link) => (
            <button
              key={link}
              onClick={() => onNav(link)}
              className="relative text-[15px] font-semibold transition-colors"
              style={{
                color: active === link ? "#0055A5" : "#475569",
              }}
            >
              {link}
              {active === link && (
                <span
                  className="absolute -bottom-1.5 left-0 right-0 h-0.5 rounded-full"
                  style={{ background: "#0055A5" }}
                />
              )}
            </button>
          ))}
        </nav>

        {/* Masuk button */}
        <button
          onClick={onLoginClick}
          className="hidden lg:flex items-center gap-2 text-[15px] font-bold px-6 py-2.5 rounded-full transition-all"
          style={{
            background: "#0055A5",
            color: "#fff",
            boxShadow: "0 4px 14px rgba(0,85,165,0.25)",
          }}
          onMouseEnter={(e) =>
            (e.currentTarget.style.background = "#004494")
          }
          onMouseLeave={(e) =>
            (e.currentTarget.style.background = "#0055A5")
          }
        >
          <LogIn size={18} />
          Masuk
        </button>

        {/* Hamburger */}
        <button
          className="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors"
          onClick={() => setOpen(!open)}
          aria-label="Toggle menu"
        >
          {open ? <X size={24} /> : <Menu size={24} />}
        </button>
      </div>

      {/* Mobile menu */}
      {open && (
        <div className="lg:hidden bg-white border-t border-slate-100 px-6 pt-4 pb-6 space-y-2 shadow-xl">
          {NAV_LINKS.map((link) => (
            <button
              key={link}
              onClick={() => {
                onNav(link);
                setOpen(false);
              }}
              className="w-full text-left px-4 py-3 rounded-xl text-[15px] font-semibold transition-colors"
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
            className="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl mt-4 text-[15px] font-bold text-white shadow-lg shadow-blue-500/20"
            style={{ background: "#0055A5" }}
          >
            <LogIn size={18} />
            Masuk
          </button>
        </div>
      )}
    </header>
  );
}

/* ────────────────────────────────────────────────────────────
   LOGIN MODAL
──────────────────────────────────────────────────────────── */
function LoginModal({
  isOpen,
  onClose,
  onLogin,
}: {
  isOpen: boolean;
  onClose: () => void;
  onLogin: (u: AuthenticatedUser) => void;
}) {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [showPw, setShowPw] = useState(false);

  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = 'auto';
    }
    return () => { document.body.style.overflow = 'auto'; };
  }, [isOpen]);

  if (!isOpen) return null;

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
    <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div 
        className="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
        onClick={onClose}
      />
      <div 
        className="bg-white rounded-3xl w-full max-w-md relative z-10 shadow-2xl p-8 animate-in fade-in zoom-in-95 duration-200"
      >
        <button 
          onClick={onClose}
          className="absolute top-4 right-4 p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors"
        >
          <X size={20} />
        </button>

        {/* Heading */}
        <div className="mb-8 text-center">
          <div className="flex justify-center mb-4">
            <div className="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center">
              <SiperbangLogo />
            </div>
          </div>
          <h2 className="text-2xl font-extrabold" style={{ color: "#1e293b" }}>
            Masuk ke Sistem
          </h2>
          <p className="text-sm mt-2 font-medium" style={{ color: "#64748b" }}>
            Gunakan kredensial Anda untuk mengakses portal.
          </p>
        </div>

        {/* Error */}
        {error && (
          <div
            className="mb-6 px-4 py-3 rounded-xl text-[13px] font-semibold border flex items-center gap-3"
            style={{ background: "#fff1f2", color: "#e11d48", borderColor: "#fecdd3" }}
          >
            <Shield size={16} className="shrink-0" />
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-5">
          {/* Username */}
          <div>
            <label
              className="block text-xs font-bold mb-2 uppercase tracking-wider"
              style={{ color: "#475569" }}
            >
              Username
            </label>
            <input
              type="text"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              placeholder="Masukkan username"
              required
              className="w-full px-5 py-3.5 rounded-xl text-[15px] font-semibold transition-all"
              style={{
                border: "2px solid #e2e8f0",
                background: "#f8fafc",
                color: "#1e293b",
                outline: "none",
              }}
              onFocus={(e) => { e.currentTarget.style.borderColor = "#0055A5"; e.currentTarget.style.background = "#fff"; }}
              onBlur={(e) => { e.currentTarget.style.borderColor = "#e2e8f0"; e.currentTarget.style.background = "#f8fafc"; }}
            />
          </div>

          {/* Password */}
          <div>
            <label
              className="block text-xs font-bold mb-2 uppercase tracking-wider"
              style={{ color: "#475569" }}
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
                className="w-full px-5 py-3.5 pr-12 rounded-xl text-[15px] font-semibold transition-all"
                style={{
                  border: "2px solid #e2e8f0",
                  background: "#f8fafc",
                  color: "#1e293b",
                  outline: "none",
                }}
                onFocus={(e) => { e.currentTarget.style.borderColor = "#0055A5"; e.currentTarget.style.background = "#fff"; }}
                onBlur={(e) => { e.currentTarget.style.borderColor = "#e2e8f0"; e.currentTarget.style.background = "#f8fafc"; }}
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
            className="w-full flex items-center justify-center gap-2 py-4 rounded-xl font-bold text-[15px] text-white transition-all mt-4"
            style={{
              background: loading ? "#93c5fd" : "#0055A5",
              boxShadow: "0 4px 20px rgba(0,85,165,0.3)",
            }}
          >
            {loading ? <Loader2 size={18} className="animate-spin" /> : <LogIn size={18} />}
            {loading ? "Memverifikasi..." : "Masuk ke Dashboard"}
          </button>
        </form>

        <p className="mt-6 text-sm text-center font-medium" style={{ color: "#64748b" }}>
          Belum memiliki akun?{" "}
          <span className="font-bold cursor-pointer" style={{ color: "#0055A5" }}>
            Hubungi administrator
          </span>
        </p>
      </div>
    </div>
  );
}

/* ────────────────────────────────────────────────────────────
   SECTION WRAPPER (fade-in on scroll)
──────────────────────────────────────────────────────────── */
function Section({ id, bg, children, className = "" }: { id: string; bg: string; children: React.ReactNode, className?: string }) {
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
      style={{ background: bg }}
      className={`py-24 transition-all duration-1000 ${visible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-8"} ${className}`}
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
  const [showModal, setShowModal] = useState(false);

  /* Scroll-spy: update active nav when scrolling */
  useEffect(() => {
    const ids = NAV_LINKS.map((l) => `section-${l.toLowerCase()}`);
    const handler = () => {
      for (const id of [...ids].reverse()) {
        const el = document.getElementById(id);
        if (el && window.scrollY >= el.offsetTop - 200) {
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

  return (
    <div className="min-h-screen flex flex-col font-sans bg-white overflow-x-hidden text-slate-800">
      <Header active={active} onNav={handleNav} onLoginClick={() => setShowModal(true)} />
      
      <LoginModal 
        isOpen={showModal} 
        onClose={() => setShowModal(false)} 
        onLogin={onLogin} 
      />

      {/* ══════════════════════════════════════════
          HERO / BERANDA
      ══════════════════════════════════════════ */}
      <section
        id="section-beranda"
        className="pt-32 pb-20 lg:pt-40 lg:pb-32 relative overflow-hidden"
      >
        <div className="absolute inset-0 pointer-events-none z-0">
           <div className="absolute top-0 right-0 w-[800px] h-[800px] bg-blue-50/50 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
           <div className="absolute bottom-0 left-0 w-[600px] h-[600px] bg-indigo-50/50 rounded-full blur-3xl translate-y-1/3 -translate-x-1/3"></div>
        </div>

        <div className="mx-auto px-6 relative z-10" style={{ maxWidth: 1280 }}>
          <div className="flex flex-col lg:flex-row items-center gap-12 lg:gap-8">
            
            {/* Left Content */}
            <div className="w-full lg:w-[50%] flex flex-col items-start text-left">
              <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-50 border border-blue-100 text-blue-700 font-bold text-xs uppercase tracking-wider mb-6">
                Sistem Informasi Penyediaan Barang
              </div>
              <h1 className="text-4xl sm:text-5xl lg:text-[3.5rem] font-extrabold leading-[1.15] text-slate-900 mb-6">
                Kelola Persediaan Barang Pemerintah Lebih <span className="text-[#0055A5]">Cepat</span>, <span className="text-[#0055A5]">Akurat</span>, dan <span className="text-[#0055A5]">Transparan</span>.
              </h1>
              <p className="text-[17px] text-slate-600 leading-relaxed mb-10 max-w-[90%] font-medium">
                SIPERBANG membantu instansi pemerintah dalam mengelola persediaan barang secara digital, dilengkapi verifikasi nota otomatis menggunakan OCR AI dan pemantauan stok real-time.
              </p>
              
              <div className="flex flex-wrap items-center gap-4 mb-10">
                <button
                  onClick={() => setShowModal(true)}
                  className="flex items-center gap-2 bg-[#0055A5] hover:bg-[#004494] text-white px-8 py-3.5 rounded-full font-bold text-[15px] transition-all shadow-[0_8px_20px_rgba(0,85,165,0.25)] hover:-translate-y-0.5"
                >
                  <LogIn size={18} />
                  Mulai Sekarang
                </button>
                <button
                  onClick={() => handleNav("Fitur")}
                  className="flex items-center gap-2 bg-white border-2 border-slate-200 hover:border-[#0055A5] text-slate-700 hover:text-[#0055A5] px-8 py-3.5 rounded-full font-bold text-[15px] transition-all"
                >
                  Pelajari Fitur <ArrowRight size={18} />
                </button>
              </div>

              <div className="flex items-center gap-3 text-sm font-semibold text-slate-500">
                <div className="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                  <Shield size={16} />
                </div>
                Akses aman dan terlindungi dengan autentikasi terenkripsi.
              </div>
            </div>

            {/* Right Image */}
            <div className="w-full lg:w-[50%] relative flex justify-center items-center mt-12 lg:mt-0" style={{ perspective: "1200px" }}>
              <div 
                className="relative w-full max-w-[650px] transition-all duration-700 ease-out hover:scale-105"
                style={{
                  transform: "rotateX(8deg) rotateY(-22deg) rotateZ(3deg)",
                  transformStyle: "preserve-3d"
                }}
              >
                {/* Glow di belakang gambar */}
                <div className="absolute inset-0 bg-blue-500/30 blur-[60px] rounded-full transform translate-z-[-50px]"></div>
                
                {/* Gambar utama dengan border tebal dan bayangan untuk efek luaran 3D */}
                <img
                  src="/images/foto 1 landing page.png"
                  alt="Dashboard Preview 1"
                  className="w-full h-auto object-contain rounded-3xl relative z-10 bg-white"
                  style={{ 
                    boxShadow: "-30px 40px 60px rgba(0, 0, 0, 0.25), 0 0 15px rgba(255, 255, 255, 0.5)",
                    border: "8px solid white",
                    outline: "1px solid rgba(0,0,0,0.05)"
                  }}
                />

                {/* Floating Card 1: Top Center-Left */}
                <div className="absolute top-[-8%] left-[8%] bg-white/95 backdrop-blur-md rounded-xl shadow-[0_10px_25px_rgba(0,0,0,0.15)] p-3 flex items-center gap-3 z-20 border border-slate-100/50"
                     style={{ transform: "translateZ(60px)" }}>
                  <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-500">
                    <CheckCircle2 size={24} fill="currentColor" className="text-green-500 bg-white rounded-full" />
                  </div>
                  <div className="text-left pr-2">
                    <div className="text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">OCR Berhasil</div>
                    <div className="text-xl font-extrabold text-slate-800 leading-none">98.7%</div>
                    <div className="text-[10px] text-slate-500 font-medium mt-1">Akurasi</div>
                  </div>
                </div>

                {/* Floating Card 2: Bottom Left */}
                <div className="absolute bottom-[10%] left-[-5%] bg-white/95 backdrop-blur-md rounded-xl shadow-[0_10px_25px_rgba(0,0,0,0.15)] p-3 flex items-center gap-3 z-20 border border-slate-100/50"
                     style={{ transform: "translateZ(80px)" }}>
                  <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-500">
                    <Package size={20} strokeWidth={2.5} />
                  </div>
                  <div className="text-left pr-2">
                    <div className="text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Barang Tersedia</div>
                    <div className="text-xl font-extrabold text-slate-800 leading-none">325</div>
                    <div className="text-[10px] text-slate-500 font-medium mt-1">Item</div>
                  </div>
                </div>

                {/* Floating Card 3: Bottom Right */}
                <div className="absolute bottom-[-5%] right-[15%] bg-white/95 backdrop-blur-md rounded-xl shadow-[0_10px_25px_rgba(0,0,0,0.15)] p-3 flex items-center gap-3 z-20 border border-slate-100/50"
                     style={{ transform: "translateZ(100px)" }}>
                  <div className="w-10 h-10 bg-amber-100/80 rounded-full flex items-center justify-center text-amber-500">
                    <Bell size={20} fill="currentColor" className="text-amber-500" />
                  </div>
                  <div className="text-left pr-2">
                    <div className="text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Permintaan</div>
                    <div className="text-xl font-extrabold text-slate-800 leading-none">3</div>
                    <div className="text-[10px] text-slate-500 font-medium mt-1">Menunggu</div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      {/* ══════════════════════════════════════════
          TENTANG (Hidden in design, but keeping section id for nav)
      ══════════════════════════════════════════ */}
      <div id="section-tentang"></div>

      {/* ══════════════════════════════════════════
          FITUR UNGGULAN
      ══════════════════════════════════════════ */}
      <Section id="section-fitur" bg="#ffffff" className="pt-10">
        <div className="mx-auto px-6" style={{ maxWidth: 1280 }}>
          <div className="text-center mb-16">
            <h2 className="text-3xl lg:text-4xl font-extrabold text-slate-900">
              Fitur Unggulan SIPERBANG
            </h2>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {[
              {
                icon: <ScanLine size={28} />,
                title: "Verifikasi Nota OCR AI",
                desc: "Ekstraksi data otomatis dari nota menggunakan OCR AI dengan akurasi tinggi.",
                color: "text-white",
                bg: "bg-[#1d4ed8]", // Blue
                shadow: "shadow-blue-500/40"
              },
              {
                icon: <Package size={28} />,
                title: "Manajemen Barang",
                desc: "Kelola data barang, kategori, dan stok secara terpusat dan terstruktur.",
                color: "text-white",
                bg: "bg-[#0d9488]", // Teal
                shadow: "shadow-teal-500/40"
              },
              {
                icon: <Monitor size={28} />,
                title: "Monitoring Real-Time",
                desc: "Pantau stok dan permintaan barang secara real-time dengan dashboard interaktif.",
                color: "text-white",
                bg: "bg-[#6366f1]", // Indigo/Purple
                shadow: "shadow-indigo-500/40"
              },
              {
                icon: <FileText size={28} />,
                title: "Laporan & Analitik",
                desc: "Laporan lengkap dan analitik untuk mendukung pengambilan keputusan.",
                color: "text-white",
                bg: "bg-[#f97316]", // Orange
                shadow: "shadow-orange-500/40"
              }
            ].map((f, i) => (
              <div key={i} className="bg-white rounded-[2rem] p-8 flex flex-col items-start text-left transition-all duration-300 hover:-translate-y-2 hover:shadow-[0_20px_40px_rgba(0,0,0,0.08)] shadow-[0_10px_30px_rgba(0,0,0,0.06)] border border-slate-100">
                <div className={`w-14 h-14 rounded-2xl ${f.bg} ${f.color} flex items-center justify-center mb-6 shadow-lg ${f.shadow}`}>
                  {f.icon}
                </div>
                <h3 className="text-xl font-bold text-slate-900 mb-3 leading-tight">{f.title}</h3>
                <p className="text-[14px] text-slate-500 leading-relaxed font-medium">
                  {f.desc}
                </p>
              </div>
            ))}
          </div>
        </div>
      </Section>

      {/* ══════════════════════════════════════════
          CARA KERJA / PANDUAN
      ══════════════════════════════════════════ */}
      <Section id="section-panduan" bg="#fafafa" className="border-y border-slate-100">
        <div className="mx-auto px-6" style={{ maxWidth: 1280 }}>
          <div className="text-center mb-20">
            <h2 className="text-3xl lg:text-4xl font-extrabold text-slate-900">
              Cara Kerja SIPERBANG
            </h2>
          </div>

          <div className="flex flex-col lg:flex-row items-center justify-between gap-6 relative">
            {[
              { step: "01", icon: <FileText size={32} strokeWidth={1.5} />, title: "Upload Nota", desc: "Unggah nota atau dokumen pengadaan barang.", iconBg: "bg-blue-50/80", iconColor: "text-blue-600" },
              { step: "02", icon: <ScanLine size={32} strokeWidth={1.5} />, title: "OCR AI", desc: "Sistem membaca dan mengekstrak data dari nota secara otomatis.", iconBg: "bg-emerald-50/80", iconColor: "text-emerald-600" },
              { step: "03", icon: <CheckCircle2 size={32} strokeWidth={1.5} />, title: "Verifikasi Data", desc: "Data diverifikasi sebelum disimpan ke sistem.", iconBg: "bg-amber-50/80", iconColor: "text-amber-500" },
              { step: "04", icon: <Package size={32} strokeWidth={1.5} />, title: "Barang Masuk", desc: "Data tersimpan dan stok barang otomatis bertambah.", iconBg: "bg-blue-50/80", iconColor: "text-blue-600" },
              { step: "05", icon: <Monitor size={32} strokeWidth={1.5} />, title: "Monitoring & Laporan", desc: "Pantau stok dan buat laporan kapan saja.", iconBg: "bg-purple-50/80", iconColor: "text-purple-600" },
            ].map((s, i, arr) => (
              <React.Fragment key={s.step}>
                <div className="bg-white rounded-[1.5rem] p-6 pt-8 pb-8 flex flex-col items-center text-center relative z-10 flex-1 shadow-[0_8px_30px_rgba(0,0,0,0.04)] border border-slate-100/50 w-full lg:w-auto h-full">
                  <div className="absolute -top-3 -left-3 w-8 h-8 bg-[#0055A5] text-white rounded-full flex items-center justify-center font-bold text-[13px] shadow-lg ring-4 ring-[#fafafa]">
                    {s.step}
                  </div>
                  
                  <div className={`w-20 h-20 rounded-full flex items-center justify-center mb-5 ${s.iconBg} ${s.iconColor}`}>
                    {s.icon}
                  </div>
                  
                  <h4 className="text-[16px] font-bold text-slate-900 mb-2 leading-tight">{s.title}</h4>
                  <p className="text-[13px] text-slate-500 font-medium leading-relaxed">
                    {s.desc}
                  </p>
                </div>
                {/* Arrow (desktop) */}
                {i < arr.length - 1 && (
                  <div className="hidden lg:flex items-center justify-center text-slate-600 shrink-0 mx-1">
                    <ArrowRight size={18} strokeWidth={2.5} />
                  </div>
                )}
              </React.Fragment>
            ))}
          </div>
        </div>
      </Section>

      {/* ══════════════════════════════════════════
          DASHBOARD PREVIEW (Between Cara Kerja and Tim Pengembang)
      ══════════════════════════════════════════ */}
      <Section id="section-dashboard-preview" bg="#f0f7ff">
        <div className="mx-auto px-6" style={{ maxWidth: 1280 }}>
          <div className="flex flex-col lg:flex-row items-center gap-16 lg:gap-8">
            <div className="w-full lg:w-5/12 flex flex-col items-start">
              <div className="inline-flex items-center px-4 py-1.5 rounded-full bg-blue-100 text-blue-700 font-bold text-xs uppercase tracking-wider mb-6">
                DASHBOARD INTERAKTIF
              </div>
              <h2 className="text-3xl lg:text-[2.75rem] font-extrabold text-slate-900 leading-tight mb-6">
                Semua Informasi dalam Satu Genggaman
              </h2>
              <p className="text-[17px] text-slate-600 leading-relaxed mb-8 font-medium">
                Dashboard SIPERBANG memberikan ringkasan informasi penting secara real-time untuk memudahkan monitoring dan pengambilan keputusan.
              </p>
              <button
                onClick={() => setShowModal(true)}
                className="flex items-center gap-2 bg-[#0055A5] hover:bg-[#004494] text-white px-8 py-3.5 rounded-full font-bold text-[15px] transition-all shadow-[0_4px_14px_rgba(0,85,165,0.25)]"
              >
                Lihat Preview Dashboard <ArrowRight size={18} />
              </button>
            </div>
            <div className="w-full lg:w-7/12 relative">
              <img
                src="/images/foto 2 landing page.png"
                alt="Dashboard Preview 2"
                className="w-full h-auto object-contain drop-shadow-2xl rounded-2xl"
              />
            </div>
          </div>
        </div>
      </Section>

      {/* ══════════════════════════════════════════
          TIM PENGEMBANG
      ══════════════════════════════════════════ */}
      <Section id="section-tim" bg="#ffffff" className="pt-24 pb-32">
        <div className="mx-auto px-6" style={{ maxWidth: 1280 }}>
          <div className="text-center mb-16">
            <h2 className="text-3xl lg:text-4xl font-extrabold text-slate-900">
              Tim Pengembang
            </h2>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-x-6 gap-y-12 group">
            {[
              {
                name: "Anugrah Fitri Novanda",
                role: "Universitas Hasanuddin",
                image: "/images/team/anugrah.jpg",
                linkedin: "https://www.linkedin.com/in/anugrah-fitri-817037219?utm_source=share_via&utm_content=profile&utm_medium=member_ios",
                instagram: "https://www.instagram.com/ira.fitri4343?igsh=djJlaHk4cHZvNDM2",
                github: "https://github.com/Anugrahfitri",
              },
              {
                name: "Siti Nurfadhilah Az Zahra Syam",
                role: "Universitas Hasanuddin",
                image: "/images/team/zahra.jpg",
                linkedin: "https://www.linkedin.com/in/siti-nurfadhilah-az-zahra-syam-14074336b?utm_source=share_via&utm_content=profile&utm_medium=member_ios",
                instagram: "https://www.instagram.com/fadhilahazz__?igsh=MXgyemRjdjF6MnVmdA%3D%3D&utm_source=qr",
                github: "https://github.com/Azzahra9",
              },
              {
                name: "A. Izza Syathra",
                role: "Universitas Hasanuddin",
                image: "/images/team/izza.jpg",
                linkedin: "https://www.linkedin.com/in/a-izza-syathra-a98a3b3bb?utm_source=share_via&utm_content=profile&utm_medium=member_ios",
                instagram: "https://www.instagram.com/_izzsythra?igsh=MWhqNmZ2d2d3NHh6bg==",
                github: "https://github.com/izzasyathra", 
              },
              {
                name: "Vina Sucitra",
                role: "Universitas Hasanuddin",
                image: "/images/team/vina.jpg",
                linkedin: " https://www.linkedin.com/in/vina-sucitra-6804b2384?utm_source=share_via&utm_content=profile&utm_medium=member_ios",
                instagram: "https://www.instagram.com/vina_sucitra024?igsh=MTc5Z3o0Z2JqY3I4eA==",
                github: "https://github.com/VinaSucitra",
              },
              {
                name: "Sita Rasmi Raihana",
                role: "Universitas Hasanuddin",
                image: "/images/team/sita.jpg",
                linkedin: "https://www.linkedin.com/in/sita-rasmi-raihana-546987381?utm_source=share_via&utm_content=profile&utm_medium=member_ios",
                instagram: "https://www.instagram.com/s4_rai?igsh=N3JtaTYyN2l0d2Vo",
                github: "https://github.com/Rai-14",
              },
              {
                name: "Isnadia Nurfadillah",
                role: "Universitas Hasanuddin",
                image: "/images/team/isnadia.jpg",
                linkedin: "https://www.linkedin.com/in/isnadia-nurfadillah-a3973b318?utm_source=share_via&utm_content=profile&utm_medium=member_ios",
                instagram: "https://www.instagram.com/isnadiyahh?igsh=NXFrdXJ1a3U3Zmd1",
                github: "https://github.com/Isnadia52",
              },
            ].map((member, i) => (
              <div key={i} className="flex flex-col items-center text-center group transition-transform duration-500 ease-out transform scale-100 group-hover:scale-90 group-hover:opacity-80 hover:scale-[1.35] hover:opacity-100 hover:z-20 cursor-pointer">
                <div className="w-28 h-28 rounded-full overflow-hidden bg-slate-100 mb-5 relative shadow-sm border border-slate-200 transition-all duration-500 ease-out transform hover:-translate-y-3 hover:border-blue-300 hover:shadow-[0_32px_64px_rgba(0,85,165,0.2)]">
                  <div className="absolute inset-0 rounded-full border border-transparent transition-all duration-500 ease-out hover:border-blue-300" />
                  <img 
                    src={member.image || `https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}&background=0055A5&color=fff&size=128&font-size=0.33`} 
                    alt={member.name}
                    className="w-full h-full object-cover transition-transform duration-500 ease-out hover:scale-[1.4]"
                  />
                </div>
                <h4 className="text-lg font-bold text-slate-900 leading-tight mb-1">{member.name}</h4>
                <p className="text-sm text-slate-500 font-semibold mb-4">{member.role}</p>
                <div className="flex items-center gap-3 text-slate-400">
                  <a href={member.linkedin} target="_blank" rel="noreferrer" className="hover:text-[#0077b5] transition-colors"><Linkedin size={20} /></a>
                  <a href={member.instagram} target="_blank" rel="noreferrer" className="hover:text-[#E1306C] transition-colors"><Instagram size={20} /></a>
                  <a href={member.github || "#"} target="_blank" rel="noreferrer" className="hover:text-slate-900 transition-colors"><Github size={20} /></a>
                </div>
              </div>
            ))}
          </div>
        </div>
      </Section>

      {/* ══════════════════════════════════════════
          FAQ / KONTAK (Combined into Footer or kept simple)
      ══════════════════════════════════════════ */}
      <div id="section-faq"></div>
      <div id="section-kontak"></div>

      {/* ══════════════════════════════════════════
          FOOTER
      ══════════════════════════════════════════ */}
      <footer className="bg-[#0C2461] text-white pt-20 pb-10">
        <div className="mx-auto px-6" style={{ maxWidth: 1280 }}>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 lg:gap-8 mb-16">
            
            {/* Brand */}
            <div className="lg:col-span-2">
              <div className="flex items-center gap-3 mb-6">
                <SiperbangLogo iconOnly />
                <div>
                  <div className="font-extrabold text-xl tracking-tight leading-none text-white">SIPERBANG</div>
                  <div className="text-[10px] text-slate-400 uppercase tracking-widest mt-1 font-semibold">
                    Sistem Informasi Persediaan Barang
                  </div>
                </div>
              </div>
              <p className="text-[15px] text-slate-400 leading-relaxed mb-8 max-w-sm">
                Sistem informasi terintegrasi untuk pengelolaan persediaan barang pemerintah yang lebih efisien, akurat, dan transparan.
              </p>
              <div className="flex items-center gap-4 text-slate-400">
                <a href="#" className="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all"><Facebook size={18} /></a>
                <a href="#" className="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-pink-600 hover:text-white transition-all"><Instagram size={18} /></a>
                <a href="#" className="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-red-600 hover:text-white transition-all"><Youtube size={18} /></a>
              </div>
            </div>

            {/* Navigasi */}
            <div>
              <h4 className="text-white font-bold text-lg mb-6">Navigasi</h4>
              <ul className="space-y-4">
                {["Beranda", "Tentang", "Fitur", "Panduan", "FAQ", "Kontak"].map(l => (
                  <li key={l}>
                    <button onClick={() => handleNav(l as NavLink)} className="text-slate-400 hover:text-white transition-colors text-[15px]">
                      {l}
                    </button>
                  </li>
                ))}
              </ul>
            </div>

            {/* Fitur */}
            <div>
              <h4 className="text-white font-bold text-lg mb-6">Fitur</h4>
              <ul className="space-y-4">
                {["Verifikasi Nota OCR AI", "Manajemen Barang", "Monitoring Stok", "Laporan & Analitik", "Dashboard"].map(l => (
                  <li key={l}>
                    <a href="#" className="text-slate-400 hover:text-white transition-colors text-[15px] cursor-default pointer-events-none">
                      {l}
                    </a>
                  </li>
                ))}
              </ul>
            </div>

            {/* Kontak & Didukung Oleh */}
            <div>
              <h4 className="text-white font-bold text-lg mb-6">Kontak</h4>
              <ul className="space-y-4 mb-10">
                <li className="flex items-start gap-3 text-[15px] text-slate-400">
                  <MapPin size={18} className="shrink-0 mt-0.5 text-blue-400" />
                  <span>Jl. Merdeka Barat No. 9, Jakarta Pusat<br />DKI Jakarta, Indonesia 10110</span>
                </li>
                <li className="flex items-center gap-3 text-[15px] text-slate-400">
                  <Phone size={18} className="shrink-0 text-blue-400" />
                  <span>(021) 1234 5678</span>
                </li>
                <li className="flex items-center gap-3 text-[15px] text-slate-400">
                  <Mail size={18} className="shrink-0 text-blue-400" />
                  <span>info@siperbang.id</span>
                </li>
              </ul>

              <h4 className="text-white font-bold text-lg mb-4">Didukung oleh</h4>
              <div className="flex items-center gap-3">
                <KomdigiLogo iconOnly />
                <div>
                  <div className="font-bold text-sm text-white leading-tight">KOMDIGI</div>
                  <div className="text-[10px] text-slate-400 leading-tight">
                    Kementerian Komunikasi<br/>dan Digital<br />Republik Indonesia
                  </div>
                </div>
              </div>
            </div>

          </div>

          <div className="pt-8 border-t border-slate-800 text-center text-sm text-slate-500 font-medium">
            © 2024 SIPERBANG, All rights reserved.
          </div>
        </div>
      </footer>
    </div>
  );
}
