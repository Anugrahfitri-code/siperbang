import React from "react";
import { useSettings } from "../context/SettingsContext";

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
  const { settings, normalizeLogoUrl } = useSettings();
  const logoUrl = normalizeLogoUrl(settings.app_logo_url);
  const appName = settings.app_name || "SIPERBANG";
  const appSubtitle = settings.app_subtitle || "Sistem Informasi Persediaan Barang";

  return (
    <div className={`flex items-center ${className}`}>
      <div className="relative w-10 h-10 flex-shrink-0">
        <img
          src={logoUrl}
          alt={`Logo ${appName}`}
          className="w-full h-full object-contain scale-[1.5] select-none pointer-events-none"
          onError={(e) => {
            const img = e.currentTarget;
            img.onerror = null;
            img.src = "/images/icon baru.png";
          }}
        />
      </div>

      {!iconOnly && (
        <div className="flex flex-col select-none justify-center ml-2">
          <div className="text-sm font-extrabold tracking-wider leading-none flex items-center">
            {appName === "SIPERBANG" ? (
              <>
                <span className="text-[#a0258b]">I</span>
                <span className="text-[#1a50a1]">P</span>
                <span className="text-[#00b5e9]">E</span>
                <span className="text-[#2b3d88]">R</span>
                <span className="text-[#f7941d]">B</span>
                <span className="text-[#f26522]">A</span>
                <span className="text-[#ef4136]">N</span>
                <span className="text-[#d7195d]">G</span>
              </>
            ) : (
              <span className={lightText ? "text-white" : "text-slate-800"}>{appName}</span>
            )}
          </div>

          <div
            className={`text-[10px] text-left font-semibold tracking-tight mt-0.5 leading-tight uppercase ${
              lightText ? "text-white" : "text-black"
            }`}
          >
            {appSubtitle}
          </div>
        </div>
      )}
    </div>
  );
};

export const KomdigiLogo: React.FC<{
  className?: string;
  iconOnly?: boolean;
}> = ({
  className = "",
  iconOnly = false,
}) => {
  const { settings, normalizeLogoUrl } = useSettings();
  const logoUrl = normalizeLogoUrl(settings.instansi_logo_url);
  const instansiName = settings.instansi_name || "KOMDIGI";
  const instansiSub = settings.instansi_sub || "Kementerian Komunikasi dan Digital Republik Indonesia";

  return (
    <div className={`flex items-center gap-4 ${className}`}>
      <div className="relative w-9 h-9 flex-shrink-0">
        <img
          src={logoUrl}
          alt={`Logo ${instansiName}`}
          className="w-full h-full object-contain select-none pointer-events-none"
          onError={(e) => {
            const img = e.currentTarget;
            img.onerror = null;
            img.src = "/images/komdigi-logo.png";
          }}
        />
      </div>

      {!iconOnly && (
        <div className="flex flex-col select-none text-left">
          <span className="text-sm font-extrabold text-[#4A4A4A] tracking-wider leading-none">
            {instansiName}
          </span>

          <span className="text-[10px] text-[#7A7A7A] font-semibold tracking-tight leading-tight mt-0.5">
            {instansiSub}
          </span>
        </div>
      )}
    </div>
  );
};