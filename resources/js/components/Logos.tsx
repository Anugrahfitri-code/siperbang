import React from "react";

interface SiperbangLogoProps {
  className?: string;
  iconOnly?: boolean;
  lightText?: boolean;
}

export const SiperbangLogo: React.FC<SiperbangLogoProps> = ({
  className = "",
  iconOnly = false,
  lightText = false,
}) => {
  return (
    <div className={`flex items-center gap-3 ${className}`}>
      {/*
        Logo baru digunakan secara global.
        Semua pemanggilan <SiperbangLogo /> akan memakai logo ini,
        termasuk halaman login dan navbar setelah login.
      */}
      <div className="relative w-12 h-12 flex-shrink-0 overflow-hidden">
        <img
          src="/images/siperbang-logo.png"
          alt="Logo SIPERBANG"
          className="w-full h-full object-contain select-none pointer-events-none"
        />
      </div>

      {!iconOnly && (
        <div className="flex flex-col select-none">
          <div className="text-2xl font-bold tracking-tight leading-none flex items-center">
            <span className="text-[#0055A5]">S</span>
            <span className="text-[#00A1E4]">I</span>
            <span className="text-[#013A70]">PERB</span>
            <span className="text-[#00A1E4]">A</span>
            <span className="text-[#0055A5]">NG</span>
          </div>

          <span
            className={`text-xs font-medium tracking-wide mt-1 leading-none uppercase ${
              lightText ? "text-indigo-200" : "text-[#7A7A7A]"
            }`}
          >
            Sistem Informasi Penyediaan Barang
          </span>
        </div>
      )}
    </div>
  );
};

export const KomdigiLogo: React.FC<{
  className?: string;
  iconOnly?: boolean;
}> = ({ className = "", iconOnly = false }) => {
  return (
    <div className={`flex items-center gap-2 ${className}`}>
      <div className="relative w-9 h-9 flex-shrink-0">
        <img
          src="/images/komdigi-logo.png"
          alt="Logo KOMDIGI"
          className="w-full h-full object-contain select-none pointer-events-none"
        />
      </div>

      {!iconOnly && (
        <div className="flex flex-col select-none">
          <span className="text-sm font-extrabold text-[#4A4A4A] tracking-wider leading-none">
            KOMDIGI
          </span>

          <span className="text-[10px] text-[#7A7A7A] font-semibold tracking-tight leading-tight mt-0.5">
            Kementerian Komunikasi dan Digital
            <br />
            Republik Indonesia
          </span>
        </div>
      )}
    </div>
  );
};
