import React from 'react';
import { PackageCheck, ArrowRight, ShieldCheck } from 'lucide-react';

export const AboutSection: React.FC = () => {
  return (
    <section className="pt-32 pb-24 md:pt-48 bg-white relative" id="tentang">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
          
          {/* Left Collage (Mirrors Eduka's overlapping images on left) */}
          <div className="relative h-[500px] hidden md:block">
            {/* Main Image Base */}
            <div className="absolute top-0 left-0 w-3/4 h-4/5 bg-slate-100 rounded-[2rem] border-4 border-white shadow-xl overflow-hidden flex items-center justify-center">
               <img src="/images/gambarabout1.jpg" alt="Tentang Aplikasi 1" className="w-full h-full object-cover" />
            </div>
            {/* Secondary Overlapping Image */}
            <div className="absolute bottom-0 right-0 w-2/3 h-2/3 bg-indigo-50 rounded-full border-8 border-white shadow-xl overflow-hidden flex items-center justify-center">
               <img src="/images/gambarabout2.jpeg" alt="Tentang Aplikasi 2" className="w-full h-full object-cover" />
            </div>
            {/* Floating Badge (Mirrors '30 Years' badge) */}
            <div className="absolute top-1/2 -left-8 transform -translate-y-1/2 bg-amber-500 text-white rounded-2xl p-6 shadow-xl shadow-amber-500/20 max-w-[180px]">
              <div className="flex items-center gap-3 mb-2">
                <span className="text-4xl font-black">100%</span>
              </div>
              <p className="font-bold text-sm leading-tight text-amber-50">Sistem Digitalisasi Terintegrasi</p>
            </div>
          </div>

          {/* Right Text Content */}
          <div className="text-left">
            <h2 className="text-sm font-extrabold text-amber-500 uppercase tracking-widest mb-3 flex items-center gap-2">
              <span className="w-6 h-0.5 bg-amber-500"></span>
              Tentang Aplikasi
            </h2>
            <h3 className="text-3xl md:text-4xl lg:text-5xl font-extrabold text-slate-800 mb-6 leading-tight">
              Sistem Persediaan Kami <span className="text-indigo-600">Menginspirasi</span> Anda.
            </h3>
            <p className="text-lg text-slate-600 mb-10 leading-relaxed">
              Ada banyak kendala dalam pencatatan persediaan secara manual. SIPERBANG hadir mengubah proses tersebut dengan antarmuka digital yang efisien, transparan, dan sangat ramah pengguna.
            </p>

            {/* Feature Blocks (Mirrors Eduka's right-side feature list) */}
            <div className="space-y-6">
              {[
                {
                  icon: "01",
                  title: "Layanan Persediaan Cepat",
                  desc: "Pemrosesan bon otomatis dan manajemen alur persetujuan instan."
                },
                {
                  icon: "02",
                  title: "Sistem Terpusat",
                  desc: "Seluruh data persediaan, dari stok hingga riwayat, diatur di satu titik pusat."
                }
              ].map((feature, i) => (
                <div key={i} className="flex gap-4 items-start">
                  <div className="w-14 h-14 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-xl shrink-0">
                    {feature.icon}
                  </div>
                  <div>
                    <h4 className="text-xl font-bold text-slate-800 mb-1">{feature.title}</h4>
                    <p className="text-slate-500 text-sm leading-relaxed">{feature.desc}</p>
                  </div>
                </div>
              ))}
            </div>

            <div className="mt-10 pt-10 border-t border-slate-100 flex items-center gap-6">
              <button className="bg-amber-500 text-slate-900 px-6 py-3 rounded-full font-bold flex items-center gap-2 hover:bg-amber-400 transition-colors">
                Jelajahi Fitur <ArrowRight size={16} />
              </button>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                   <ShieldCheck size={20} />
                </div>
                <div className="text-sm font-bold text-slate-700">Aman & Terjamin</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>
  );
};
