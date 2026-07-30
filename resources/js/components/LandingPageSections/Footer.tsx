import React from 'react';
import { SiperbangLogo, KomdigiLogo } from '../Logos';

export const Footer: React.FC = () => {
  return (
    <footer className="bg-[#0d1b2e] text-gray-400">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 pb-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-10">

          {/* Col 1: About */}
          <div className="lg:col-span-1">
            <div className="mb-5">
              <SiperbangLogo lightText />
            </div>
            <p className="text-sm text-gray-400 leading-relaxed mb-6">
              Sistem informasi terintegrasi untuk pengelolaan persediaan barang pemerintah yang lebih efisien, akurat, dan transparan.
            </p>
            {/* Social media icons */}
            <div className="flex items-center gap-3">
              {/* Facebook */}
              <a href="#" className="w-8 h-8 rounded-full bg-white/10 text-gray-300 hover:bg-[#0055A5] hover:text-white flex items-center justify-center transition-colors">
                <svg viewBox="0 0 24 24" className="w-4 h-4" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
              </a>
              {/* Instagram */}
              <a href="#" className="w-8 h-8 rounded-full bg-white/10 text-gray-300 hover:bg-gradient-to-r hover:from-pink-500 hover:to-purple-500 hover:text-white flex items-center justify-center transition-all">
                <svg viewBox="0 0 24 24" className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
              </a>
              {/* YouTube */}
              <a href="#" className="w-8 h-8 rounded-full bg-white/10 text-gray-300 hover:bg-red-600 hover:text-white flex items-center justify-center transition-colors">
                <svg viewBox="0 0 24 24" className="w-4 h-4" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.95C5.12 20 12 20 12 20s6.88 0 8.59-.47a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58zM9.75 15.02V8.98L15.5 12l-5.75 3.02z"/></svg>
              </a>
            </div>
          </div>

          {/* Col 2: Navigasi */}
          <div>
            <h4 className="text-white font-bold mb-5 text-sm">Navigasi</h4>
            <ul className="space-y-3">
              {['Beranda', 'Tentang', 'Fitur', 'Panduan', 'FAQ', 'Kontak'].map((item) => (
                <li key={item}>
                  <a href={`#${item.toLowerCase()}`} className="text-sm text-gray-400 hover:text-white transition-colors">
                    {item}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          {/* Col 3: Fitur */}
          <div>
            <h4 className="text-white font-bold mb-5 text-sm">Fitur</h4>
            <ul className="space-y-3">
              {['Verifikasi Nota OCR AI', 'Manajemen Barang', 'Monitoring Stok', 'Laporan & Analitik', 'Dashboard'].map((item) => (
                <li key={item}>
                  <a href="#fitur" className="text-sm text-gray-400 hover:text-white transition-colors">
                    {item}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          {/* Col 4: Kontak + Dukungan */}
          <div>
            <h4 className="text-white font-bold mb-5 text-sm">Kontak</h4>
            <ul className="space-y-3 mb-8">
              <li className="flex items-start gap-2.5 text-sm text-gray-400">
                <svg viewBox="0 0 24 24" className="w-4 h-4 mt-0.5 shrink-0 text-gray-500" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>Jl. Merdeka Sarat No. 9, Jakarta Pusat DKI Jakarta, Indonesia 10110</span>
              </li>
              <li className="flex items-center gap-2.5 text-sm text-gray-400">
                <svg viewBox="0 0 24 24" className="w-4 h-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" strokeWidth="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.23a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.48h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l.81-.81a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7a2 2 0 0 1 1.72 2.03z"/></svg>
                (021) 1234 5678
              </li>
              <li className="flex items-center gap-2.5 text-sm text-gray-400">
                <svg viewBox="0 0 24 24" className="w-4 h-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" strokeWidth="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                info@siperbang.id
              </li>
            </ul>

            {/* Didukung oleh */}
            <div>
              <p className="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-3">Didukung oleh</p>
              <div className="bg-white/5 rounded-xl p-4 inline-flex">
                <KomdigiLogo />
              </div>
            </div>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="border-t border-white/10 pt-6 text-center">
          <p className="text-xs text-gray-500">© 2024 SIPERBANG. All rights reserved.</p>
        </div>
      </div>
    </footer>
  );
};
