import React from "react";
import { ConfirmDialog } from "./ConfirmDialog";

interface AlertDialogProps {
  open: boolean;
  onClose: () => void;
  title: string;
  message: string | React.ReactNode;
  variant?: "danger" | "warning" | "info" | "success";
  confirmText?: string;
}

export function AlertDialog({
  open,
  onClose,
  title,
  message,
  variant = "info",
  confirmText = "OK",
}: AlertDialogProps) {
  return (
    <ConfirmDialog
      open={open}
      onClose={onClose}
      onConfirm={onClose}
      title={title}
      message={message}
      variant={variant}
      confirmText={confirmText}
      showCancel={false}
    />
  );
}
