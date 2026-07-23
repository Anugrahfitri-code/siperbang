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
            <span className="text-[#B90015]">I</span>
            <span className="text-[#0055A5]">PERB</span>
            <span className="text-[#F2B818]">A</span>
            <span className="text-[#4A4A4A]">NG</span>
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
      <div className="relative w-10 h-10 flex-shrink-0">
        <svg
          viewBox="0 0 100 100"
          className="w-full h-full"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M40 30 H60"
            stroke="#0055A5"
            strokeWidth="4"
            strokeLinecap="round"
          />

          <path
            d="M40 50 H60"
            stroke="#00A1E4"
            strokeWidth="4"
            strokeLinecap="round"
          />

          <path
            d="M30 40 V60"
            stroke="#013A70"
            strokeWidth="4"
            strokeLinecap="round"
          />

          <path
            d="M70 30 V50"
            stroke="#F2B818"
            strokeWidth="4"
            strokeLinecap="round"
          />

          <rect
            x="24"
            y="24"
            width="16"
            height="16"
            rx="4"
            fill="#B90015"
          />

          <rect
            x="56"
            y="20"
            width="20"
            height="20"
            rx="5"
            fill="#00A1E4"
          />

          <rect
            x="18"
            y="48"
            width="22"
            height="22"
            rx="5"
            fill="#013A70"
          />

          <rect
            x="54"
            y="44"
            width="22"
            height="22"
            rx="5"
            fill="#00A1E4"
          />

          <rect
            x="68"
            y="70"
            width="14"
            height="14"
            rx="3.5"
            fill="#F2B818"
          />
        </svg>
      </div>

      {!iconOnly && (
        <div className="flex flex-col select-none border-l border-gray-300 pl-2">
          <span className="text-sm font-extrabold text-[#4A4A4A] tracking-wider leading-none">
            KOMDIGI
          </span>

          <span className="text-2xs text-[#7A7A7A] font-semibold tracking-tight leading-tight mt-0.5">
            Kementerian Komunikasi dan Digital
            <br />
            Republik Indonesia
          </span>
        </div>
      )}
    </div>
  );
};
