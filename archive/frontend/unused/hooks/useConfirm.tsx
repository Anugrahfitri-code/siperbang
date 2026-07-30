import { useState, useCallback, useRef } from "react";
import { ConfirmDialog } from "./ConfirmDialog";

type Variant = "danger" | "warning" | "info" | "success";

interface ConfirmOptions {
  title: string;
  message: string;
  variant?: Variant;
  confirmText?: string;
  cancelText?: string;
}

interface DialogData extends ConfirmOptions {
  id: number;
}

let nextId = 0;

export function useConfirm() {
  const [dialog, setDialog] = useState<DialogData | null>(null);
  const [loading, setLoading] = useState(false);
  const resolveRef = useRef<((value: boolean) => void) | null>(null);

  const confirm = useCallback((opts: ConfirmOptions): Promise<boolean> => {
    return new Promise<boolean>((resolve) => {
      resolveRef.current = resolve;
      setDialog({ ...opts, id: ++nextId });
    });
  }, []);

  const handleConfirm = useCallback(async () => {
    setLoading(true);
    resolveRef.current?.(true);
    setDialog(null);
    resolveRef.current = null;
    setLoading(false);
  }, []);

  const handleCancel = useCallback(() => {
    resolveRef.current?.(false);
    setDialog(null);
    resolveRef.current = null;
  }, []);

  const el = dialog ? (
    <ConfirmDialog
      key={dialog.id}
      open
      title={dialog.title}
      message={dialog.message}
      variant={dialog.variant ?? (dialog.cancelText === undefined && !dialog.confirmText ? "info" : "warning")}
      confirmText={dialog.confirmText ?? "Konfirmasi"}
      cancelText={dialog.cancelText ?? (dialog.variant === "info" ? undefined : "Batal")}
      showCancel={dialog.cancelText !== undefined || dialog.variant !== "info"}
      loading={loading}
      onConfirm={handleConfirm}
      onClose={handleCancel}
    />
  ) : null;

  return { confirm, dialog: el };
}
