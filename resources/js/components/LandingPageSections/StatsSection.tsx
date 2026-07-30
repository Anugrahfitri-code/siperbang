import React from 'react';
import { Package, Users, ClipboardCheck, Award } from 'lucide-react';

export const StatsSection: React.FC = () => {
  return (
    <section className="bg-[#0055A5] py-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-8 divide-x divide-white/20">
          {[
            { icon: <Package size={40} />, count: "500", label: "Total Barang" },
            { icon: <Users size={40} />, count: "1900", label: "Pengguna Aktif" },
            { icon: <ClipboardCheck size={40} />, count: "750", label: "Bon Diproses" },
            { icon: <Award size={40} />, count: "30", label: "Unit Layanan" }
          ].map((stat, i) => (
            <div key={i} className="flex flex-col items-center text-center px-4 group">
              <div className="text-amber-400 mb-4 drop-shadow-md group-hover:scale-110 transition-transform duration-300">
                {stat.icon}
              </div>
              <h4 className="text-3xl md:text-4xl font-extrabold text-white mb-2">{stat.count}</h4>
              <p className="text-sky-100 font-medium text-sm">+ {stat.label}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};
