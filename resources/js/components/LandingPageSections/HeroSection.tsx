import React from 'react';
import { ArrowRight, ShieldCheck } from 'lucide-react';

interface HeroSectionProps {
  onNavigateToLogin: () => void;
}

export const HeroSection: React.FC<HeroSectionProps> = ({ onNavigateToLogin }) => {
  return (
    <section className="relative bg-white overflow-hidden" id="beranda">
      {/* Subtle blue background gradient on right */}
      <div className="absolute right-0 top-0 w-1/2 h-full bg-gradient-to-bl from-blue-50/80 via-blue-50/30 to-transparent pointer-events-none"></div>
      <div className="absolute right-0 top-0 w-[600px] h-[600px] rounded-full bg-[#e8f1fb]/60 blur-3xl -mr-48 -mt-32 pointer-events-none"></div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-20 relative z-10">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          {/* Left Content */}
          <div>
            {/* Badge */}
            <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded bg-[#EBF3FC] text-[#0055A5] text-xs font-bold uppercase tracking-wider mb-6">
              Sistem Informasi Penyediaan Barang
            </div>

            {/* Headline */}
            <h1 className="text-4xl lg:text-5xl font-extrabold text-[#0d1b2e] leading-tight mb-6">
              Kelola Persediaan Barang<br />
              Pemerintah Lebih{' '}
              <span className="text-[#00A1E4]">Cepat</span>,<br />
              <span className="text-[#00A1E4]">Akurat</span>, dan{' '}
              <span className="text-[#00A1E4]">Transparan</span>.
            </h1>

            {/* Description */}
            <p className="text-gray-500 text-[15px] leading-relaxed mb-8 max-w-[480px]">
              SIPERBANG membantu instansi pemerintah dalam mengelola persediaan barang secara digital, dilengkapi verifikasi nota otomatis menggunakan OCR AI dan pemantauan stok real-time.
            </p>

            {/* Buttons */}
            <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-6">
              <button
                onClick={onNavigateToLogin}
                className="flex items-center gap-2 bg-[#0055A5] text-white px-6 py-3 rounded-lg font-bold text-sm shadow-md shadow-blue-200 hover:bg-[#013A70] hover:-translate-y-0.5 transition-all duration-200"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Mulai Sekarang
              </button>
              <a
                href="#fitur"
                className="flex items-center gap-2 border border-[#0055A5] text-[#0055A5] px-6 py-3 rounded-lg font-bold text-sm hover:bg-blue-50 transition-all duration-200"
              >
                Pelajari Fitur <ArrowRight size={15} />
              </a>
            </div>

            {/* Security note */}
            <div className="flex items-center gap-2 text-gray-400 text-xs">
              <div className="w-5 h-5 rounded-full border border-green-400 flex items-center justify-center">
                <ShieldCheck size={11} className="text-green-500" />
              </div>
              Akses aman dan terlindungi dengan autentikasi terenkripsi.
            </div>
          </div>

          {/* Right: Dashboard Illustration */}
          <div className="relative hidden lg:flex items-center justify-center">
            {/* Floating stat cards */}
            <div className="absolute top-4 right-0 bg-white rounded-xl shadow-xl border border-gray-100 px-4 py-3 flex items-center gap-3 z-20 min-w-[160px]">
              <div className="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                <svg viewBox="0 0 24 24" className="w-4 h-4 text-green-500" fill="none" stroke="currentColor" strokeWidth="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              </div>
              <div>
                <p className="text-xs text-gray-400">Akurasi</p>
                <p className="text-xl font-black text-gray-800">98.7%</p>
              </div>
            </div>

            <div className="absolute bottom-10 left-0 bg-white rounded-xl shadow-xl border border-gray-100 px-4 py-3 flex items-center gap-3 z-20">
              <div className="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                <svg viewBox="0 0 24 24" className="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" strokeWidth="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
              </div>
              <div>
                <p className="text-xs text-gray-400">Barang Tersedia</p>
                <p className="text-xl font-black text-gray-800">325</p>
              </div>
            </div>

            <div className="absolute bottom-0 right-4 bg-amber-500 rounded-xl shadow-xl px-4 py-3 flex items-center gap-3 z-20">
              <div className="w-8 h-8 rounded-full bg-amber-400 flex items-center justify-center">
                <svg viewBox="0 0 24 24" className="w-4 h-4 text-white" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
              </div>
              <div>
                <p className="text-xs text-amber-100">Permintaan</p>
                <p className="text-2xl font-black text-white">3</p>
                <p className="text-xs text-amber-100">Menunggu</p>
              </div>
            </div>

            {/* Main Image */}
            <div className="relative z-10 w-full max-w-[560px] drop-shadow-2xl">
              <img
                src="/images/Ilustrasihero.jpeg"
                alt="Dashboard SIPERBANG"
                className="w-full h-auto object-contain rounded-2xl"
              />
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};
