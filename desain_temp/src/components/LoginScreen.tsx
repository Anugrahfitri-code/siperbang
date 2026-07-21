import React from "react";
import { UserRole } from "../types";
import { PackageOpen, Users, ShieldCheck, KeyRound } from "lucide-react";

interface LoginScreenProps {
  onLogin: (role: UserRole) => void;
}

export function LoginScreen({ onLogin }: LoginScreenProps) {
  return (
    <div className="min-h-screen bg-slate-50 flex items-center justify-center p-4 font-sans relative overflow-hidden">
      {/* Background Decor */}
      <div className="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-200/50 rounded-full blur-3xl opacity-50"></div>
      <div className="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-amber-200/50 rounded-full blur-3xl opacity-50"></div>

      <div className="w-full max-w-4xl grid grid-cols-1 md:grid-cols-2 bg-white rounded-2xl shadow-xl overflow-hidden relative z-10 border border-slate-100">
        
        {/* Left Side: Branding / Intro */}
        <div className="bg-indigo-600 p-10 flex flex-col justify-between text-white relative overflow-hidden">
          {/* Subtle pattern background */}
          <div className="absolute inset-0 opacity-10" style={{ backgroundImage: 'radial-gradient(circle at 2px 2px, white 1px, transparent 0)', backgroundSize: '24px 24px' }}></div>
          
          <div className="relative z-10">
            <div className="flex items-center gap-3 mb-8">
              <div className="bg-white/20 p-2.5 rounded-lg backdrop-blur-sm border border-white/30">
                <PackageOpen size={28} className="text-white" />
              </div>
              <div>
                <h1 className="text-xl font-extrabold tracking-tight">SIPERBANG</h1>
                <p className="text-xs font-medium text-indigo-200 uppercase tracking-widest">Sistem Penyediaan Barang</p>
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
                <h3 className="text-xs font-bold text-white mb-1 uppercase tracking-wider">Akses Terotorisasi</h3>
                <p className="text-xs text-indigo-100 leading-relaxed">
                  Pilih peran Anda untuk memasuki dashboard khusus yang disesuaikan dengan tanggung jawab kerja Anda.
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Right Side: Login Options */}
        <div className="p-10 flex flex-col justify-center">
          <div className="mb-8 text-center md:text-left">
            <h2 className="text-2xl font-extrabold text-slate-800">Masuk ke Sistem</h2>
            <p className="text-sm text-slate-500 mt-2">Silakan pilih peran untuk melanjutkan ke dashboard simulasi.</p>
          </div>

          <div className="space-y-4">
            {/* Login Petugas */}
            <button
              onClick={() => onLogin(UserRole.PETUGAS_PERSERDIAN)}
              className="w-full group text-left bg-white border-2 border-slate-200 hover:border-indigo-600 rounded-xl p-4 transition-all hover:shadow-md flex items-center justify-between"
            >
              <div className="flex items-center gap-4">
                <div className="bg-slate-50 group-hover:bg-indigo-50 text-slate-500 group-hover:text-indigo-600 p-3 rounded-lg transition-colors">
                  <KeyRound size={24} />
                </div>
                <div>
                  <h3 className="text-sm font-bold text-slate-800 group-hover:text-indigo-700 transition-colors">Petugas Persediaan</h3>
                  <p className="text-xs text-slate-500 mt-1">Akses untuk Iwan Setiawan</p>
                </div>
              </div>
              <div className="w-8 h-8 rounded-full border-2 border-slate-200 group-hover:border-indigo-600 flex items-center justify-center transition-colors">
                <div className="w-2.5 h-2.5 rounded-full bg-transparent group-hover:bg-indigo-600 transition-colors"></div>
              </div>
            </button>

            {/* Login Ketua Tim */}
            <button
              onClick={() => onLogin(UserRole.KETUA_TIM)}
              className="w-full group text-left bg-white border-2 border-slate-200 hover:border-amber-500 rounded-xl p-4 transition-all hover:shadow-md flex items-center justify-between"
            >
              <div className="flex items-center gap-4">
                <div className="bg-slate-50 group-hover:bg-amber-50 text-slate-500 group-hover:text-amber-600 p-3 rounded-lg transition-colors">
                  <Users size={24} />
                </div>
                <div>
                  <h3 className="text-sm font-bold text-slate-800 group-hover:text-amber-700 transition-colors">Ketua Tim Kerja</h3>
                  <p className="text-xs text-slate-500 mt-1">Akses untuk Budi Santoso (TU)</p>
                </div>
              </div>
              <div className="w-8 h-8 rounded-full border-2 border-slate-200 group-hover:border-amber-500 flex items-center justify-center transition-colors">
                <div className="w-2.5 h-2.5 rounded-full bg-transparent group-hover:bg-amber-500 transition-colors"></div>
              </div>
            </button>

            {/* Login Superadmin */}
            <button
              onClick={() => onLogin(UserRole.SUPERADMIN)}
              className="w-full group text-left bg-white border-2 border-slate-200 hover:border-emerald-500 rounded-xl p-4 transition-all hover:shadow-md flex items-center justify-between"
            >
              <div className="flex items-center gap-4">
                <div className="bg-slate-50 group-hover:bg-emerald-50 text-slate-500 group-hover:text-emerald-600 p-3 rounded-lg transition-colors">
                  <ShieldCheck size={24} />
                </div>
                <div>
                  <h3 className="text-sm font-bold text-slate-800 group-hover:text-emerald-700 transition-colors">Superadmin</h3>
                  <p className="text-xs text-slate-500 mt-1">Kelola Akun Pengguna</p>
                </div>
              </div>
              <div className="w-8 h-8 rounded-full border-2 border-slate-200 group-hover:border-emerald-500 flex items-center justify-center transition-colors">
                <div className="w-2.5 h-2.5 rounded-full bg-transparent group-hover:bg-emerald-500 transition-colors"></div>
              </div>
            </button>
          </div>
          
          <div className="mt-8 pt-6 border-t border-slate-100 text-center">
            <p className="text-xs text-slate-400 font-medium uppercase tracking-widest">
              SIPERBANG Prototype v1.1
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
