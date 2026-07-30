import React from 'react';

export const HowItWorksSection: React.FC = () => {
  const steps = [
    {
      num: '01',
      iconBg: 'bg-blue-50',
      iconColor: 'text-[#0055A5]',
      iconSvg: (
        <svg viewBox="0 0 24 24" className="w-8 h-8" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="17 8 12 3 7 8"/>
          <line x1="12" y1="3" x2="12" y2="15"/>
        </svg>
      ),
      title: 'Upload Nota',
      desc: 'Unggah nota atau dokumen pengadaan barang.',
    },
    {
      num: '02',
      iconBg: 'bg-sky-50',
      iconColor: 'text-sky-500',
      iconSvg: (
        <svg viewBox="0 0 24 24" className="w-8 h-8" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
          <line x1="8" y1="21" x2="16" y2="21"/>
          <line x1="12" y1="17" x2="12" y2="21"/>
        </svg>
      ),
      title: 'OCR AI',
      desc: 'Sistem membaca dan mengolah data dari nota secara otomatis.',
    },
    {
      num: '03',
      iconBg: 'bg-amber-50',
      iconColor: 'text-amber-500',
      iconSvg: (
        <svg viewBox="0 0 24 24" className="w-8 h-8" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
          <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
      ),
      title: 'Verifikasi Data',
      desc: 'Data diverifikasi sebelum disimpan ke sistem.',
    },
    {
      num: '04',
      iconBg: 'bg-blue-50',
      iconColor: 'text-[#0055A5]',
      iconSvg: (
        <svg viewBox="0 0 24 24" className="w-8 h-8" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
        </svg>
      ),
      title: 'Barang Masuk',
      desc: 'Data tersimpan dan stok barang bertambah.',
    },
    {
      num: '05',
      iconBg: 'bg-indigo-50',
      iconColor: 'text-indigo-500',
      iconSvg: (
        <svg viewBox="0 0 24 24" className="w-8 h-8" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
          <line x1="8" y1="21" x2="16" y2="21"/>
          <line x1="12" y1="17" x2="12" y2="21"/>
          <line x1="7" y1="8" x2="17" y2="8"/>
          <line x1="7" y1="12" x2="13" y2="12"/>
        </svg>
      ),
      title: 'Monitoring & Laporan',
      desc: 'Pantau stok dan buat laporan kapan saja.',
    },
  ];

  const ArrowIcon = () => (
    <div className="hidden lg:flex items-center justify-center flex-shrink-0 px-2">
      <svg viewBox="0 0 40 24" className="w-10 h-6 text-gray-300" fill="none">
        <path d="M0 12 H34" stroke="currentColor" strokeWidth="1.5" strokeDasharray="4 3"/>
        <path d="M30 6 L38 12 L30 18" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
      </svg>
    </div>
  );

  return (
    <section className="py-20 bg-gray-50/70" id="panduan">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="text-center mb-14">
          <h2 className="text-2xl font-extrabold text-[#0d1b2e] mb-3">Cara Kerja SIPERBANG</h2>
          <div className="w-14 h-1 bg-[#0055A5] rounded-full mx-auto"></div>
        </div>

        {/* Steps */}
        <div className="flex flex-col lg:flex-row items-start justify-center gap-4 lg:gap-0">
          {steps.map((step, i) => (
            <React.Fragment key={i}>
              <div className="flex flex-col items-center text-center lg:max-w-[150px] group">
                {/* Icon Container with step number badge */}
                <div className="relative mb-4">
                  <div className={`w-20 h-20 rounded-2xl ${step.iconBg} ${step.iconColor} flex items-center justify-center shadow-sm group-hover:shadow-md group-hover:-translate-y-1 transition-all duration-300`}>
                    {step.iconSvg}
                  </div>
                  <div className="absolute -top-2 -left-2 w-7 h-7 rounded-full bg-[#0055A5] text-white text-xs font-black flex items-center justify-center shadow-md">
                    {step.num}
                  </div>
                </div>
                <h4 className="text-sm font-bold text-[#0d1b2e] mb-1.5">{step.title}</h4>
                <p className="text-xs text-gray-500 leading-relaxed">{step.desc}</p>
              </div>
              {i < steps.length - 1 && <ArrowIcon />}
            </React.Fragment>
          ))}
        </div>
      </div>
    </section>
  );
};
