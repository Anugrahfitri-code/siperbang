import React, { useState } from "react";
import { UserRole } from "../types";
import { LogIn, Loader2 } from "lucide-react";
import { SiperbangLogo } from "./Logos";
import { apiFetch } from "../api";

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

export function LoginScreen({ onLogin }: LoginScreenProps) {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      const response = await apiFetch("/api/login", {
        method: "POST",
        body: JSON.stringify({ username, password }),
      });

      const data = await response.json();

      if (response.ok) {
        onLogin(data.user as AuthenticatedUser);
      } else {
        setError(
          data.message ||
            "Login gagal. Periksa kembali username dan password Anda.",
        );
      }
    } catch (err) {
      setError("Terjadi kesalahan jaringan.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div
      className="h-screen w-screen overflow-hidden flex flex-col lg:flex-row font-sans text-slate-800"
      style={{
        backgroundImage: "url('/images/login-bg.png')",
        backgroundSize: "cover",
        backgroundPosition: "center",
      }}
    >
      {/* Konten Kiri */}
      <div className="hidden lg:flex lg:w-[55%] h-full flex-col lg:pl-16 lg:pr-20 py-12">
        <div className="w-full max-w-[650px] mx-auto h-full flex flex-col items-center text-center">

          {/* Logo */}
          <div className="flex-shrink-0 flex items-center justify-center gap-8 mt-4">
            <SiperbangLogo />
            <div className="w-px h-16 bg-slate-300"></div>
            <img src="/images/komdigi-logo.png" alt="Logo KOMDIGI" className="h-16 w-auto object-contain" />
          </div>

          {/* Judul dan Deskripsi */}
          <div className="mt-10 flex-shrink-0 w-full max-w-[480px] text-left">
            <h2 className="text-3xl lg:text-4xl font-extrabold text-[#183b63] leading-tight">
              Selamat Datang di
              <br />
              Portal SIPERBANG.
            </h2>
            <p className="mt-4 text-[14px] lg:text-[15px] leading-relaxed text-slate-600">
              Pusat pengelolaan persediaan barang secara digital, dilengkapi
              fitur verifikasi nota otomatis menggunakan teknologi OCR AI, dan
              pemantauan stok real-time.
            </p>
          </div>

          {/* Ilustrasi */}
          <div className="flex-grow flex items-center justify-center w-full max-w-[500px] min-h-0 mt-8 mb-4 relative">
            <img
              src="/images/login-illustration.png"
              alt="Ilustrasi portal SIPERBANG"
              className="w-full h-full object-contain object-center select-none pointer-events-none scale-[1.35] lg:scale-[1.5] origin-center"
              style={{ filter: "drop-shadow(6px 8px 4px rgba(0, 0, 0, 0.12))" }}
            />
          </div>

        </div>
      </div>

      {/* Konten Kanan (Form Login) */}
      <div className="w-full lg:w-[45%] h-full flex flex-col items-center justify-center px-8 lg:pr-40 lg:pl-0">
        <div className="w-full max-w-[420px] flex flex-col justify-center">

          <div className="mb-8 text-center">
            <h2 className="text-2xl font-extrabold text-slate-800">
              Masuk ke Sistem
            </h2>
            <p className="text-sm text-slate-500 mt-2">
              Silakan isi username dan password Anda.
            </p>
          </div>

          {error && (
            <div className="mb-4 p-3 rounded-lg bg-rose-50 text-rose-600 text-xs font-medium border border-rose-100">
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-5">
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">
                Username
              </label>
              <input
                type="text"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                className="w-full px-4 py-3 rounded-xl border-2 border-slate-200 bg-white focus:border-indigo-600 focus:ring-0 transition-colors text-sm font-medium text-slate-800"
                placeholder="Masukkan username"
                required
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">
                Password
              </label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full px-4 py-3 rounded-xl border-2 border-slate-200 bg-white focus:border-indigo-600 focus:ring-0 transition-colors text-sm font-medium text-slate-800"
                placeholder="••••••••"
                required
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2 mt-4"
            >
              {loading ? (
                <Loader2 size={18} className="animate-spin" />
              ) : (
                <LogIn size={18} />
              )}
              {loading ? "Memverifikasi..." : "Masuk"}
            </button>
          </form>

          <div className="mt-6 text-center">
            <p className="text-sm text-slate-500">
              Belum memiliki akun?{" "}
              <span className="text-indigo-600 font-semibold cursor-default">
                Hubungi administrator
              </span>{" "}
              untuk mendapatkan akses.
            </p>
          </div>

          {/* Akses Aman */}
          <div className="mt-8">
            <div className="bg-white/60 backdrop-blur-md border border-[#b4cce8] rounded-2xl px-4 py-4 flex items-start gap-4 text-left shadow-sm">
              <div className="flex-shrink-0 text-blue-600 mt-0.5">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
              </div>
              <div>
                <h3 className="text-sm font-bold uppercase tracking-wide text-[#24508a]">
                  Akses Aman
                </h3>
                <p className="mt-1 text-xs leading-snug text-slate-600">
                  Sistem menggunakan autentikasi terenkripsi. Silakan masuk
                  dengan kredensial Anda untuk mengakses fitur SIPERBANG.
                </p>
              </div>
            </div>
          </div>

          <div className="mt-6 pt-4 border-t border-slate-200/60 text-center">
            <p className="text-xs text-slate-400 font-medium uppercase tracking-widest">
              SIPERBANG - Authentication Mode
            </p>
          </div>

        </div>
      </div>

    </div>
  );
}
