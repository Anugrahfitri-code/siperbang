import React, { useCallback, useEffect, useId, useMemo, useRef, useState } from "react";
import {
  AlertCircle,
  Building2,
  CalendarClock,
  CheckCircle,
  Eye,
  FileClock,
  Globe,
  History,
  Layout,
  Loader2,
  LogIn,
  Pencil,
  Rocket,
  RotateCcw,
  Save,
  Trash2,
  Type,
  Upload,
} from "lucide-react";
import { apiFetch } from "../../../shared/api";
import { useSettings } from "../../../shared/context/SettingsContext";
import {
  defaultSiteSettings,
  normalizeAssetUrl,
  renderBrandingTemplate,
  type SiteSettingsData,
} from "../../../shared/settings";
import { sanitizeHtml } from "../../../shared/utils/sanitizeHtml";
import { ConfirmDialog } from "../../../shared/components/feedback/ConfirmDialog";

interface SiteSettingsProps {
  onSettingsUpdated?: () => void;
}

interface BrandingVersion {
  id: number;
  label: string;
  status: "draft" | "scheduled" | "published" | "archived";
  settings: SiteSettingsData;
  effective_from?: string | null;
  effective_until?: string | null;
  published_at?: string | null;
  notes?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  creator?: { id: number; name: string } | null;
  publisher?: { id: number; name: string } | null;
}

type ValidationErrors = Record<string, string[]>;
type SaveAction = "draft" | "publish";

const LetterColorPicker = ({ text, colorsJson, onChange }: { text: string, colorsJson: string | undefined, onChange: (newJson: string) => void }) => {
  let colors: string[] = [];
  try {
    colors = JSON.parse(colorsJson || "[]");
  } catch(e) {}
  
  if (!text) return null;
  
  return (
    <div className="flex flex-wrap gap-2 mt-3 p-3 bg-slate-50 border border-slate-100 rounded-xl">
      <div className="w-full text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Pewarnaan Per Huruf</div>
      {text.split('').map((char, idx) => {
         if (char === ' ') return <span key={idx} className="w-4"></span>;
         const color = colors[idx] || "#4f46e5";
         return (
           <div key={idx} className="flex flex-col items-center gap-1.5 group">
             <span className="text-sm font-black uppercase drop-shadow-sm" style={{color}}>{char}</span>
             <input type="color" value={color} className="w-6 h-6 p-0 border-0 rounded-md cursor-pointer opacity-80 group-hover:opacity-100 transition-opacity shadow-sm" onChange={(e) => {
               const newColors = [...colors];
               for(let i = 0; i < text.length; i++) {
                 if (!newColors[i]) newColors[i] = "#4f46e5";
               }
               newColors[idx] = e.target.value;
               onChange(JSON.stringify(newColors));
             }} />
           </div>
         );
      })}
    </div>
  )
}

type RichTextEditorProps = {
  value: string;
  onChange: (html: string) => void;
  placeholder: string;
  ariaLabel: string;
  sizeOptions: string[];
  defaultColor: string;
  minHeight: string;
};

const statusLabels: Record<BrandingVersion["status"], string> = {
  draft: "Draft",
  scheduled: "Terjadwal",
  published: "Aktif",
  archived: "Arsip",
};

const statusClasses: Record<BrandingVersion["status"], string> = {
  draft: "bg-slate-100 text-slate-700 border-slate-200",
  scheduled: "bg-amber-50 text-amber-700 border-amber-200",
  published: "bg-emerald-50 text-emerald-700 border-emerald-200",
  archived: "bg-indigo-50 text-indigo-700 border-indigo-200",
};

const formatDateTime = (value?: string | null): string => {
  if (!value) return "—";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;

  return new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date);
};

const toDateTimeLocal = (value?: string | null): string => {
  if (!value) return "";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "";
  const offset = date.getTimezoneOffset() * 60_000;
  return new Date(date.getTime() - offset).toISOString().slice(0, 16);
};

const useObjectUrl = (file: File | null): string | null => {
  const [url, setUrl] = useState<string | null>(null);

  useEffect(() => {
    if (!file) {
      setUrl(null);
      return;
    }

    const objectUrl = URL.createObjectURL(file);
    setUrl(objectUrl);
    return () => URL.revokeObjectURL(objectUrl);
  }, [file]);

  return url;
};

const FieldError: React.FC<{ errors: ValidationErrors; name: string }> = ({ errors, name }) => {
  const message = errors[name]?.[0];
  return message ? <p role="alert" className="mt-1.5 text-[11px] font-semibold text-rose-600">{message}</p> : null;
};

const readImageDimensions = async (file: File): Promise<{ width: number; height: number }> => {
  if (typeof createImageBitmap === "function") {
    const bitmap = await createImageBitmap(file);
    try {
      return { width: bitmap.width, height: bitmap.height };
    } finally {
      bitmap.close();
    }
  }

  const objectUrl = URL.createObjectURL(file);
  try {
    return await new Promise<{ width: number; height: number }>((resolve, reject) => {
      const image = new Image();
      image.onload = () => resolve({ width: image.naturalWidth, height: image.naturalHeight });
      image.onerror = () => reject(new Error("Gambar tidak dapat dibaca."));
      image.src = objectUrl;
    });
  } finally {
    URL.revokeObjectURL(objectUrl);
  }
};

const SafeRichTextEditor: React.FC<RichTextEditorProps> = ({
  value,
  onChange,
  placeholder,
  ariaLabel,
  sizeOptions,
  defaultColor,
  minHeight,
}) => {
  const editorRef = useRef<HTMLDivElement>(null);
  const savedRangeRef = useRef<Range | null>(null);
  const lastEmittedValueRef = useRef<string | null>(null);
  const helpId = useId();
  const [color, setColor] = useState(defaultColor);
  const [fontSize, setFontSize] = useState(sizeOptions[Math.floor(sizeOptions.length / 2)] || "16px");
  const [selectionHint, setSelectionHint] = useState("");

  useEffect(() => {
    if (editorRef.current && value !== lastEmittedValueRef.current) {
      if (editorRef.current.innerHTML !== value) {
        editorRef.current.innerHTML = sanitizeHtml(value || "");
        lastEmittedValueRef.current = value;
      }
    }
  }, [value]);

  const emitValue = () => {
    if (!editorRef.current) return;
    const sanitized = sanitizeHtml(editorRef.current.innerHTML);
    if (sanitized === '<br>') {
      lastEmittedValueRef.current = "";
      return onChange("");
    }
    lastEmittedValueRef.current = sanitized;
    onChange(sanitized);
  };

  const saveSelection = () => {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0 || !editorRef.current) return;
    const range = selection.getRangeAt(0);
    if (editorRef.current.contains(range.commonAncestorContainer)) {
      savedRangeRef.current = range.cloneRange();
    }
  };

  const selectedRange = (): Range | null => {
    if (!editorRef.current) return null;
    const selection = window.getSelection();
    const range = savedRangeRef.current || (selection?.rangeCount ? selection.getRangeAt(0) : null);
    if (!range || range.collapsed || !editorRef.current.contains(range.commonAncestorContainer)) {
      setSelectionHint("Blok teks yang ingin diformat terlebih dahulu.");
      return null;
    }
    return range.cloneRange();
  };

  const wrapSelection = (tagName: "strong" | "em" | "span", style?: Partial<CSSStyleDeclaration>) => {
    const range = selectedRange();
    if (!range || !editorRef.current) return;

    const wrapper = document.createElement(tagName);
    if (style) Object.assign(wrapper.style, style);

    try {
      const fragment = range.extractContents();
      
      if (style) {
        const elements = fragment.querySelectorAll("*");
        elements.forEach((el) => {
          if (el instanceof HTMLElement) {
            Object.keys(style).forEach((key) => {
              (el.style as any)[key] = "";
            });
          }
        });
      }

      wrapper.appendChild(fragment);
      range.insertNode(wrapper);
      const selection = window.getSelection();
      const nextRange = document.createRange();
      nextRange.selectNodeContents(wrapper);
      selection?.removeAllRanges();
      selection?.addRange(nextRange);
      savedRangeRef.current = nextRange.cloneRange();
      setSelectionHint("");
      emitValue();
    } catch {
      setSelectionHint("Format tidak dapat diterapkan pada seleksi tersebut.");
    }
  };

  const resetFormatting = () => {
    if (!editorRef.current) return;
    const plainText = editorRef.current.innerText;
    editorRef.current.textContent = plainText;
    setSelectionHint("Seluruh format pada bidang ini telah dihapus.");
    emitValue();
  };

  const handlePaste = (event: React.ClipboardEvent<HTMLDivElement>) => {
    event.preventDefault();
    const text = event.clipboardData.getData("text/plain");
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) return;
    const range = selection.getRangeAt(0);
    range.deleteContents();
    const node = document.createTextNode(text);
    range.insertNode(node);
    range.setStartAfter(node);
    range.collapse(true);
    selection.removeAllRanges();
    selection.addRange(range);
    emitValue();
  };

  return (
    <div className="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 transition-all focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/20">
      <div className="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-slate-100/80 p-2.5">
        <span className="text-[10px] font-bold uppercase tracking-wider text-slate-500">Format teks terpilih</span>
        <button type="button" aria-label="Tebalkan teks terpilih" onMouseDown={(e) => { e.preventDefault(); wrapSelection("strong"); }} className="flex size-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm font-black text-slate-700 hover:border-slate-400">B</button>
        <button type="button" aria-label="Miringkan teks terpilih" onMouseDown={(e) => { e.preventDefault(); wrapSelection("em"); }} className="flex size-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm font-bold italic text-slate-700 hover:border-slate-400">I</button>
        <button type="button" aria-label="Garisbawahi teks terpilih" onMouseDown={(e) => { e.preventDefault(); wrapSelection("span", { textDecoration: "underline" }); }} className="flex size-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm font-bold underline text-slate-700 hover:border-slate-400">U</button>
        <label className="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[10px] font-bold uppercase text-slate-600">
          Warna
          <span className="size-4 rounded-full border border-slate-300" style={{ backgroundColor: color }} />
          <input
            type="color"
            value={color}
            onFocus={saveSelection}
            onChange={(event) => {
              setColor(event.target.value);
              wrapSelection("span", { color: event.target.value });
            }}
            className="sr-only"
          />
        </label>
        <select
          value={fontSize}
          onFocus={saveSelection}
          onChange={(event) => {
            setFontSize(event.target.value);
            wrapSelection("span", { fontSize: event.target.value });
          }}
          className="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold text-slate-700"
        >
          {sizeOptions.map((size) => <option key={size} value={size}>{size}</option>)}
        </select>
        <button type="button" onClick={resetFormatting} className="ml-auto rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-rose-600 hover:border-rose-300 hover:bg-rose-50">Jadikan teks polos</button>
      </div>
      <div
        ref={editorRef}
        contentEditable
        suppressContentEditableWarning
        role="textbox"
        aria-multiline="true"
        aria-label={ariaLabel}
        aria-describedby={helpId}
        data-placeholder={placeholder}
        onInput={emitValue}
        onBlur={emitValue}
        onKeyUp={saveSelection}
        onMouseUp={saveSelection}
        onPaste={handlePaste}
        style={{ minHeight }}
        className="empty:before:pointer-events-none empty:before:text-slate-400 empty:before:content-[attr(data-placeholder)] cursor-text bg-white p-4 font-sans text-base leading-relaxed text-slate-800 focus:outline-none"
      />
      <p id={helpId} className="sr-only">Pilih teks dengan keyboard atau mouse, lalu gunakan kontrol format. Teks yang ditempel akan dijadikan teks polos.</p>
      {selectionHint && <p aria-live="polite" className="border-t border-slate-100 bg-white px-4 py-2 text-[10px] font-semibold text-amber-700">{selectionHint}</p>}
    </div>
  );
};

const LogoPicker: React.FC<{
  id: string;
  label: string;
  preview: string;
  file: File | null;
  onFile: (file: File | null) => void;
  onInvalid: (message: string | null) => void;
  inputRef: React.RefObject<HTMLInputElement | null>;
  favicon?: boolean;
  errors: ValidationErrors;
  errorName: string;
}> = ({ id, label, preview, file, onFile, onInvalid, inputRef, favicon = false, errors, errorName }) => {
  const handleFile = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const input = event.currentTarget;
    const selected = input.files?.[0] || null;
    if (!selected) return;

    const allowed = ["image/png", "image/jpeg", "image/webp"];
    const limit = favicon ? 1024 * 1024 : 2 * 1024 * 1024;
    const maximumDimension = favicon ? 1024 : 2000;

    const rejectFile = (message: string) => {
      input.value = "";
      onFile(null);
      onInvalid(message);
    };

    if (!allowed.includes(selected.type)) {
      rejectFile("File harus berformat PNG, JPG/JPEG, atau WebP.");
      return;
    }

    if (selected.size > limit) {
      rejectFile(`Ukuran file maksimal ${favicon ? "1MB" : "2MB"}.`);
      return;
    }

    try {
      const { width, height } = await readImageDimensions(selected);
      if (width < 1 || height < 1) {
        rejectFile("Dimensi gambar tidak valid.");
        return;
      }
      if (width > maximumDimension || height > maximumDimension) {
        rejectFile(`Dimensi gambar maksimal ${maximumDimension}×${maximumDimension} piksel.`);
        return;
      }
    } catch {
      rejectFile("Gambar tidak dapat dibaca atau berkas rusak.");
      return;
    }

    onInvalid(null);
    onFile(selected);
  };

  return (
    <div className="space-y-3 rounded-xl border border-slate-200/80 bg-slate-50 p-4">
      <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">{label}</label>
      <div className="flex items-center gap-4">
        <div className={`${favicon ? "size-14" : "size-16"} flex shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white p-2 shadow-xs`}>
          <img src={preview} alt={`Pratinjau ${label}`} className="max-h-full max-w-full object-contain" />
        </div>
        <div className="min-w-0 flex-1">
          <input ref={inputRef} type="file" id={id} accept=".png,.jpg,.jpeg,.webp" onChange={handleFile} className="hidden" />
          <label htmlFor={id} className="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-xs transition-colors hover:border-indigo-300">
            <Upload size={14} className="text-indigo-600" />
            {file ? "Ganti file" : "Pilih file"}
          </label>
          {file && <button type="button" onClick={() => { onFile(null); if (inputRef.current) inputRef.current.value = ""; }} className="ml-2 text-[11px] font-bold text-rose-600">Batalkan</button>}
          <p className="mt-1.5 text-[10px] font-medium text-slate-500">PNG, JPG/JPEG, atau WebP. {favicon ? "Maks. 1MB." : "Maks. 2MB, dimensi maks. 2000×2000 px."}</p>
          <FieldError errors={errors} name={errorName} />
        </div>
      </div>
    </div>
  );
};

export const SiteSettings: React.FC<SiteSettingsProps> = ({ onSettingsUpdated }) => {
  const { settings, isLoading, hasLoaded, error: settingsError, refetchSettings } = useSettings();
  const [versions, setVersions] = useState<BrandingVersion[]>([]);
  const [versionsLoading, setVersionsLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [rowActionId, setRowActionId] = useState<number | null>(null);
  const [editingVersionId, setEditingVersionId] = useState<number | null>(null);
  const [successMsg, setSuccessMsg] = useState("");
  const [errorMsg, setErrorMsg] = useState("");
  const [validationErrors, setValidationErrors] = useState<ValidationErrors>({});
  const [confirmDialog, setConfirmDialog] = useState<{
    open: boolean;
    title: string;
    message: string;
    variant: "info" | "warning" | "danger";
    confirmText: string;
    onConfirm: () => Promise<void>;
  } | null>(null);

  const [label, setLabel] = useState(`Identitas ${new Date().getFullYear()}`);
  const [effectiveFrom, setEffectiveFrom] = useState("");
  const [notes, setNotes] = useState("");
  const [form, setForm] = useState<SiteSettingsData>(defaultSiteSettings);
  const [persistedPreviews, setPersistedPreviews] = useState({
    app: defaultSiteSettings.app_logo_url,
    institution: defaultSiteSettings.instansi_logo_url,
    favicon: defaultSiteSettings.favicon_url,
  });

  const [appLogoFile, setAppLogoFile] = useState<File | null>(null);
  const [instansiLogoFile, setInstansiLogoFile] = useState<File | null>(null);
  const [faviconFile, setFaviconFile] = useState<File | null>(null);
  const appLogoObjectUrl = useObjectUrl(appLogoFile);
  const institutionLogoObjectUrl = useObjectUrl(instansiLogoFile);
  const faviconObjectUrl = useObjectUrl(faviconFile);
  const appLogoInputRef = useRef<HTMLInputElement | null>(null);
  const institutionLogoInputRef = useRef<HTMLInputElement | null>(null);
  const faviconInputRef = useRef<HTMLInputElement | null>(null);

  const appLogoPreview = appLogoObjectUrl || normalizeAssetUrl(persistedPreviews.app, defaultSiteSettings.app_logo_url);
  const institutionLogoPreview = institutionLogoObjectUrl || normalizeAssetUrl(persistedPreviews.institution, defaultSiteSettings.instansi_logo_url);
  const faviconPreview = faviconObjectUrl || normalizeAssetUrl(persistedPreviews.favicon, defaultSiteSettings.favicon_url);

  const updateField = <K extends keyof SiteSettingsData>(key: K, value: SiteSettingsData[K]) => {
    setForm((current) => ({ ...current, [key]: value }));
    setValidationErrors((current) => {
      if (!current[key]) return current;
      const next = { ...current };
      delete next[key];
      return next;
    });
  };

  const setClientFileError = (name: string, message: string | null) => {
    setValidationErrors((current) => {
      const next = { ...current };
      if (message) next[name] = [message];
      else delete next[name];
      return next;
    });
  };

  const clearFiles = useCallback(() => {
    setAppLogoFile(null);
    setInstansiLogoFile(null);
    setFaviconFile(null);
    if (appLogoInputRef.current) appLogoInputRef.current.value = "";
    if (institutionLogoInputRef.current) institutionLogoInputRef.current.value = "";
    if (faviconInputRef.current) faviconInputRef.current.value = "";
  }, []);

  const populateForm = useCallback((source: SiteSettingsData) => {
    const merged = { ...defaultSiteSettings, ...source };
    setForm(merged);
    setPersistedPreviews({
      app: merged.app_logo_url,
      institution: merged.instansi_logo_url,
      favicon: merged.favicon_url,
    });
    clearFiles();
  }, [clearFiles]);

  const resetToActive = () => {
    setEditingVersionId(null);
    setLabel(`Identitas ${new Date().getFullYear()}`);
    setEffectiveFrom("");
    setNotes("");
    setValidationErrors({});
    populateForm(settings);
  };

  const loadVersions = useCallback(async () => {
    try {
      setVersionsLoading(true);
      const response = await apiFetch("/api/settings/versions");
      const payload = await response.json().catch(() => null);
      if (!response.ok) throw new Error(payload?.message || "Gagal memuat riwayat identitas.");
      setVersions(Array.isArray(payload?.data) ? payload.data : []);
    } catch (error) {
      setErrorMsg(error instanceof Error ? error.message : "Gagal memuat riwayat identitas.");
    } finally {
      setVersionsLoading(false);
    }
  }, []);

  useEffect(() => {
    if (hasLoaded && editingVersionId === null) populateForm(settings);
  }, [hasLoaded, settings, editingVersionId, populateForm]);

  useEffect(() => {
    void loadVersions();
  }, [loadVersions]);

  const submit = async (action: SaveAction) => {
    const unresolvedFileError = ["app_logo", "instansi_logo", "favicon"]
      .find((field) => validationErrors[field]?.length);
    if (unresolvedFileError) {
      setSuccessMsg("");
      setErrorMsg("Perbaiki berkas gambar yang ditandai sebelum menyimpan identitas.");
      return;
    }

    setSaving(true);
    setSuccessMsg("");
    setErrorMsg("");
    setValidationErrors({});

    try {
      const body = new FormData();
      body.append("label", label);
      body.append("action", action);
      body.append("app_name", form.app_name);
      body.append("app_name_colors", form.app_name_colors || "[]");
      body.append("app_subtitle", form.app_subtitle);
      body.append("instansi_name", form.instansi_name);
      body.append("instansi_name_colors", form.instansi_name_colors || "[]");
      body.append("instansi_sub", form.instansi_sub);
      body.append("login_heading", sanitizeHtml(form.login_heading));
      body.append("login_description", sanitizeHtml(form.login_description));
      body.append("footer_copyright", form.footer_copyright);
      if (form.contact_address !== undefined) body.append("contact_address", form.contact_address);
      if (form.contact_phone !== undefined) body.append("contact_phone", form.contact_phone);
      if (form.contact_email !== undefined) body.append("contact_email", form.contact_email);
      if (effectiveFrom) body.append("effective_from", effectiveFrom);
      if (notes) body.append("notes", notes);
      if (appLogoFile) body.append("app_logo", appLogoFile);
      if (instansiLogoFile) body.append("instansi_logo", instansiLogoFile);
      if (faviconFile) body.append("favicon", faviconFile);

      const endpoint = editingVersionId
        ? `/api/settings/versions/${editingVersionId}`
        : "/api/settings/versions";
      const response = await apiFetch(endpoint, { method: "POST", body });
      const payload = await response.json().catch(() => null);

      if (!response.ok) {
        if (response.status === 422 && payload?.errors) setValidationErrors(payload.errors);
        throw new Error(payload?.message || "Pengaturan identitas gagal disimpan.");
      }

      setSuccessMsg(payload?.message || "Pengaturan identitas berhasil disimpan.");
      clearFiles();
      await loadVersions();

      if (action === "publish") {
        await refetchSettings();
        setEditingVersionId(null);
        onSettingsUpdated?.();
      } else if (payload?.version?.id) {
        setEditingVersionId(payload.version.id);
        setLabel(payload.version.label);
      }
    } catch (error) {
      setErrorMsg(error instanceof Error ? error.message : "Terjadi kesalahan jaringan saat menyimpan data.");
    } finally {
      setSaving(false);
    }
  };

  const editVersion = (version: BrandingVersion) => {
    setEditingVersionId(version.id);
    setLabel(version.label);
    setEffectiveFrom(toDateTimeLocal(version.effective_from));
    setNotes(version.notes || "");
    setValidationErrors({});
    populateForm(version.settings);
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const performVersionAction = (version: BrandingVersion, action: "publish" | "rollback" | "delete") => {
    const question = action === "delete"
      ? `Hapus draft “${version.label}”?`
      : action === "rollback"
        ? `Aktifkan kembali identitas dari “${version.label}” melalui versi rollback baru?`
        : version.status === "scheduled"
          ? `Publikasikan “${version.label}” sekarang dan batalkan waktu jadwal sebelumnya?`
          : `Publikasikan “${version.label}” sekarang?`;

    const title = action === "delete" ? "Konfirmasi Hapus" 
                : action === "rollback" ? "Konfirmasi Rollback" 
                : "Konfirmasi Publikasi";

    const variant = action === "delete" ? "danger" 
                  : action === "rollback" ? "warning" 
                  : "info";
                  
    const confirmText = action === "delete" ? "Hapus" : "Lanjutkan";

    setConfirmDialog({
      open: true,
      title,
      message: question,
      variant,
      confirmText,
      onConfirm: async () => {
        setConfirmDialog(null);
        setRowActionId(version.id);
        setErrorMsg("");
        setSuccessMsg("");

        try {
          const endpoint = action === "delete"
            ? `/api/settings/versions/${version.id}`
            : `/api/settings/versions/${version.id}/${action}`;
          const publishBody = action === "publish" && version.status === "scheduled"
            ? JSON.stringify({ effective_from: new Date().toISOString() })
            : undefined;
          const response = await apiFetch(endpoint, {
            method: action === "delete" ? "DELETE" : "POST",
            body: publishBody,
          });
          const payload = await response.json().catch(() => null);
          if (!response.ok) throw new Error(payload?.message || "Aksi pada versi identitas gagal.");
          
          setSuccessMsg(payload?.message || `Aksi identitas berhasil diproses.`);
          await loadVersions();
          
          if (action !== "delete") {
            await refetchSettings();
            onSettingsUpdated?.();
          }
          if (editingVersionId === version.id) resetToActive();
        } catch (error: any) {
          setErrorMsg(error.message || "Terjadi kesalahan saat memproses data.");
        } finally {
          setRowActionId(null);
        }
      }
    });
  };

  const footerPreview = useMemo(() => renderBrandingTemplate(form.footer_copyright, form), [form]);

  if (isLoading) {
    return <div className="flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white p-12 text-center"><Loader2 className="mb-3 animate-spin text-indigo-600" size={32} /><p className="text-sm font-semibold text-slate-600">Memuat pengaturan situs...</p></div>;
  }

  if (settingsError && !hasLoaded) {
    return (
      <div className="rounded-2xl border border-rose-200 bg-white p-8 text-center shadow-xs">
        <AlertCircle className="mx-auto mb-3 text-rose-500" size={34} />
        <h3 className="font-extrabold text-slate-800">Pengaturan aktif tidak berhasil dimuat</h3>
        <p className="mx-auto mt-2 max-w-xl text-sm text-slate-600">Form dinonaktifkan agar nilai bawaan tidak menimpa identitas produksi. {settingsError}</p>
        <button type="button" onClick={() => void refetchSettings()} className="mt-5 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-indigo-700">Coba muat kembali</button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="relative bg-gradient-to-r from-[#f8faff] to-[#f0f4ff] rounded-2xl border border-indigo-50/50 p-6 shadow-sm flex flex-col sm:flex-row justify-between sm:items-center gap-5 overflow-hidden">
        <div className="absolute right-0 top-0 w-64 h-64 bg-indigo-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
        <div className="absolute left-0 bottom-0 w-48 h-48 bg-blue-500/5 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2 pointer-events-none"></div>

        <div className="flex items-center gap-4 relative z-10">
          <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white shadow-sm border border-slate-100 text-emerald-500">
            <Globe size={26} strokeWidth={2.5} />
          </div>
          <div>
            <h2 className="text-base font-extrabold text-slate-800 uppercase tracking-wide">Kelola Identitas Situs & Branding</h2>
            <p className="text-xs font-medium text-slate-500 mt-1">
              Draft, jadwalkan, publikasikan, dan pulihkan identitas tanpa kehilangan riwayat tahunan.
            </p>
          </div>
        </div>
        
        {editingVersionId && (
          <button
            type="button"
            onClick={resetToActive}
            className="relative z-10 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-2"
          >
            <RotateCcw size={15} /> Batalkan edit draft
          </button>
        )}
      </div>

      {successMsg && <div className="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800"><CheckCircle size={18} className="shrink-0 text-emerald-600" />{successMsg}</div>}
      {errorMsg && <div className="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700"><AlertCircle size={18} className="shrink-0 text-rose-500" />{errorMsg}</div>}

      <form onSubmit={(event) => { event.preventDefault(); void submit("publish"); }} className="space-y-6">
        <div className="rounded-2xl border border-indigo-100 bg-indigo-50/40 p-5 shadow-xs">
          <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div>
              <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-600">Nama versi <span className="text-rose-500">*</span></label>
              <input value={label} onChange={(e) => setLabel(e.target.value)} required placeholder={`Contoh: Identitas ${new Date().getFullYear() + 1}`} className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
              <FieldError errors={validationErrors} name="label" />
            </div>
            <div>
              <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-600">Tanggal mulai berlaku</label>
              <input type="datetime-local" value={effectiveFrom} onChange={(e) => setEffectiveFrom(e.target.value)} className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
              <p className="mt-1.5 text-[10px] font-medium text-slate-500">Kosongkan untuk publikasi langsung. Tanggal mendatang akan menjadi versi terjadwal.</p>
              <FieldError errors={validationErrors} name="effective_from" />
            </div>
            <div>
              <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-600">Catatan perubahan</label>
              <input value={notes} onChange={(e) => setNotes(e.target.value)} placeholder="Contoh: Rebranding tahun anggaran baru" className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
              <FieldError errors={validationErrors} name="notes" />
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <section className="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
            <div className="flex items-center gap-3 border-b border-slate-100 pb-4"><div className="flex size-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><Layout size={18} /></div><div><h3 className="text-sm font-extrabold text-slate-800">Identitas Aplikasi</h3><p className="text-xs text-slate-500">Nama dan logo utama pada navbar, sidebar, metadata, dan ekspor.</p></div></div>
            <div>
              <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-600">Nama aplikasi <span className="text-rose-500">*</span></label>
              <input value={form.app_name} onChange={(e) => updateField("app_name", e.target.value)} required className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
              <LetterColorPicker text={form.app_name} colorsJson={form.app_name_colors} onChange={(v) => updateField("app_name_colors", v)} />
              <FieldError errors={validationErrors} name="app_name" />
            </div>
            <div><label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-600">Subtitle aplikasi</label><input value={form.app_subtitle} onChange={(e) => updateField("app_subtitle", e.target.value)} className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" /><FieldError errors={validationErrors} name="app_subtitle" /></div>
            <LogoPicker id="appLogoInput" label={`Logo utama ${form.app_name || "aplikasi"}`} preview={appLogoPreview} file={appLogoFile} onFile={(f) => { setAppLogoFile(f); if (!faviconFile) setFaviconFile(f); }} onInvalid={(message) => setClientFileError("app_logo", message)} inputRef={appLogoInputRef} errors={validationErrors} errorName="app_logo" />
            <LogoPicker id="faviconInput" label="Favicon browser" preview={faviconPreview} file={faviconFile} onFile={setFaviconFile} onInvalid={(message) => setClientFileError("favicon", message)} inputRef={faviconInputRef} favicon errors={validationErrors} errorName="favicon" />
          </section>

          <section className="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
            <div className="flex items-center gap-3 border-b border-slate-100 pb-4"><div className="flex size-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600"><Building2 size={18} /></div><div><h3 className="text-sm font-extrabold text-slate-800">Identitas Instansi</h3><p className="text-xs text-slate-500">Nama lembaga yang tampil pada portal dan dokumen.</p></div></div>
            <div>
              <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-600">Singkatan instansi <span className="text-rose-500">*</span></label>
              <input value={form.instansi_name} onChange={(e) => updateField("instansi_name", e.target.value)} required className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
              <LetterColorPicker text={form.instansi_name} colorsJson={form.instansi_name_colors} onChange={(v) => updateField("instansi_name_colors", v)} />
              <FieldError errors={validationErrors} name="instansi_name" />
            </div>
            <div><label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-600">Nama lengkap instansi</label><textarea value={form.instansi_sub} onChange={(e) => updateField("instansi_sub", e.target.value)} rows={3} className="w-full resize-y rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" /><FieldError errors={validationErrors} name="instansi_sub" /></div>
            <LogoPicker id="institutionLogoInput" label={`Logo instansi ${form.instansi_name || ""}`} preview={institutionLogoPreview} file={instansiLogoFile} onFile={setInstansiLogoFile} onInvalid={(message) => setClientFileError("instansi_logo", message)} inputRef={institutionLogoInputRef} errors={validationErrors} errorName="instansi_logo" />
          </section>
        </div>

        <section className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
          <div className="flex items-center gap-3 border-b border-slate-100 pb-4"><div className="flex size-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600"><LogIn size={18} /></div><div><h3 className="text-sm font-extrabold text-slate-800">Teks Portal Login & Footer</h3><p className="text-xs text-slate-500">Format terbatas, paste sebagai teks polos, dan sanitasi ulang di server.</p></div></div>
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div className="space-y-3"><label className="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-600"><span>Judul halaman login</span><span className="text-[10px] font-semibold normal-case text-indigo-600">Blok teks sebelum memformat</span></label><SafeRichTextEditor value={form.login_heading} onChange={(value) => updateField("login_heading", value)} placeholder="Selamat datang di portal..." ariaLabel="Judul halaman login" sizeOptions={["24px", "28px", "32px", "36px", "40px", "44px", "48px", "56px"]} defaultColor="#0055A5" minHeight="100px" /><FieldError errors={validationErrors} name="login_heading" /></div>
            <div className="space-y-3"><label className="text-xs font-bold uppercase tracking-wider text-slate-600">Template hak cipta</label><input value={form.footer_copyright} onChange={(e) => updateField("footer_copyright", e.target.value)} className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" /><p className="text-[10px] font-medium leading-relaxed text-slate-500">Token: <code>{"{year}"}</code>, <code>{"{app_name}"}</code>, <code>{"{instansi_name}"}</code>, <code>{"{instansi_full_name}"}</code>.</p><div className="rounded-xl border border-slate-200 bg-slate-50 p-4"><p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Hasil footer saat ini</p><p className="text-sm font-medium text-slate-700">{footerPreview}</p></div><FieldError errors={validationErrors} name="footer_copyright" /></div>
          </div>
          <div className="space-y-3"><label className="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-600"><span>Deskripsi halaman login</span><span className="text-[10px] font-semibold normal-case text-indigo-600">HTML dibatasi dan disanitasi</span></label><SafeRichTextEditor value={form.login_description} onChange={(value) => updateField("login_description", value)} placeholder="Deskripsi singkat portal..." ariaLabel="Deskripsi halaman login" sizeOptions={["12px", "13px", "14px", "15px", "16px", "17px", "18px", "20px"]} defaultColor="#334155" minHeight="110px" /><FieldError errors={validationErrors} name="login_description" /></div>
        </section>

        <section className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
          <div className="flex items-center gap-3 border-b border-slate-100 pb-4"><div className="flex size-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><Globe size={18} /></div><div><h3 className="text-sm font-extrabold text-slate-800">Kontak Login</h3><p className="text-xs text-slate-500">Informasi alamat, nomor telepon, dan email instansi.</p></div></div>
          <div><label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-600">Alamat</label><textarea value={form.contact_address || ""} onChange={(e) => updateField("contact_address", e.target.value)} rows={3} placeholder="Contoh: Jl. Prof. Abdurrahman Basalamah II..." className="w-full resize-y rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" /><FieldError errors={validationErrors} name="contact_address" /></div>
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div><label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-600">No. Telepon</label><input type="text" value={form.contact_phone || ""} onChange={(e) => updateField("contact_phone", e.target.value)} placeholder="Contoh: 0851-1729-7705" className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" /><FieldError errors={validationErrors} name="contact_phone" /></div>
            <div><label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-600">Email</label><input type="email" value={form.contact_email || ""} onChange={(e) => updateField("contact_email", e.target.value)} placeholder="Contoh: bblsdm.makassar@komdigi.go.id" className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" /><FieldError errors={validationErrors} name="contact_email" /></div>
          </div>
        </section>

        <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
          <div className="flex items-center gap-3 border-b border-slate-100 p-5"><div className="flex size-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"><Eye size={18} /></div><div><h3 className="text-sm font-extrabold text-slate-800">Pratinjau Identitas</h3><p className="text-xs text-slate-500">Simulasi navbar, halaman login, dan footer sebelum disimpan.</p></div></div>
          <div className="bg-slate-50 p-4 sm:p-6">
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
              <div className="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-5 py-4"><div className="flex items-center gap-3"><img src={appLogoPreview} alt="Logo aplikasi" className="size-12 object-contain" /><div><div className="text-xl font-extrabold text-indigo-800">{(() => {
                let colors: Record<number, string> = {};
                try { if (form.app_name_colors) colors = JSON.parse(form.app_name_colors); } catch (e) {}
                return form.app_name.split("").map((char, i) => <span key={i} style={{ color: colors[i] || undefined }}>{char}</span>);
              })()}</div><div className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{form.app_subtitle}</div></div></div><div className="flex items-center gap-3"><img src={institutionLogoPreview} alt="Logo instansi" className="size-10 object-contain" /><div><div className="text-sm font-extrabold text-slate-700">{(() => {
                let colors: Record<number, string> = {};
                try { if (form.instansi_name_colors) colors = JSON.parse(form.instansi_name_colors); } catch (e) {}
                return form.instansi_name.split("").map((char, i) => <span key={i} style={{ color: colors[i] || undefined }}>{char}</span>);
              })()}</div><div className="max-w-xs text-[10px] font-medium text-slate-500">{form.instansi_sub}</div></div></div></div>
              <div className="grid min-h-64 place-items-center bg-gradient-to-br from-slate-50 to-indigo-50 p-8 text-center"><div className="max-w-2xl"><div className="text-2xl font-extrabold leading-tight text-slate-900" dangerouslySetInnerHTML={{ __html: sanitizeHtml(form.login_heading) }} /><div className="mt-4 text-sm leading-relaxed text-slate-600" dangerouslySetInnerHTML={{ __html: sanitizeHtml(form.login_description) }} /></div></div>
              <div className="border-t border-slate-200 px-5 py-4 text-center text-xs font-medium text-slate-500">{footerPreview}</div>
            </div>
          </div>
        </section>

        <div className="flex flex-col-reverse justify-end gap-3 sm:flex-row">
          <button type="button" disabled={saving} onClick={() => void submit("draft")} className="inline-flex items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-white px-6 py-3 text-xs font-extrabold text-indigo-700 shadow-sm hover:bg-indigo-50 disabled:opacity-50">{saving ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />} Simpan sebagai Draft</button>
          <button type="submit" disabled={saving} className="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-xs font-extrabold text-white shadow-md hover:bg-indigo-700 disabled:opacity-50">{saving ? <Loader2 size={16} className="animate-spin" /> : effectiveFrom ? <CalendarClock size={16} /> : <Rocket size={16} />}{saving ? "Memproses..." : effectiveFrom ? "Simpan & Jadwalkan" : "Publikasikan Sekarang"}</button>
        </div>
      </form>

      <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
        <div className="mb-5 flex items-center justify-between gap-4 border-b border-slate-100 pb-4"><div className="flex items-center gap-3"><div className="flex size-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700"><History size={19} /></div><div><h3 className="text-sm font-extrabold text-slate-800">Riwayat Versi Identitas</h3><p className="text-xs text-slate-500">Versi aktif tidak ditimpa; rollback selalu membuat publikasi baru.</p></div></div><button type="button" onClick={() => void loadVersions()} className="text-xs font-bold text-indigo-600 hover:text-indigo-800">Muat ulang</button></div>
        {versionsLoading ? <div className="flex items-center justify-center gap-2 py-10 text-sm font-semibold text-slate-500"><Loader2 size={18} className="animate-spin" /> Memuat riwayat...</div> : versions.length === 0 ? <div className="py-10 text-center text-sm font-medium text-slate-500">Belum ada versi branding.</div> : <div className="space-y-3">{versions.map((version) => {
          const busy = rowActionId === version.id;
          return <article key={version.id} className={`rounded-xl border p-4 ${editingVersionId === version.id ? "border-indigo-300 bg-indigo-50/40" : "border-slate-200 bg-white"}`}><div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center"><div className="min-w-0"><div className="flex flex-wrap items-center gap-2"><h4 className="truncate text-sm font-extrabold text-slate-800">{version.label}</h4><span className={`rounded-full border px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider ${statusClasses[version.status]}`}>{statusLabels[version.status]}</span></div><div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11px] font-medium text-slate-500"><span>Dibuat: {formatDateTime(version.created_at)}</span><span>Oleh: {version.creator?.name || "Sistem"}</span>{version.effective_from && <span>Mulai: {formatDateTime(version.effective_from)}</span>}{version.published_at && <span>Publikasi: {formatDateTime(version.published_at)}</span>}</div>{version.notes && <p className="mt-2 text-xs text-slate-600">{version.notes}</p>}</div><div className="flex flex-wrap items-center gap-2">{["draft", "scheduled"].includes(version.status) && <button type="button" disabled={busy} onClick={() => editVersion(version)} className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 hover:border-indigo-300"><Pencil size={13} /> Edit</button>}{["draft", "scheduled"].includes(version.status) && <button type="button" disabled={busy} onClick={() => void performVersionAction(version, "publish")} className="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-bold text-emerald-700 hover:bg-emerald-100">{busy ? <Loader2 size={13} className="animate-spin" /> : <Rocket size={13} />} {version.status === "scheduled" ? "Publikasikan Sekarang" : "Publikasikan"}</button>}{version.status === "archived" && <button type="button" disabled={busy} onClick={() => void performVersionAction(version, "rollback")} className="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-[11px] font-bold text-indigo-700 hover:bg-indigo-100"><RotateCcw size={13} /> Rollback</button>}{["draft", "scheduled", "archived"].includes(version.status) && <button type="button" disabled={busy} onClick={() => void performVersionAction(version, "delete")} className="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-[11px] font-bold text-rose-700 hover:bg-rose-100"><Trash2 size={13} /> Hapus</button>}</div></div></article>;
        })}</div>}
        <div className="mt-5 flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50/60 p-4 text-xs text-blue-800"><FileClock size={17} className="mt-0.5 shrink-0" /><p><strong>Operasional tahunan:</strong> buat draft baru, unggah logo dan favicon, cek pratinjau, tentukan tanggal berlaku, lalu jadwalkan. Versi lama tetap tersedia sebagai arsip.</p></div>
      </section>

      {confirmDialog && (
        <ConfirmDialog
          open={confirmDialog.open}
          onClose={() => setConfirmDialog(null)}
          onConfirm={confirmDialog.onConfirm}
          title={confirmDialog.title}
          message={confirmDialog.message}
          variant={confirmDialog.variant}
          confirmText={confirmDialog.confirmText}
        />
      )}
    </div>
  );
};
