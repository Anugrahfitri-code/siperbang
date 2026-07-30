import React from 'react';
import { ArrowRight } from 'lucide-react';

export const DashboardPreviewSection: React.FC = () => {
  return (
    <section className="py-20 bg-[#EBF3FC]/50" id="tentang">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          {/* Left */}
          <div>
            <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded bg-[#0055A5]/10 text-[#0055A5] text-xs font-bold uppercase tracking-wider mb-5">
              Dashboard Interaktif
            </div>
            <h2 className="text-3xl lg:text-4xl font-extrabold text-[#0d1b2e] leading-tight mb-5">
              Semua Informasi dalam <br />
              <span className="text-[#0055A5]">Satu Genggaman</span>
            </h2>
            <p className="text-gray-500 text-[15px] leading-relaxed mb-8 max-w-[440px]">
              Dashboard SIPERBANG memberikan ringkasan informasi penting secara real-time untuk memudahkan monitoring dan pengambilan keputusan.
            </p>
            <button className="flex items-center gap-2 bg-[#0055A5] text-white px-6 py-3 rounded-lg font-bold text-sm shadow-md shadow-blue-200 hover:bg-[#013A70] hover:-translate-y-0.5 transition-all duration-200">
              Lihat Preview Dashboard <ArrowRight size={15} />
            </button>
          </div>

          {/* Right: Dashboard preview image */}
          <div className="relative">
            <div className="relative rounded-2xl overflow-hidden shadow-2xl border border-white/80">
              <img
                src="/images/header_illustration.png"
                alt="Preview Dashboard SIPERBANG"
                className="w-full h-auto object-cover"
              />
            </div>
            {/* Decorative dots */}
            <div className="absolute -bottom-4 -left-4 w-24 h-24 rounded-full bg-[#0055A5]/5 -z-10"></div>
            <div className="absolute -top-4 -right-4 w-32 h-32 rounded-full bg-[#00A1E4]/5 -z-10"></div>
          </div>
        </div>
      </div>
    </section>
  );
};
