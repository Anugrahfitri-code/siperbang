import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { apiFetch } from "../api";
import {
  defaultSiteSettings,
  normalizeAssetUrl,
  type SiteSettingsData,
} from "../settings";

export interface SettingsContextValue {
  settings: SiteSettingsData;
  isLoading: boolean;
  hasLoaded: boolean;
  error: string | null;
  refetchSettings: () => Promise<SiteSettingsData | null>;
  normalizeLogoUrl: (url?: string, fallback?: string) => string;
}

const SettingsContext = createContext<SettingsContextValue>({
  settings: defaultSiteSettings,
  isLoading: false,
  hasLoaded: false,
  error: null,
  refetchSettings: async () => null,
  normalizeLogoUrl: normalizeAssetUrl,
});

export const SettingsProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [settings, setSettings] = useState<SiteSettingsData>(defaultSiteSettings);
  const [isLoading, setIsLoading] = useState(true);
  const [hasLoaded, setHasLoaded] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchSettings = useCallback(async (): Promise<SiteSettingsData | null> => {
    try {
      setIsLoading(true);
      const response = await apiFetch("/api/settings");
      const payload = await response.json().catch(() => null);

      if (!response.ok || !payload) {
        setError(payload?.message || "Gagal memuat pengaturan situs.");
        return null;
      }

      const merged = { ...defaultSiteSettings, ...(payload as Partial<SiteSettingsData>) };
      setSettings(merged);
      setError(null);
      setHasLoaded(true);
      return merged;
    } catch (err) {
      console.error("Gagal memuat pengaturan situs:", err);
      setError("Gagal memuat pengaturan situs. Periksa koneksi dan coba kembali.");
      return null;
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    void fetchSettings();
  }, [fetchSettings]);

  useEffect(() => {
    if (typeof document === "undefined") return;

    let favicon = document.querySelector<HTMLLinkElement>('link[rel~="icon"]');
    if (!favicon) {
      favicon = document.createElement("link");
      favicon.rel = "icon";
      document.head.appendChild(favicon);
    }
    favicon.href = normalizeAssetUrl(settings.favicon_url, defaultSiteSettings.favicon_url);

    let applicationName = document.querySelector<HTMLMetaElement>('meta[name="application-name"]');
    if (!applicationName) {
      applicationName = document.createElement("meta");
      applicationName.name = "application-name";
      document.head.appendChild(applicationName);
    }
    applicationName.content = settings.app_name;

    let description = document.querySelector<HTMLMetaElement>('meta[name="description"]');
    if (!description) {
      description = document.createElement("meta");
      description.name = "description";
      document.head.appendChild(description);
    }
    description.content = settings.app_subtitle;
  }, [settings.app_name, settings.app_subtitle, settings.favicon_url]);

  const value = useMemo(
    () => ({
      settings,
      isLoading,
      hasLoaded,
      error,
      refetchSettings: fetchSettings,
      normalizeLogoUrl: normalizeAssetUrl,
    }),
    [settings, isLoading, hasLoaded, error, fetchSettings],
  );

  return <SettingsContext.Provider value={value}>{children}</SettingsContext.Provider>;
};

export const useSettings = (): SettingsContextValue => useContext(SettingsContext);
export type { SiteSettingsData } from "../settings";
