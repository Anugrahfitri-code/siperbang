import React, { useEffect, useState, useCallback } from "react";
import {
  AlertTriangle,
  Trash2,
  Info,
  CheckCircle,
  X,
  Loader2,
} from "lucide-react";

type Variant = "danger" | "warning" | "info" | "success";

interface ConfirmDialogProps {
  open: boolean;
  onClose: () => void;
  onConfirm: () => void | Promise<void>;
  title: string;
  message: string | React.ReactNode;
  confirmText?: string;
  cancelText?: string;
  variant?: Variant;
  loading?: boolean;
  showCancel?: boolean;
  children?: React.ReactNode;
}

const variantConfig: Record<Variant, {
  icon: React.ReactNode;
  bgCircle: string;
  iconColor: string;
  confirmBg: string;
  confirmHover: string;
}> = {
  danger: {
    icon: <Trash2 size={20} />,
    bgCircle: "bg-rose-100",
    iconColor: "text-rose-600",
    confirmBg: "bg-rose-600",
    confirmHover: "hover:bg-rose-700",
  },
  warning: {
    icon: <AlertTriangle size={20} />,
    bgCircle: "bg-amber-100",
    iconColor: "text-amber-600",
    confirmBg: "bg-amber-600",
    confirmHover: "hover:bg-amber-700",
  },
  info: {
    icon: <Info size={20} />,
    bgCircle: "bg-indigo-100",
    iconColor: "text-indigo-600",
    confirmBg: "bg-indigo-600",
    confirmHover: "hover:bg-indigo-700",
  },
  success: {
    icon: <CheckCircle size={20} />,
    bgCircle: "bg-emerald-100",
    iconColor: "text-emerald-600",
    confirmBg: "bg-emerald-600",
    confirmHover: "hover:bg-emerald-700",
  },
};

export function ConfirmDialog({
  open,
  onClose,
  onConfirm,
  title,
  message,
  confirmText = "Konfirmasi",
  cancelText = "Batal",
  variant = "info",
  loading = false,
  showCancel = true,
  children,
}: ConfirmDialogProps) {
  const [animating, setAnimating] = useState(false);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    if (open) {
      setVisible(true);
      requestAnimationFrame(() => setAnimating(true));
    } else {
      setAnimating(false);
      const timer = setTimeout(() => setVisible(false), 200);
      return () => clearTimeout(timer);
    }
  }, [open]);

  const handleConfirm = useCallback(async () => {
    await onConfirm();
  }, [onConfirm]);

  const handleBackdropClick = useCallback((e: React.MouseEvent) => {
    if (e.target === e.currentTarget && !loading) onClose();
  }, [onClose, loading]);

  const handleKeyDown = useCallback((e: React.KeyboardEvent) => {
    if (e.key === "Escape" && !loading) onClose();
  }, [onClose, loading]);

  if (!visible) return null;

  const cfg = variantConfig[variant];

  return (
    <div
      className={`fixed inset-0 z-50 flex items-center justify-center p-4 transition-all duration-200 ${
        animating ? "bg-slate-900/60 backdrop-blur-sm" : "bg-transparent backdrop-blur-0"
      }`}
      onClick={handleBackdropClick}
      onKeyDown={handleKeyDown}
      role="dialog"
      aria-modal="true"
    >
      <div
        className={`bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-0 overflow-hidden transition-all duration-200 ${
          animating
            ? "opacity-100 scale-100 translate-y-0"
            : "opacity-0 scale-95 translate-y-4"
        }`}
      >
        <div className="p-6">
          <div className="flex items-start gap-4">
            <div className={`${cfg.bgCircle} rounded-full p-2.5 shrink-0`}>
              <span className={cfg.iconColor}>{cfg.icon}</span>
            </div>
            <div className="flex-1 min-w-0">
              <div className="flex items-start justify-between gap-2">
                <h3 className="text-base font-extrabold text-slate-900 leading-snug">
                  {title}
                </h3>
                {!loading && (
                  <button
                    onClick={onClose}
                    className="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors -mr-1 -mt-1 shrink-0"
                  >
                    <X size={16} />
                  </button>
                )}
              </div>
              <div className="mt-2 text-sm text-slate-600 leading-relaxed">
                {typeof message === "string" ? <p>{message}</p> : message}
              </div>
              {children && <div className="mt-4">{children}</div>}
            </div>
          </div>
        </div>
        <div className="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
          {showCancel && (
            <button
              onClick={onClose}
              disabled={loading}
              className="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-white hover:border-slate-300 disabled:opacity-50 transition-all"
            >
              {cancelText}
            </button>
          )}
          <button
            onClick={handleConfirm}
            disabled={loading}
            className={`px-5 py-2.5 rounded-xl text-sm font-bold text-white shadow-sm transition-all ${cfg.confirmBg} ${cfg.confirmHover} disabled:opacity-50 flex items-center gap-2`}
          >
            {loading && <Loader2 size={15} className="animate-spin" />}
            {loading ? "Memproses..." : confirmText}
          </button>
        </div>
      </div>
    </div>
  );
}
