import React from 'react';

export const FeaturesSection: React.FC = () => {
  const features = [
    {
      iconBg: 'bg-blue-100',
      iconColor: 'text-[#0055A5]',
      iconSvg: (
        <svg viewBox="0 0 24 24" className="w-7 h-7" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
          <polyline points="10 9 9 9 8 9"/>
        </svg>
      ),
      title: 'Verifikasi Nota OCR AI',
      desc: 'Ekstraksi data otomatis dari nota persediaan barang secara digital, dilengkapi verifikasi nota dengan akurasi tinggi.',
    },
    {
      iconBg: 'bg-emerald-100',
      iconColor: 'text-emerald-600',
      iconSvg: (
        <svg viewBox="0 0 24 24" className="w-7 h-7" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
        </svg>
      ),
      title: 'Manajemen Barang',
      desc: 'Kelola data barang, kategori, dan stok secara terpusat dan terstruktur.',
    },
    {
      iconBg: 'bg-indigo-100',
      iconColor: 'text-indigo-600',
      iconSvg: (
        <svg viewBox="0 0 24 24" className="w-7 h-7" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
        </svg>
      ),
      title: 'Monitoring Real-Time',
      desc: 'Pantau stok dan permintaan barang secara real-time dengan dashboard interaktif.',
    },
    {
      iconBg: 'bg-orange-100',
      iconColor: 'text-orange-500',
      iconSvg: (
        <svg viewBox="0 0 24 24" className="w-7 h-7" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/>
        </svg>
      ),
      title: 'Laporan & Analitik',
      desc: 'Laporan lengkap dan analitik untuk mendukung pengambilan keputusan.',
    },
  ];

  return (
    <section className="py-20 bg-white" id="fitur">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <div className="text-center mb-14">
          <h2 className="text-2xl font-extrabold text-[#0d1b2e] mb-3">Fitur Unggulan SIPERBANG</h2>
          <div className="w-14 h-1 bg-[#0055A5] rounded-full mx-auto"></div>
        </div>

        {/* Feature Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {features.map((feat, i) => (
            <div
              key={i}
              className="bg-white border border-gray-100 rounded-2xl p-7 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col gap-4"
            >
              <div className={`w-14 h-14 rounded-xl ${feat.iconBg} ${feat.iconColor} flex items-center justify-center`}>
                {feat.iconSvg}
              </div>
              <div>
                <h3 className="text-base font-bold text-[#0d1b2e] mb-2">{feat.title}</h3>
                <p className="text-gray-500 text-sm leading-relaxed">{feat.desc}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};
