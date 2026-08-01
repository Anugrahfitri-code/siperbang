export interface SiteSettingsData {
  app_name: string;
  app_subtitle: string;
  instansi_name: string;
  instansi_sub: string;
  login_heading: string;
  login_description: string;
  footer_copyright: string;
  app_logo_url: string;
  instansi_logo_url: string;
  favicon_url: string;
  app_name_colors?: string;
  instansi_name_colors?: string;
  contact_address?: string;
  contact_phone?: string;
  contact_email?: string;
}

export const defaultSiteSettings: SiteSettingsData = {
  app_name: "SIPERBANG",
  app_subtitle: "Sistem Informasi Persediaan Barang",
  instansi_name: "KOMDIGI",
  instansi_sub: "Kementerian Komunikasi dan Digital Republik Indonesia",
  login_heading: "Selamat Datang di Portal SIPERBANG.",
  login_description:
    "Pusat pengelolaan persediaan barang secara digital, dilengkapi fitur verifikasi nota otomatis menggunakan teknologi OCR AI, dan pemantauan stok real-time.",
  footer_copyright: "© {year} {instansi_name}. Seluruh hak cipta dilindungi.",
  app_logo_url: "/images/brand/siperbang-symbol.png",
  instansi_logo_url: "/images/brand/komdigi-logo.png",
  favicon_url: "/images/brand/siperbang-symbol.png",
  app_name_colors: '["#a0258b","#1a50a1","#00b5e9","#2b3d88","#f7941d","#f26522","#ef4136","#d7195d"]',
  instansi_name_colors: '[]',
  contact_address: 'Jl. Prof. Abdurrahman Basalamah II No.25, Karampuang, Kec. Panakkukang, Kota Makassar, Sulawesi Selatan 9023',
  contact_phone: '0851-1729-7705',
  contact_email: 'bblsdm.makassar@komdigi.go.id',
};

export const normalizeAssetUrl = (
  url: string | undefined,
  fallback = defaultSiteSettings.app_logo_url,
): string => {
  if (!url || typeof url !== "string") {
    return fallback;
  }

  if (/^(https?:)?\/\//i.test(url) || url.startsWith("blob:") || url.startsWith("data:")) {
    return url;
  }

  return url.startsWith("/") ? url : `/${url}`;
};

export const renderBrandingTemplate = (
  template: string | undefined,
  settings: Partial<SiteSettingsData>,
): string => {
  const source = template || defaultSiteSettings.footer_copyright;

  return source
    .replaceAll("{year}", String(new Date().getFullYear()))
    .replaceAll("{app_name}", settings.app_name || defaultSiteSettings.app_name)
    .replaceAll("{instansi_name}", settings.instansi_name || defaultSiteSettings.instansi_name)
    .replaceAll("{instansi_full_name}", settings.instansi_sub || defaultSiteSettings.instansi_sub);
};

export const safeFilePrefix = (name: string | undefined): string => {
  const normalized = (name || defaultSiteSettings.app_name)
    .normalize("NFKD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^a-zA-Z0-9]+/g, "_")
    .replace(/^_+|_+$/g, "")
    .toUpperCase();

  return normalized || "APLIKASI";
};
