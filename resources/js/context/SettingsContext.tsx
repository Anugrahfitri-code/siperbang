import React, { createContext, useContext, useEffect, useMemo, useState } from "react";
import { apiFetch } from "../api";

export interface SiteSettingsData {
  app_name?: string;
  app_subtitle?: string;
  instansi_name?: string;
  instansi_sub?: string;
  login_heading?: string;
  login_description?: string;
  footer_copyright?: string;
  app_logo_url?: string;
  instansi_logo_url?: string;
}

const defaultSettings: SiteSettingsData = {
  app_name: "SIPERBANG",
  app_subtitle: "Sistem Informasi Persediaan Barang",
  instansi_name: "KOMDIGI",
  instansi_sub: "Kementerian Komunikasi dan Digital Republik Indonesia",
  login_heading: "Selamat Datang di Portal SIPERBANG.",
  login_description:
    "Pusat pengelolaan persediaan barang secara digital, dilengkapi fitur verifikasi nota otomatis menggunakan teknologi OCR AI, dan pemantauan stok real-time.",
  footer_copyright:
    "© 2026 BBPSDM Komunikasi dan Digital Makassar. Seluruh hak cipta dilindungi.",
  app_logo_url: "/images/icon baru.png",
  instansi_logo_url: "/images/komdigi-logo.png",
};

const normalizeLogoUrl = (url?: string): string => {
  if (!url || typeof url !== "string") {
    return "/images/icon baru.png";
  }

  if (/^https?:\/\//.test(url)) {
    return url;
  }

  if (url.startsWith("/")) {
    return url;
  }

  return `/${url}`;
};

export interface SettingsContextValue {
  settings: SiteSettingsData;
  isLoading: boolean;
  error: string | null;
  refetchSettings: () => Promise<void>;
  normalizeLogoUrl: (url?: string) => string;
}

const SettingsContext = createContext<SettingsContextValue>({
  settings: defaultSettings,
  isLoading: false,
  error: null,
  refetchSettings: async () => {},
  normalizeLogoUrl,
});

export const SettingsProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [settings, setSettings] = useState<SiteSettingsData>(defaultSettings);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchSettings = async () => {
    try {
      setIsLoading(true);
      const response = await apiFetch("/api/settings");
      if (!response.ok) {
        const payload = await response.json().catch(() => null);
        setError(payload?.message || "Gagal memuat pengaturan situs.");
        return;
      }

      const data = (await response.json()) as SiteSettingsData;
      setSettings({ ...defaultSettings, ...data });
      setError(null);
    } catch (err) {
      console.error("Gagal memuat pengaturan situs:", err);
      setError("Gagal memuat pengaturan situs.");
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchSettings();
  }, []);

  const value = useMemo(
    () => ({
      settings,
      isLoading,
      error,
      refetchSettings: fetchSettings,
      normalizeLogoUrl,
    }),
    [settings, isLoading, error]
  );

  return <SettingsContext.Provider value={value}>{children}</SettingsContext.Provider>;
};

export const useSettings = (): SettingsContextValue => useContext(SettingsContext);
