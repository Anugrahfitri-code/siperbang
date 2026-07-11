import React from "react";

export const SiperbangLogo: React.FC<{ className?: string; iconOnly?: boolean }> = ({
  className = "",
  iconOnly = false,
}) => {
  return (
    <div className={`flex items-center gap-3 ${className}`}>
      {/* 3D Box Icon & Particles */}
      <div className="relative w-12 h-12 flex-shrink-0">
        <svg
          viewBox="0 0 100 100"
          className="w-full h-full"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          {/* Rising Pixel Particles */}
          {/* Red Pixel */}
          <rect x="30" y="36" width="10" height="10" rx="1.5" fill="#B90015" />
          {/* Blue Pixels */}
          <rect x="42" y="44" width="10" height="10" rx="1.5" fill="#0055A5" />
          <rect x="44" y="24" width="10" height="10" rx="1.5" fill="#00A1E4" />
          <rect x="56" y="32" width="10" height="10" rx="1.5" fill="#00A1E4" />
          <rect x="52" y="42" width="6" height="6" rx="1" fill="#F2B818" />

          {/* 3D Box */}
          {/* Left Side (Dark Blue) */}
          <path
            d="M20 50 L48 64 L48 90 L20 74 Z"
            fill="#013A70"
          />
          {/* Right Side (Light Blue with White Arrow) */}
          <path
            d="M48 64 L80 50 L80 74 L48 90 Z"
            fill="#00A1E4"
          />
          {/* Arrow inside Right Side (White) */}
          <path
            d="M52 78 L72 68 M72 68 L64 67 M72 68 L71 74"
            stroke="white"
            strokeWidth="3.5"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
          {/* Top Flap Left (Gold/Yellow) */}
          <path
            d="M20 50 L48 36 L48 64 L20 50 Z"
            fill="#E5A800"
            opacity="0.8"
          />
          {/* Top Flap Right (Gold/Yellow) */}
          <path
            d="M48 36 L80 50 L48 64 L48 36 Z"
            fill="#F2B818"
          />
        </svg>
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
          <span className="text-[10px] text-[#7A7A7A] font-medium tracking-wide mt-1 leading-none uppercase">
            Sistem Informasi Penyediaan Barang
          </span>
        </div>
      )}
    </div>
  );
};

export const KomdigiLogo: React.FC<{ className?: string; iconOnly?: boolean }> = ({
  className = "",
  iconOnly = false,
}) => {
  return (
    <div className={`flex items-center gap-2 ${className}`}>
      {/* Connected Nodes Icon */}
      <div className="relative w-10 h-10 flex-shrink-0">
        <svg
          viewBox="0 0 100 100"
          className="w-full h-full"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          {/* Connector Paths (Subtle Grays/Blues) */}
          <path d="M40 30 H60" stroke="#0055A5" strokeWidth="4" strokeLinecap="round" />
          <path d="M40 50 H60" stroke="#00A1E4" strokeWidth="4" strokeLinecap="round" />
          <path d="M30 40 V60" stroke="#013A70" strokeWidth="4" strokeLinecap="round" />
          <path d="M70 30 V50" stroke="#F2B818" strokeWidth="4" strokeLinecap="round" />

          {/* Node 1: Red (Top Left) */}
          <rect x="24" y="24" width="16" height="16" rx="4" fill="#B90015" />
          {/* Node 2: Light Blue (Top Right) */}
          <rect x="56" y="20" width="20" height="20" rx="5" fill="#00A1E4" />
          {/* Node 3: Dark Blue (Middle Left) */}
          <rect x="18" y="48" width="22" height="22" rx="5" fill="#013A70" />
          {/* Node 4: Cyan (Middle Right) */}
          <rect x="54" y="44" width="22" height="22" rx="5" fill="#00A1E4" />
          {/* Node 5: Gold (Bottom Right Accent) */}
          <rect x="68" y="70" width="14" height="14" rx="3.5" fill="#F2B818" />
        </svg>
      </div>

      {!iconOnly && (
        <div className="flex flex-col select-none border-l border-gray-300 pl-2">
          <span className="text-sm font-extrabold text-[#4A4A4A] tracking-wider leading-none">
            KOMDIGI
          </span>
          <span className="text-[8px] text-[#7A7A7A] font-semibold tracking-tight leading-tight mt-0.5">
            Kementerian Komunikasi dan Digital<br />Republik Indonesia
          </span>
        </div>
      )}
    </div>
  );
};
