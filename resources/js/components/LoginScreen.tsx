import React, { useState } from "react";
import { UserRole } from "../types";
import { ShieldCheck, LogIn, Loader2 } from "lucide-react";
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
        method: 'POST',
        body: JSON.stringify({ username, password })
      });

      const data = await response.json();

      if (response.ok) {
        onLogin(data.user as AuthenticatedUser);
      } else {
        setError(data.message || "Login gagal. Periksa kembali username dan password Anda.");
      }
    } catch (err) {
      setError("Terjadi kesalahan jaringan.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 flex items-center justify-center p-4 font-sans relative overflow-hidden">
      <div className="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-200/50 rounded-full blur-3xl opacity-50"></div>
      <div className="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-amber-200/50 rounded-full blur-3xl opacity-50"></div>

      <div className="w-full max-w-4xl grid grid-cols-1 md:grid-cols-2 bg-white rounded-2xl shadow-xl overflow-hidden relative z-10 border border-slate-100">
        
        {/* Left Side */}
        <div className="bg-indigo-600 p-10 flex flex-col justify-between text-white relative overflow-hidden">
          <div className="absolute inset-0 opacity-10" style={{ backgroundImage: 'radial-gradient(circle at 2px 2px, white 1px, transparent 0)', backgroundSize: '24px 24px' }}></div>
          
          <div className="relative z-10">
            <div className="flex items-center mb-8">
              <div className="bg-white px-5 pr-6 py-3 rounded-2xl shadow-lg border border-white/30 transform transition-transform hover:scale-105 inline-block w-max">
                <SiperbangLogo />
              </div>
            </div>

            <div className="space-y-6">
              <h2 className="text-3xl font-extrabold leading-tight">
                Selamat Datang di <br /> Portal SIPERBANG.
              </h2>
              <p className="text-sm text-indigo-100 leading-relaxed font-medium">
                Pusat pengelolaan persediaan barang secara digital, dilengkapi fitur verifikasi nota otomatis menggunakan teknologi OCR AI, dan pemantauan stok real-time.
              </p>
            </div>
          </div>

          <div className="relative z-10 mt-12 bg-white/10 rounded-xl p-5 backdrop-blur-sm border border-white/20">
            <div className="flex items-start gap-3">
              <ShieldCheck className="text-emerald-300 mt-1 flex-shrink-0" size={20} />
              <div>
                <h3 className="text-xs font-bold text-white mb-1 uppercase tracking-wider">Akses Aman</h3>
                <p className="text-xs text-indigo-100 leading-relaxed">
                  Sistem menggunakan autentikasi terenkripsi dari Laravel. Silakan masuk dengan kredensial Anda.
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Right Side: Login Form */}
        <div className="p-10 flex flex-col justify-center">
          <div className="mb-8 text-center md:text-left">
            <h2 className="text-2xl font-extrabold text-slate-800">Masuk ke Sistem</h2>
            <p className="text-sm text-slate-500 mt-2">Silakan isi username dan password Anda.</p>
          </div>

          {error && (
            <div className="mb-4 p-3 rounded-lg bg-rose-50 text-rose-600 text-xs font-medium border border-rose-100">
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-5">
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Username</label>
              <input
                type="text"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                className="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-indigo-600 focus:ring-0 transition-colors text-sm font-medium text-slate-800"
                placeholder="Masukkan username"
                required
              />
            </div>
            
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Password</label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-indigo-600 focus:ring-0 transition-colors text-sm font-medium text-slate-800"
                placeholder="••••••••"
                required
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2 mt-4"
            >
              {loading ? <Loader2 size={18} className="animate-spin" /> : <LogIn size={18} />}
              {loading ? "Memverifikasi..." : "Masuk"}
            </button>
          </form>

          {/* Hint for dev */}
          <div className="mt-6 p-4 bg-slate-50 border border-slate-100 rounded-xl">
             <p className="text-xs text-slate-500 font-medium">Akun Demo (Password: password):</p>
             <ul className="text-xs text-slate-600 font-medium mt-2 space-y-1">
               <li>Petugas: <strong className="text-slate-800">iwan.s</strong></li>
               <li>Ketua Tim: <strong className="text-slate-800">budi.tu</strong></li>
               <li>Superadmin: <strong className="text-slate-800">admin</strong></li>
             </ul>
          </div>
          
          <div className="mt-6 pt-6 border-t border-slate-100 text-center">
            <p className="text-xs text-slate-400 font-medium uppercase tracking-widest">
              SIPERBANG - Authentication Mode
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
