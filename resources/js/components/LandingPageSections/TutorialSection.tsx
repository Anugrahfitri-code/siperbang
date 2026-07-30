import React from 'react';
import { PackageCheck, ShieldCheck, Database, FileText } from 'lucide-react';

export const TutorialSection: React.FC = () => {
  return (
    <section className="py-24 bg-white" id="tutorial">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-3xl mx-auto mb-16">
          <p className="text-slate-500 mb-4 font-medium text-sm">www.siperbang.com</p>
          <h2 className="text-sm font-extrabold text-amber-500 uppercase tracking-widest mb-3 flex items-center justify-center gap-2">
            <span className="w-4 h-0.5 bg-amber-500"></span>
            Tutorial Penggunaan
            <span className="w-4 h-0.5 bg-amber-500"></span>
          </h2>
          <h3 className="text-3xl md:text-4xl font-extrabold text-slate-800 mb-6">
            Mari Lihat <span className="text-amber-500">Fitur Kami</span>
          </h3>
          <p className="text-slate-500 max-w-xl mx-auto">
            Sistem kami dirancang agar mudah digunakan oleh berbagai divisi tanpa perlu pelatihan yang rumit.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {[
            {
              bg: "bg-indigo-50",
              icon: <PackageCheck size={60} className="text-indigo-200" />,
              badge: "Pengajuan",
              title: "Buat Pengajuan (BON)",
              desc: "Pilih barang yang dibutuhkan dari katalog, tentukan jumlahnya, dan kirimkan pengajuan secara digital dengan cepat."
            },
            {
              bg: "bg-amber-50",
              icon: <ShieldCheck size={60} className="text-amber-200" />,
              badge: "Verifikasi",
              title: "Cek & Verifikasi Stok",
              desc: "Tim persediaan akan mengecek ketersediaan stok dan memverifikasi pengajuan Anda dalam sistem secara instan."
            },
            {
              bg: "bg-emerald-50",
              icon: <Database size={60} className="text-emerald-200" />,
              badge: "Distribusi",
              title: "Proses Distribusi",
              desc: "Barang diserahkan ke pengaju, dan sistem secara otomatis memotong stok yang ada di gudang tanpa perlu catat manual."
            }
          ].map((item, i) => (
            <div key={i} className="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-md hover:shadow-xl hover:-translate-y-2 transition-all duration-300">
              {/* Thumbnail Image Placeholder */}
              <div className={`h-56 ${item.bg} relative flex items-center justify-center`}>
                 <div className="absolute top-4 right-4 bg-amber-500 text-white text-xs font-bold px-4 py-1.5 rounded-full shadow-md">
                   {item.badge}
                 </div>
                 {item.icon}
              </div>
              {/* Card Body */}
              <div className="p-8">
                <div className="flex items-center gap-1 text-amber-400 mb-4">
                  {[1,2,3,4,5].map(star => <span key={star}>★</span>)}
                  <span className="text-slate-400 text-sm ml-2 font-semibold">(4.9)</span>
                </div>
                <h4 className="text-xl font-bold text-slate-800 mb-3">{item.title}</h4>
                <p className="text-slate-500 mb-6 text-sm leading-relaxed border-b border-slate-100 pb-6">
                  {item.desc}
                </p>
                <div className="flex justify-between items-center">
                  <div className="text-slate-400 text-sm font-medium flex items-center gap-2">
                    <FileText size={16} /> 3 Langkah
                  </div>
                  <button className="text-indigo-600 font-bold text-sm hover:text-indigo-800 transition-colors">
                    Lihat Detail &rarr;
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};
