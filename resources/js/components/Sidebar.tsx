import React, { type ReactNode } from "react";
import {
  CheckSquare,
  ClipboardList,
  FileSpreadsheet,
  History,
  Home,
  LayoutDashboard,
  Package,
  Receipt,
  ShieldCheck,
  Users,
  X,
} from "lucide-react";

import {
  ItemRequest,
  ReceiptData,
  RequestStatus,
  UserRole,
} from "../types";

interface SidebarProps {
  isOpen: boolean;
  onClose: () => void;
  currentRole: UserRole;

  officerTab: string;
  setOfficerTab: (
    tab:
      | "dashboard"
      | "checking"
      | "stock"
      | "ocr"
      | "report"
      | "history"
  ) => void;

  requesterTab: string;
  setRequesterTab: (
    tab:
      | "dashboard"
      | "bon"
      | "monitoring"
      | "history"
      | "stock"
  ) => void;

  superadminTab: string;
  setSuperadminTab: (
    tab:
      | "users"
      | "dashboard"
      | "checking"
      | "stock_manage"
      | "ocr"
      | "report"
      | "bon"
      | "monitoring"
      | "stock_catalog"
      | "history"
  ) => void;

  requests: ItemRequest[];
  receipts: ReceiptData[];
}

type SidebarColor = "indigo" | "emerald" | "amber";

interface SidebarItemProps {
  active: boolean;
  color: SidebarColor;
  icon: ReactNode;
  label: string;
  onClick: () => void;
  badge?: number;
  badgeTone?: "amber" | "rose";
}

const activeClass: Record<SidebarColor, string> = {
  indigo: "bg-blue-50 text-blue-700 border-blue-600 border-l-4",
  emerald: "bg-emerald-50 text-emerald-700 border-emerald-600 border-l-4",
  amber: "bg-amber-50 text-amber-700 border-amber-600 border-l-4",
};

const hoverClass: Record<SidebarColor, string> = {
  indigo: "hover:bg-blue-50/70 hover:text-blue-700",
  emerald: "hover:bg-emerald-50/70 hover:text-emerald-700",
  amber: "hover:bg-amber-50/70 hover:text-amber-700",
};

const activeBadgeClass: Record<SidebarColor, string> = {
  indigo: "bg-blue-200 text-blue-800",
  emerald: "bg-emerald-200 text-emerald-800",
  amber: "bg-amber-200 text-amber-800",
};

function SidebarItem({
  active,
  color,
  icon,
  label,
  onClick,
  badge,
  badgeTone = "amber",
}: SidebarItemProps) {
  const inactiveBadgeClass =
    badgeTone === "rose"
      ? "border border-rose-200 bg-rose-50 text-rose-600"
      : "border border-amber-200 bg-amber-50 text-amber-700";

  return (
    <button
      type="button"
      onClick={onClick}
      className={`group flex w-full items-center justify-between py-3 pl-6 pr-5 text-left text-sm font-bold transition-all duration-200 ${
        active
          ? activeClass[color]
          : `border-transparent border-l-4 bg-transparent text-slate-500 ${hoverClass[color]}`
      }`}
    >
      <span className="flex min-w-0 items-center gap-3">
        <span className="shrink-0">{icon}</span>

        <span className="truncate">
          {label}
        </span>
      </span>

      {typeof badge === "number" && badge > 0 && (
        <span
          className={`ml-2 min-w-6 shrink-0 rounded-full px-2 py-0.5 text-center text-xs font-extrabold ${
            active
              ? activeBadgeClass[color]
              : inactiveBadgeClass
          }`}
        >
          {badge}
        </span>
      )}
    </button>
  );
}

function SectionTitle({
  children,
}: {
  children: ReactNode;
}) {
  return (
    <p className="px-7 pb-2 pt-5 text-xs font-extrabold uppercase tracking-[0.14em] text-slate-400">
      {children}
    </p>
  );
}

export function Sidebar({
  isOpen,
  onClose,
  currentRole,

  officerTab,
  setOfficerTab,

  requesterTab,
  setRequesterTab,

  superadminTab,
  setSuperadminTab,

  requests,
  receipts,
}: SidebarProps) {
  const incomingRequestCount = requests.filter(
    (request) =>
      request.status === RequestStatus.DIAJUKAN
  ).length;

  const unverifiedReceiptCount = receipts.filter(
    (receipt) => !receipt.isVerified
  ).length;

  const roleLabel =
    currentRole === UserRole.SUPERADMIN
      ? "Superadmin"
      : currentRole === UserRole.PETUGAS_PERSERDIAN
        ? "Petugas Persediaan"
        : "Ketua Tim Kerja";

  const handleOfficerTab = (
    tab:
      | "dashboard"
      | "checking"
      | "stock"
      | "ocr"
      | "report"
      | "history"
  ) => {
    setOfficerTab(tab);
    if (typeof window !== "undefined" && window.innerWidth < 1024) {
      onClose();
    }
  };

  const handleRequesterTab = (
    tab:
      | "dashboard"
      | "bon"
      | "monitoring"
      | "history"
      | "stock"
  ) => {
    setRequesterTab(tab);
    if (typeof window !== "undefined" && window.innerWidth < 1024) {
      onClose();
    }
  };

  const handleSuperadminTab = (
    tab:
      | "users"
      | "dashboard"
      | "checking"
      | "stock_manage"
      | "ocr"
      | "report"
      | "bon"
      | "monitoring"
      | "stock_catalog"
      | "history"
  ) => {
    setSuperadminTab(tab);
    if (typeof window !== "undefined" && window.innerWidth < 1024) {
      onClose();
    }
  };

  return (
    <>
      {/* Overlay hanya untuk tablet dan HP */}
      {isOpen && (
        <button
          type="button"
          aria-label="Tutup menu"
          onClick={onClose}
          className="fixed inset-x-0 bottom-0 top-16 z-30 bg-slate-900/50 backdrop-blur-sm lg:hidden"
        />
      )}

      {/* Sidebar dimulai di bawah navbar */}
      <aside
        className={`fixed bottom-0 left-0 top-16 z-40 flex w-72 flex-col border-r border-slate-200 bg-white shadow-2xl transition-transform duration-300 ease-in-out lg:shadow-none ${
          isOpen ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        {/* Header ini hanya ditampilkan pada layar kecil */}
        <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4 lg:hidden">
          <h2 className="text-sm font-extrabold uppercase tracking-wider text-slate-800">
            Menu Utama
          </h2>

          <button
            type="button"
            onClick={onClose}
            aria-label="Tutup sidebar"
            className="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
          >
            <X size={18} />
          </button>
        </div>

        {/* Isi menu sidebar */}
        <nav className="flex-1 space-y-1 overflow-y-auto pt-5 pb-5">
          {currentRole === UserRole.SUPERADMIN ? (
            <>
              <SectionTitle>
                Manajemen Sistem
              </SectionTitle>

              <SidebarItem
                active={superadminTab === "users"}
                color="emerald"
                icon={
                  <Users
                    size={19}
                    strokeWidth={1.9}
                  />
                }
                label="Kelola Pengguna"
                onClick={() =>
                  handleSuperadminTab("users")
                }
              />

              <SectionTitle>
                Petugas Persediaan
              </SectionTitle>

              <SidebarItem
                active={
                  superadminTab === "dashboard"
                }
                color="emerald"
                icon={
                  <Home
                    size={19}
                    strokeWidth={1.9}
                  />
                }
                label="Daftar Tindakan"
                badge={incomingRequestCount}
                onClick={() =>
                  handleSuperadminTab("dashboard")
                }
              />

              <SidebarItem
                active={
                  superadminTab === "checking"
                }
                color="emerald"
                icon={
                  <Package
                    size={19}
                    strokeWidth={1.9}
                  />
                }
                label="Pengecekan & Pemenuhan"
                onClick={() =>
                  handleSuperadminTab("checking")
                }
              />

              <SidebarItem
                active={
                  superadminTab === "stock_manage"
                }
                color="emerald"
                icon={
                  <FileSpreadsheet
                    size={19}
                    strokeWidth={1.9}
                  />
                }
                label="Excel & Kode Persediaan"
                onClick={() =>
                  handleSuperadminTab(
                    "stock_manage"
                  )
                }
              />

              <SidebarItem
                active={superadminTab === "ocr"}
                color="emerald"
                icon={
                  <Receipt
                    size={19}
                    strokeWidth={1.9}
                  />
                }
                label="OCR Kuitansi & Pajak"
                badge={unverifiedReceiptCount}
                badgeTone="rose"
                onClick={() =>
                  handleSuperadminTab("ocr")
                }
              />

              <SidebarItem
                active={
                  superadminTab === "report"
                }
                color="emerald"
                icon={
                  <FileSpreadsheet
                    size={19}
                    strokeWidth={1.9}
                  />
                }
                label="Rekap Laporan Excel"
                onClick={() =>
                  handleSuperadminTab("report")
                }
              />

              <SectionTitle>
                Ketua Tim Kerja
              </SectionTitle>

              <SidebarItem
                active={superadminTab === "bon"}
                color="emerald"
                icon={
                  <ClipboardList
                    size={19}
                    strokeWidth={1.9}
                  />
                }
                label="BON Digital / Ajukan Baru"
                onClick={() =>
                  handleSuperadminTab("bon")
                }
              />

              <SidebarItem
                active={
                  superadminTab === "monitoring"
                }
                color="emerald"
                icon={
                  <CheckSquare
                    size={19}
                    strokeWidth={1.9}
                  />
                }
                label="Pantau Pengajuan"
                onClick={() =>
                  handleSuperadminTab(
                    "monitoring"
                  )
                }
              />

              <SidebarItem
                active={
                  superadminTab ===
                  "stock_catalog"
                }
                color="emerald"
                icon={
                  <Package
                    size={19}
                    strokeWidth={1.9}
                  />
                }
                label="Katalog Stok Gudang"
                onClick={() =>
                  handleSuperadminTab(
                    "stock_catalog"
                  )
                }
              />

              <SectionTitle>
                Laporan & Audit
              </SectionTitle>

              <SidebarItem
                active={
                  superadminTab === "history"
                }
                color="emerald"
                icon={
                  <History
                    size={19}
                    strokeWidth={1.9}
                  />
                }
                label="Audit Log Sistem"
                onClick={() =>
                  handleSuperadminTab("history")
                }
              />
            </>
          ) : currentRole ===
            UserRole.PETUGAS_PERSERDIAN ? (
            <>
              {/* Ikon Daftar Tindakan menggunakan ikon rumah */}
              <SidebarItem
                active={
                  officerTab === "dashboard"
                }
                color="indigo"
                icon={
                  <Home
                    size={20}
                    strokeWidth={1.9}
                  />
                }
                label="Daftar Tindakan"
                badge={incomingRequestCount}
                onClick={() =>
                  handleOfficerTab("dashboard")
                }
              />

              <SidebarItem
                active={
                  officerTab === "checking"
                }
                color="indigo"
                icon={
                  <Package
                    size={20}
                    strokeWidth={1.9}
                  />
                }
                label="Pengecekan & Pemenuhan"
                onClick={() =>
                  handleOfficerTab("checking")
                }
              />

              <SidebarItem
                active={officerTab === "stock"}
                color="indigo"
                icon={
                  <FileSpreadsheet
                    size={20}
                    strokeWidth={1.9}
                  />
                }
                label="Excel & Kode Persediaan"
                onClick={() =>
                  handleOfficerTab("stock")
                }
              />

              <SidebarItem
                active={officerTab === "ocr"}
                color="indigo"
                icon={
                  <Receipt
                    size={20}
                    strokeWidth={1.9}
                  />
                }
                label="OCR Kuitansi & Pajak"
                badge={unverifiedReceiptCount}
                badgeTone="rose"
                onClick={() =>
                  handleOfficerTab("ocr")
                }
              />

              <SidebarItem
                active={officerTab === "report"}
                color="indigo"
                icon={
                  <FileSpreadsheet
                    size={20}
                    strokeWidth={1.9}
                  />
                }
                label="Rekap Laporan Excel"
                onClick={() =>
                  handleOfficerTab("report")
                }
              />

              <SidebarItem
                active={
                  officerTab === "history"
                }
                color="indigo"
                icon={
                  <History
                    size={20}
                    strokeWidth={1.9}
                  />
                }
                label="Histori & Audit Log"
                onClick={() =>
                  handleOfficerTab("history")
                }
              />
            </>
          ) : (
            <>
              <SidebarItem
                active={
                  requesterTab === "dashboard"
                }
                color="amber"
                icon={
                  <LayoutDashboard
                    size={20}
                    strokeWidth={1.9}
                  />
                }
                label="Dashboard Ketua Tim"
                onClick={() =>
                  handleRequesterTab("dashboard")
                }
              />

              <SidebarItem
                active={requesterTab === "bon"}
                color="amber"
                icon={
                  <ClipboardList
                    size={20}
                    strokeWidth={1.9}
                  />
                }
                label="BON Digital / Ajukan Baru"
                onClick={() =>
                  handleRequesterTab("bon")
                }
              />

              <SidebarItem
                active={
                  requesterTab === "monitoring"
                }
                color="amber"
                icon={
                  <CheckSquare
                    size={20}
                    strokeWidth={1.9}
                  />
                }
                label="Pantau Pengajuan Saya"
                onClick={() =>
                  handleRequesterTab("monitoring")
                }
              />

              <SidebarItem
                active={
                  requesterTab === "stock"
                }
                color="amber"
                icon={
                  <Package
                    size={20}
                    strokeWidth={1.9}
                  />
                }
                label="Katalog Stok Gudang"
                onClick={() =>
                  handleRequesterTab("stock")
                }
              />

              <SidebarItem
                active={
                  requesterTab === "history"
                }
                color="amber"
                icon={
                  <History
                    size={20}
                    strokeWidth={1.9}
                  />
                }
                label="Histori Pengajuan"
                onClick={() =>
                  handleRequesterTab("history")
                }
              />
            </>
          )}
        </nav>

        {/* Footer sidebar */}
        <div className="mt-auto border-t border-slate-200 px-5 py-5">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600">
              <ShieldCheck
                size={20}
                strokeWidth={1.8}
              />
            </div>

            <div>
              <p className="text-xs font-extrabold text-slate-800">
                SIPERBANG
              </p>

              <p className="mt-0.5 text-xs font-semibold text-slate-400">
                v1.1.0
              </p>

              <p className="mt-1 text-xs font-semibold text-slate-400">
                © 2026 KOMDIGI
              </p>
            </div>
          </div>
        </div>
      </aside>
    </>
  );
}
