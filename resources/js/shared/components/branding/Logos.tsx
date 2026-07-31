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
    <div className={`flex items-center ${className}`}>
      {/*
        Logo baru digunakan secara global.
        Semua pemanggilan <SiperbangLogo /> akan memakai logo ini,
        termasuk halaman login dan navbar setelah login.
      */}
      <div className="relative w-10 h-10 flex-shrink-0">
        <img
          src="/images/brand/siperbang-symbol.png"
          alt="Logo SIPERBANG"
          className="w-full h-full object-contain scale-[1.5] select-none pointer-events-none"
        />
      </div>

      {!iconOnly && (
        <div className="flex flex-col select-none justify-center ml-2">
          <div className={`text-xl font-medium tracking-wide ${lightText ? "text-white" : "text-slate-700"}`}>
            SIPERBANG
          </div>
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
    <div className={`flex items-center gap-4 ${className}`}>
      <div className="relative w-9 h-9 flex-shrink-0">
        <img
          src="/images/brand/komdigi-logo.png"
          alt="Logo KOMDIGI"
          className="w-full h-full object-contain select-none pointer-events-none"
        />
      </div>

      {!iconOnly && (
        <div className="flex flex-col select-none text-left">
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
