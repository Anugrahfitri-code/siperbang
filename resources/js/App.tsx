/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState, useEffect } from "react";
import { UserRole, UserAccount, ItemRequest, StockItem, ReceiptData, HistoryLog as LogType, RequestStatus, ProcurementMethod } from "./types";
import { Navbar } from "./components/Navbar";
import { DashboardStats } from "./components/DashboardStats";
import { BonDigitalForm } from "./components/BonDigitalForm";
import { StockManagement } from "./components/StockManagement";
import { StockChecking } from "./components/StockChecking";
import { ReceiptOCRProcessor } from "./components/ReceiptOCRProcessor";
import { ReportExport } from "./components/ReportExport";
import { HistoryLog } from "./components/HistoryLog";
import { LoginScreen } from "./components/LoginScreen";
import { Sidebar } from "./components/Sidebar";
import { RequesterStockList } from "./components/RequesterStockList";
import { UserManagement } from "./components/UserManagement";
import { LayoutDashboard, FileSpreadsheet, ClipboardList, Package, Receipt, History, AlertCircle, Info, ChevronRight, CheckSquare, Loader2 } from "lucide-react";
import { apiFetch } from "./api";

type AuthenticatedUser = {
  id: number | string;
  name: string;
  username: string;
  role: UserRole;
  section?: string | null;
};

const normalizeRequestStatus = (status: string): RequestStatus => {
  const statusMap: Record<string, RequestStatus> = {
    DIAJUKAN: RequestStatus.DIAJUKAN,
    DICEK: RequestStatus.DICEK,
    TERPENUHI: RequestStatus.TERPENUHI,

    TERPENUHI_SEBAGIAN: RequestStatus.TERPENUHI_SEBAGIAN,
    "TERPENUHI SEBAGIAN": RequestStatus.TERPENUHI_SEBAGIAN,

    PERLU_PENGADAAN: RequestStatus.PERLU_PENGADAAN,
    "PERLU PENGADAAN": RequestStatus.PERLU_PENGADAAN,

    DALAM_PENGADAAN: RequestStatus.DALAM_PENGADAAN,
    "DALAM PENGADAAN": RequestStatus.DALAM_PENGADAAN,

    DITOLAK: RequestStatus.DITOLAK,
    SELESAI: RequestStatus.SELESAI,
  };

  return statusMap[status.toUpperCase()] ?? (status as RequestStatus);
};

const toDateOnly = (
  value: string | null | undefined
): string => {
  return value ? value.substring(0, 10) : "";
};

export default function App() {
  // Roles & Authentication state
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [authChecked, setAuthChecked] = useState(false);
  const [currentRole, setCurrentRole] = useState<UserRole>(UserRole.PETUGAS_PERSERDIAN);
  const [currentUser, setCurrentUser] = useState("Iwan Setiawan (Petugas Persediaan)");

  // Active database states loaded from localStorage or fallback to defaults
  const [requests, setRequests] = useState<ItemRequest[]>([]);
  const [stock, setStock] = useState<StockItem[]>([]);
  const [receipts, setReceipts] = useState<ReceiptData[]>([]);
  const [logs, setLogs] = useState<LogType[]>([]);
  const [users, setUsers] = useState<UserAccount[]>([]);

  // Navigation tab states
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const [officerTab, setOfficerTab] = useState<"dashboard" | "checking" | "stock" | "ocr" | "report" | "history">(
    () => (localStorage.getItem("officerTab") as any) || "dashboard"
  );
  const [requesterTab, setRequesterTab] = useState<"bon" | "monitoring" | "history" | "stock">(
    () => (localStorage.getItem("requesterTab") as any) || "bon"
  );
  const [superadminTab, setSuperadminTab] = useState<"users" | "dashboard" | "checking" | "stock_manage" | "ocr" | "report" | "bon" | "monitoring" | "stock_catalog" | "history">(
    () => (localStorage.getItem("superadminTab") as any) || "users"
  );

  useEffect(() => {
    localStorage.setItem("officerTab", officerTab);
  }, [officerTab]);

  useEffect(() => {
    localStorage.setItem("requesterTab", requesterTab);
  }, [requesterTab]);

  useEffect(() => {
    localStorage.setItem("superadminTab", superadminTab);
  }, [superadminTab]);

  // Memulihkan sesi Laravel ketika browser di-refresh.
useEffect(() => {
  let cancelled = false;

  const restoreSession = async () => {
    try {
      const response = await apiFetch("/api/user");

      if (!response.ok) {
        if (response.status !== 401) {
          console.error(
            "Gagal memeriksa sesi login:",
            response.status
          );
        }

        return;
      }

      const user =
        (await response.json()) as AuthenticatedUser;

      if (!cancelled) {
        setCurrentRole(user.role);
        setCurrentUser(`${user.name} (${user.role})`);
        setIsLoggedIn(true);
      }
    } catch (error) {
      console.error("Gagal memulihkan sesi login:", error);
    } finally {
      if (!cancelled) {
        setAuthChecked(true);
      }
    }
  };

  restoreSession();

  return () => {
    cancelled = true;
  };
}, []);
  
  // Load initial databases
  useEffect(() => {
    // Fetch initial data from API
    const loadData = async () => {
      try {
        const [reqRes, stockRes, recRes, logRes, userRes] =
          await Promise.all([
            apiFetch("/api/requests"),
            apiFetch("/api/stocks"),
            apiFetch("/api/receipts"),
            apiFetch("/api/logs"),
            apiFetch("/api/users"),
          ]);
        
        if (reqRes.ok) {
          const reqs = await reqRes.json();
          setRequests(reqs.map((r: any) => ({
            id: String(r.id),
            bonNo: r.bon_no,
            section: r.section,
            itemName: r.item_name,
            qtyRequested: Number(r.qty_requested ?? 0),
            qtyAvailable: Number(r.qty_available ?? 0),
            qtyFulfilled: Number(r.qty_fulfilled ?? 0),
            qtyToProcure: Number(r.qty_to_procure ?? 0),
            stockAllocated: Boolean(r.stock_allocated),
            unit: r.unit,
            status: normalizeRequestStatus(r.status),
            notes: r.notes ?? "",
            date: toDateOnly(r.date),
            requester: r.requester,
            lastUpdated: toDateOnly(r.last_updated),
            stockItemId: r.stock_item_id
              ? String(r.stock_item_id)
              : undefined,
            procurementMethod: r.procurement_method ?? undefined,
            vendorName: r.vendor_name ?? undefined,
            distribution: r.distribution ?? undefined,
            procurements: r.procurements ?? [],
          })));
        }

        if (stockRes.ok) {
          const stocks = await stockRes.json();
          setStock(stocks.map((s: any) => ({
            id: String(s.id),
            category: s.category,
            code: s.code,
            name: s.name,
            qty: s.qty,
            unit: s.unit,
            lastUpdated: s.last_updated
          })));
        }

        if (userRes.ok) {
          const fetchedUsers = await userRes.json();
          setUsers(fetchedUsers.map((u: any) => ({
            id: String(u.id),
            name: u.name,
            username: u.username,
            role: u.role,
            section: u.section,
            status: u.status
          })));
        }

        if (logRes.ok) {
          const fetchedLogs = await logRes.json();
          setLogs(fetchedLogs.map((l: any) => ({
            id: String(l.id),
            timestamp: l.created_at,
            actor: l.actor,
            action: l.action,
            details: l.details
          })));
        }

        if (recRes.ok) {
          const fetchedReceipts = await recRes.json();
          setReceipts(fetchedReceipts.map((r: any) => ({
            id: String(r.id),
            invoiceNo: r.invoice_no,
            storeName: r.store_name,
            date: r.date,
            isTaxed: r.is_taxed,
            taxRate: r.tax_rate,
            subtotal: r.subtotal,
            taxAmount: r.tax_amount,
            total: r.total,
            isVerified: r.is_verified,
            status: r.status,
            method: r.method,
            bastName: r.bast_name,
            bastDate: r.bast_date,
            items: r.items || []
          })));
        }
      } catch (err) {
        console.error("Error fetching data:", err);
      }
    };
    
    if (isLoggedIn) {
      loadData();
    }
  }, [isLoggedIn]);

  // local storage sync removed

  // Handle Switching Role
  const handleRoleChange = (role: UserRole) => {
    setCurrentRole(role);
    if (role === UserRole.SUPERADMIN) {
      setCurrentUser("Admin Utama (Superadmin)");
    } else if (role === UserRole.KETUA_TIM) {
      setCurrentUser("Budi Santoso (Ketua Tim TU)");
    } else {
      setCurrentUser("Iwan Setiawan (Petugas Persediaan)");
    }
  };

  // User Management Actions
  const handleAddUser = (newUser: Omit<UserAccount, "id">) => {
    const userWithId = {
      ...newUser,
      id: "u-" + Math.random().toString(36).substring(2, 9),
    };
    setUsers((prev) => [...prev, userWithId]);
    addLog(currentUser, "Tambah Pengguna", `Menambahkan akun baru: ${newUser.name} (${newUser.role})`);
  };

  const handleUpdateUser = (id: string, updates: Partial<UserAccount>) => {
    setUsers((prev) => prev.map((u) => (u.id === id ? { ...u, ...updates } : u)));
    addLog(currentUser, "Update Pengguna", `Memperbarui data akun ID: ${id}`);
  };

  const handleDeleteUser = (id: string) => {
    setUsers((prev) => prev.filter((u) => u.id !== id));
    addLog(currentUser, "Hapus Pengguna", `Menghapus akun ID: ${id}`);
  };

  // Log activity helper
  const addLog = (actor: string, action: string, details: string) => {
    const newLog: LogType = {
      id: "log-" + Math.random().toString(36).substring(2, 9),
      timestamp: new Date().toISOString().replace("T", " ").substring(0, 19),
      actor,
      action,
      details,
    };
    setLogs((prev) => [newLog, ...prev]);
  };

  // --- ACTIONS ---

  // 1. Submit a new BON Digital Request
  const handleAddRequest = async (
    newReq: Omit<
      ItemRequest,
      | "id"
      | "bonNo"
      | "status"
      | "qtyAvailable"
      | "qtyFulfilled"
      | "lastUpdated"
    >
  ): Promise<void> => {
    const response = await apiFetch("/api/requests", {
      method: "POST",
      body: JSON.stringify(newReq),
    });
    const data: any = await response
      .json()
      .catch(() => ({}));
    if (!response.ok) {
      if (response.status === 401) {
        setIsLoggedIn(false);

        throw new Error(
          "Sesi login berakhir. Silakan masuk kembali."
        );
      }
      const validationMessage =
        data.errors &&
        typeof data.errors === "object"
          ? Object.values(
              data.errors as Record<string, string[]>
            )
              .flat()
              .join(" ")
          : "";
      throw new Error(
        validationMessage ||
          data.message ||
          "BON gagal disimpan ke database."
      );
    }

    const savedRequest: ItemRequest = {
      id: String(data.id),
      bonNo: data.bon_no,
      section: data.section,
      itemName: data.item_name,
      qtyRequested: Number(data.qty_requested),
      qtyAvailable: Number(data.qty_available ?? 0),
      qtyFulfilled: Number(data.qty_fulfilled ?? 0),
      unit: data.unit,
      status: normalizeRequestStatus(data.status),
      notes: data.notes ?? "",
      date: toDateOnly(data.date),
      requester: data.requester,
      lastUpdated: toDateOnly(data.last_updated),
    };
    setRequests((previousRequests) => [
      savedRequest,
      ...previousRequests,
    ]);
    addLog(
      currentUser,
      "Ajukan Kebutuhan",
      `Mengajukan kebutuhan barang baru: ${newReq.itemName} sebanyak ${newReq.qtyRequested} ${newReq.unit} untuk seksi ${newReq.section}.`
    );
  };

  // 2. Upload New Stock (Excel) & Add to DB
  const handleUploadStock = (newStock: StockItem[]) => {
    setStock((prev) => {
      const merged = [...prev];
      newStock.forEach((ns) => {
        const existingIdx = merged.findIndex((s) => s.code === ns.code);
        if (existingIdx > -1) {
          merged[existingIdx].qty += ns.qty;
          merged[existingIdx].lastUpdated = ns.lastUpdated;
        } else {
          merged.push(ns);
        }
      });
      return merged;
    });

    addLog(
      currentUser,
      "Upload Stok Excel",
      `Mengunggah stok baru dari file Excel. Berhasil memverifikasi & menyimpan ${newStock.length} barang ke dalam database.`
    );
  };

  // 3. Process Stock Check & Allocate Quantities
  const handleUpdateStatus = async (
    reqId: string,
    status: RequestStatus,
    qtyAvailable: number,
    qtyFulfilled: number,
    logMessage: string,
    deductStock?: { code: string; qtyToDeduct: number }
  ) => {
    try {
      const payload = {
        status,
        qtyAvailable,
        qtyFulfilled,
        deductStock: deductStock ? {
          code: deductStock.code,
          qtyToDeduct: deductStock.qtyToDeduct
        } : null
      };

      const response = await apiFetch(`/api/requests/${reqId}/status`, {
        method: "PUT",
        body: JSON.stringify(payload)
      });
      
      if (!response.ok) {
        const error = await response.json().catch(() => ({}));
        throw new Error(error.message || "Gagal mengupdate status pengajuan");
      }
      
      const resData = await response.json();
      const updatedReq = resData.data;

      setRequests((prev) =>
        prev.map((req) => {
          if (req.id === reqId) {
            return {
              ...req,
              status,
              qtyAvailable,
              qtyFulfilled,
              qtyToProcure: updatedReq.qty_to_procure ?? 0,
              stockAllocated: Boolean(updatedReq.stock_allocated),
              lastUpdated: new Date().toISOString().split("T")[0],
            };
          }
          return req;
        })
      );

      // Deduct stock if allocated from warehouse
      if (deductStock) {
        setStock((prev) =>
          prev.map((s) => {
            if (s.code === deductStock.code) {
              return {
                ...s,
                qty: Math.max(0, s.qty - deductStock.qtyToDeduct),
                lastUpdated: new Date().toISOString().split("T")[0],
              };
            }
            return s;
          })
        );
      }

      addLog(currentUser, "Verifikasi Stok", logMessage);
    } catch (err: any) {
      console.error(err);
      window.alert(err.message || "Terjadi kesalahan saat memverifikasi stok");
    }
  };

  // 4. Manual OCR Verified Invoice Saver
  const handleVerifyReceipt = (id: string, verifiedReceipt: ReceiptData, logMsg: string) => {
    setReceipts((prev) => {
      // Check if it already exists as draft, or add as new verified
      const exists = prev.some((r) => r.id === id);
      if (exists) {
        return prev.map((r) => (r.id === id ? verifiedReceipt : r));
      } else {
        return [verifiedReceipt, ...prev];
      }
    });

    // Check if we have an unfulfilled request matching this store name / item name to progress status to SELESAI
    // (Simulate completion of procurements!)
    setRequests((prev) =>
      prev.map((req) => {
        // If request is in "Perlu Pengadaan" or "Terpenuhi Sebagian" and we bought the item
        const matchFound = verifiedReceipt.items.some(
          (vi) => vi.name.toLowerCase().includes(req.itemName.toLowerCase())
        );
        if (
          matchFound &&
          (req.status === RequestStatus.PERLU_PENGADAAN ||
            req.status === RequestStatus.TERPENUHI_SEBAGIAN ||
            req.status === RequestStatus.DALAM_PENGADAAN)
        ) {
          // Complete it
          return {
            ...req,
            status: RequestStatus.SELESAI,
            qtyFulfilled: req.qtyRequested,
            lastUpdated: new Date().toISOString().split("T")[0],
          };
        }
        return req;
      })
    );

    addLog(currentUser, "Verifikasi Kuitansi", logMsg);
  };

  const handleAddReceipt = (newReceipt: ReceiptData) => {
    setReceipts((prev) => [newReceipt, ...prev]);
    addLog(currentUser, "Tambah Kuitansi", `Menambahkan kuitansi manual/baru dari ${newReceipt.storeName} senilai ${formatIDR(newReceipt.total)}.`);
  };

  // 5. Handle Distribution
  const handleDistribute = async (
    reqId: string,
    data: {
      stockItemId: string;
      qtyDistributed: number;
      distributedBy: string;
      notes?: string;
    }
  ) => {
    try {
      const response = await apiFetch(
        `/api/requests/${reqId}/distribute`,
        {
          method: "POST",
          body: JSON.stringify(data),
        }
      );

      if (response.ok) {
        const updated = await response.json();

        setRequests((prev) =>
          prev.map((req) =>
            req.id === reqId ? updated : req
          )
        );

        addLog(
          currentUser,
          "Distribusi Barang",
          `BON distribusi: ${data.qtyDistributed} unit oleh ${data.distributedBy}`
        );
      } else {
        const error = await response
          .json()
          .catch(() => ({}));

        window.alert(
          error.message ||
            "Gagal melakukan distribusi"
        );
      }
    } catch (error) {
      console.error("Distribution error:", error);

      window.alert(
        "Terjadi kesalahan saat melakukan distribusi"
      );
    }
  };

  // 6. Handle Procurement
  const handleProcure = async (
    reqId: string,
    data: {
      method:
        | "Pengadaan Vendor"
        | "Pengadaan Sendiri (Toko)";
      vendorName?: string;
      storeName?: string;
      qtyProcured: number;
      unitPrice: number;
      isTaxed: boolean;
      taxRate: number;
      invoiceNo?: string;
      bastName?: string;
      bastDate?: string;
      contractNo?: string;
      processedBy: string;
    }
  ) => {
    try {
      const response = await apiFetch(
        `/api/requests/${reqId}/procure`,
        {
          method: "POST",
          body: JSON.stringify(data),
        }
      );

      if (response.ok) {
        const updated = await response.json();

        setRequests((prev) =>
          prev.map((req) =>
            req.id === reqId ? updated : req
          )
        );

        addLog(
          currentUser,
          "Pengadaan Barang",
          `BON pengadaan: ${data.qtyProcured} unit via ${data.method}`
        );
      } else {
        const error = await response
          .json()
          .catch(() => ({}));

        window.alert(
          error.message ||
            "Gagal membuat pengadaan"
        );
      }
    } catch (error) {
      console.error("Procurement error:", error);

      window.alert(
        "Terjadi kesalahan saat membuat pengadaan"
      );
    }
  };

  // 7. Handle Complete Procurement
  const handleCompleteProcurement = async (
    reqId: string,
    procurementId: string,
    processedBy: string
  ) => {
    try {
      const response = await apiFetch(
        `/api/requests/${reqId}/complete-procurement`,
        {
          method: "POST",
          body: JSON.stringify({
            procurementId,
            processedBy,
          }),
        }
      );

      if (response.ok) {
        const updated = await response.json();

        setRequests((prev) =>
          prev.map((req) =>
            req.id === reqId ? updated : req
          )
        );

        addLog(
          currentUser,
          "Terima Pengadaan",
          `BON pengadaan #${procurementId} diterima`
        );
      } else {
        const error = await response
          .json()
          .catch(() => ({}));

        window.alert(
          error.message ||
            "Gagal menyelesaikan pengadaan"
        );
      }
    } catch (error) {
      console.error(
        "Complete procurement error:",
        error
      );

      window.alert(
        "Terjadi kesalahan saat menyelesaikan pengadaan"
      );
    }
  };

  const handleLogout = async () => {
    try {
      const response = await apiFetch("/api/logout", {
        method: "POST",
      });

      if (!response.ok) {
        throw new Error(
          "Logout gagal diproses oleh server."
        );
      }

      window.location.assign("/");
    } catch (error) {
      console.error(error);

      window.alert(
        "Logout gagal. Silakan coba kembali."
      );
    }
  };

  const formatIDR = (num: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(num);
  };

  if (!authChecked) {
    return (
      <div className="min-h-screen bg-slate-50 flex items-center justify-center text-slate-600">
        <div className="flex items-center gap-2 text-sm font-semibold">
          <Loader2
            size={18}
            className="animate-spin"
          />

          Memeriksa sesi login...
        </div>
      </div>
    );
  }

  return (
    <>
      {!isLoggedIn ? (
        <LoginScreen
          onLogin={(user) => {
            setCurrentRole(user.role);
            setCurrentUser(
              `${user.name} (${user.role})`
            );
            setIsLoggedIn(true);

            addLog(
              "System",
              "Login Berhasil",
              `User login sebagai ${user.role}`
            );
          }}
        />
      ) : (
        <div className="min-h-screen bg-slate-50 flex flex-col font-sans">
          {/* Top Navigation */}
          <Navbar
            currentRole={currentRole}
            onChangeRole={handleRoleChange}
            currentUser={currentUser}
            onLogout={handleLogout}
            onToggleSidebar={() => setIsSidebarOpen(true)}
          />

          <Sidebar 
            isOpen={isSidebarOpen}
            onClose={() => setIsSidebarOpen(false)}
            currentRole={currentRole}
            officerTab={officerTab}
            setOfficerTab={setOfficerTab}
            requesterTab={requesterTab}
            setRequesterTab={setRequesterTab}
            superadminTab={superadminTab}
            setSuperadminTab={setSuperadminTab}
            requests={requests}
            receipts={receipts}
          />

          <main className="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        {/* Dynamic Warning Alert */}
        <div className="bg-indigo-50 border border-indigo-150 rounded p-4 mb-8 flex items-start gap-3">
          <Info className="text-indigo-600 mt-0.5 flex-shrink-0" size={16} />
          <div className="text-[11px] text-slate-600 leading-relaxed font-sans">
            <span className="font-extrabold text-indigo-700">PROTOTIPE DESAIN UI:</span> Aplikasi ini mensimulasikan fungsionalitas penuh modul SIPERBANG yang direncanakan dibangun menggunakan <strong className="font-bold text-slate-800">Laravel 13</strong>. Anda dapat menguji modul login role, BON Digital, pengunggahan data Excel stok, pengecekan stok otomatis, pembacaan OCR kuitansi belanja dengan pajak toko yang dinamis (customizable), pencatatan BAST, serta ekspor rekap excel.
          </div>
        </div>

        {/* Stats Section */}
        <DashboardStats requests={requests} receipts={receipts} />

        {/* Role-Specific Workspaces */}
        {currentRole === UserRole.PETUGAS_PERSERDIAN ? (
          /* =========================================================
             1. ROLE WORKSPACE: PETUGAS PERSERDIAN (OFFICER)
             ========================================================= */
          <div className="w-full space-y-6">
            {officerTab === "dashboard" && (
              <div className="space-y-6">
                {/* Task list quick peek */}
                <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
                    <h3 className="text-sm font-extrabold text-slate-800 tracking-tight mb-4 flex items-center gap-1.5 uppercase">
                      <AlertCircle className="text-amber-500" size={16} />
                      Antrean Pengajuan BON Masuk Baru
                    </h3>
                    <div className="space-y-3">
                      {requests.filter((r) => r.status === RequestStatus.DIAJUKAN).map((r) => (
                        <div key={r.id} className="flex justify-between items-center bg-amber-50/30 border border-amber-200 rounded p-4 text-xs">
                          <div>
                            <span className="font-mono text-[10px] font-bold text-slate-500 block uppercase tracking-wider">{r.bonNo}</span>
                            <span className="font-bold text-slate-800 text-sm mt-1 block">{r.itemName}</span>
                            <span className="text-[11px] text-slate-500 mt-1 block">Diminta oleh {r.requester} • {r.section}</span>
                          </div>
                          <button
                            onClick={() => {
                              setOfficerTab("checking");
                            }}
                            className="bg-indigo-600 text-white px-3 py-1.5 rounded font-bold flex items-center gap-1 hover:bg-indigo-700 transition-colors text-[11px] shadow-xs"
                          >
                            <span>Proses Cek</span>
                            <ChevronRight size={11} />
                          </button>
                        </div>
                      ))}
                      {requests.filter((r) => r.status === RequestStatus.DIAJUKAN).length === 0 && (
                        <div className="text-center py-6 text-slate-400 text-xs font-semibold">
                          Semua antrean BON digital telah diproses. Bersih!
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Stock quick view */}
                  <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
                    <div className="flex justify-between items-center mb-4">
                      <h3 className="text-sm font-extrabold text-slate-800 tracking-tight uppercase">Ringkasan Ketersediaan Stok</h3>
                      <button onClick={() => setOfficerTab("stock")} className="text-[11px] text-indigo-600 font-bold hover:text-indigo-700">
                        Lihat Semua
                      </button>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                      {stock.slice(0, 3).map((st) => (
                        <div key={st.id} className="bg-slate-50 border border-slate-200 rounded p-3 text-xs">
                          <span className="font-mono text-[9px] text-slate-400 font-bold block uppercase tracking-wider">{st.code}</span>
                          <span className="font-bold text-slate-800 text-sm mt-1 block truncate">{st.name}</span>
                          <span className={`mt-2 inline-block font-extrabold text-[11px] ${st.qty < 10 ? "text-rose-500" : "text-emerald-600"}`}>
                            {st.qty} {st.unit} Tersedia
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              )}

              {officerTab === "checking" && (
                <StockChecking
                  requests={requests}
                  stockList={stock}
                  onUpdateStatus={handleUpdateStatus}
                  onDistribute={handleDistribute}
                  onProcure={handleProcure}
                  onCompleteProcurement={handleCompleteProcurement}
                  currentUser={currentUser}
                />
              )}

              {officerTab === "stock" && (
                <StockManagement stockList={stock} onUploadStock={handleUploadStock} />
              )}

              {officerTab === "ocr" && (
                <ReceiptOCRProcessor
                  receipts={receipts}
                  requests={requests}
                  onAddReceipt={handleAddReceipt}
                  onVerifyReceipt={handleVerifyReceipt}
                />
              )}

              {officerTab === "report" && <ReportExport receipts={receipts} />}

              {officerTab === "history" && <HistoryLog logs={logs} />}
            </div>
        ) : currentRole === UserRole.KETUA_TIM ? (
          /* =========================================================
             2. ROLE WORKSPACE: KETUA TIM KERJA (REQUESTER)
             ========================================================= */
          <div className="w-full space-y-6">
            {requesterTab === "bon" && (
              <BonDigitalForm onAddRequest={handleAddRequest} currentUser={currentUser} />
            )}

              {requesterTab === "monitoring" && (
                <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="bg-amber-50 text-amber-600 p-2.5 rounded border border-amber-100">
                      <CheckSquare size={18} />
                    </div>
                    <div>
                      <h2 className="text-base font-extrabold text-slate-800 tracking-tight">Daftar Pengajuan Kebutuhan Barang</h2>
                      <p className="text-[11px] text-slate-500">
                        Pantau status real-time, ketersediaan stok, hasil pengecekan, serta status pengadaan unit kerja Anda
                      </p>
                    </div>
                  </div>

                  <div className="space-y-3">
                    {requests.map((req) => (
                      <div key={req.id} className="border border-slate-200 rounded p-4 hover:border-slate-300 transition-colors">
                        <div className="flex flex-col sm:flex-row justify-between sm:items-center gap-3">
                          <div>
                            <div className="flex items-center gap-2 flex-wrap text-[10px] font-mono font-bold text-slate-400 uppercase tracking-wider">
                              <span>{req.bonNo}</span>
                              <span className="text-slate-300">•</span>
                              <span>{req.date}</span>
                            </div>
                            <h3 className="text-sm font-extrabold text-slate-800 mt-1">
                              {req.itemName}
                              <span className="text-[11px] font-normal text-slate-500 ml-1 font-mono">
                                ({req.qtyRequested} {req.unit})
                              </span>
                            </h3>

                            {/* Fulfillments display info */}
                            <div className="flex items-center gap-4 mt-2.5 text-[10px] font-bold">
                              <span className="text-slate-500">
                                Jumlah Dipenuhi: <strong className="text-slate-800 font-extrabold">{req.qtyFulfilled} {req.unit}</strong>
                              </span>
                              <span className="text-slate-300">|</span>
                              <span className="text-slate-500">
                                Hasil Cek Stok: <strong className="text-indigo-600 font-extrabold">{req.qtyAvailable} {req.unit} tersedia di gudang</strong>
                              </span>
                            </div>
                          </div>

                          <div className="flex items-center gap-2 self-start sm:self-auto">
                            <span className={`px-2.5 py-0.5 rounded text-[10px] font-extrabold border ${
                              req.status === RequestStatus.SELESAI || req.status === RequestStatus.TERPENUHI
                                ? "bg-emerald-50 text-emerald-800 border-emerald-200"
                                : req.status === RequestStatus.DIAJUKAN
                                ? "bg-amber-50 text-amber-800 border-amber-300"
                                : req.status === RequestStatus.TERPENUHI_SEBAGIAN
                                ? "bg-amber-50 text-amber-700 border-amber-200"
                                : "bg-rose-50 text-rose-800 border-rose-200"
                            }`}>
                              {req.status}
                            </span>
                          </div>
                        </div>
                      </div>
                    ))}
                    {requests.length === 0 && (
                      <div className="text-center py-10 text-slate-400 text-xs bg-slate-50 border border-slate-200 rounded">
                        Anda belum mengajukan kebutuhan barang.
                      </div>
                    )}
                  </div>
                </div>
              )}

              {requesterTab === "stock" && <RequesterStockList stock={stock} />}

              {requesterTab === "history" && <HistoryLog logs={logs} />}
            </div>
        ) : (
          /* =========================================================
             3. ROLE WORKSPACE: SUPERADMIN
             ========================================================= */
          <div className="w-full space-y-6">
            {superadminTab === "users" && (
              <UserManagement
                users={users}
                onAddUser={handleAddUser}
                onUpdateUser={handleUpdateUser}
                onDeleteUser={handleDeleteUser}
              />
            )}
            
            {superadminTab === "dashboard" && (
              <div className="space-y-6">
                {/* Task list quick peek */}
                <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
                    <h3 className="text-sm font-extrabold text-slate-800 tracking-tight mb-4 flex items-center gap-1.5 uppercase">
                      <AlertCircle className="text-amber-500" size={16} />
                      Antrean Pengajuan BON Masuk Baru
                    </h3>
                    <div className="space-y-3">
                      {requests.filter((r) => r.status === RequestStatus.DIAJUKAN).map((r) => (
                        <div key={r.id} className="flex justify-between items-center bg-amber-50/30 border border-amber-200 rounded p-4 text-xs">
                          <div>
                            <span className="font-mono text-[10px] font-bold text-slate-500 block uppercase tracking-wider">{r.bonNo}</span>
                            <span className="font-bold text-slate-800 text-sm mt-1 block">{r.itemName}</span>
                            <span className="text-[11px] text-slate-500 mt-1 block">Diminta oleh {r.requester} • {r.section}</span>
                          </div>
                          <button
                            onClick={() => {
                              setSuperadminTab("checking");
                            }}
                            className="bg-indigo-600 text-white px-3 py-1.5 rounded font-bold flex items-center gap-1 hover:bg-indigo-700 transition-colors text-[11px] shadow-xs"
                          >
                            <span>Proses Cek</span>
                            <ChevronRight size={11} />
                          </button>
                        </div>
                      ))}
                      {requests.filter((r) => r.status === RequestStatus.DIAJUKAN).length === 0 && (
                        <div className="text-center py-6 text-slate-400 text-xs font-semibold">
                          Semua antrean BON digital telah diproses. Bersih!
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Stock quick view */}
                  <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
                    <div className="flex justify-between items-center mb-4">
                      <h3 className="text-sm font-extrabold text-slate-800 tracking-tight uppercase">Ringkasan Ketersediaan Stok</h3>
                      <button onClick={() => setSuperadminTab("stock_manage")} className="text-[11px] text-indigo-600 font-bold hover:text-indigo-700">
                        Lihat Semua
                      </button>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                      {stock.slice(0, 3).map((st) => (
                        <div key={st.id} className="bg-slate-50 border border-slate-200 rounded p-3 text-xs">
                          <span className="font-mono text-[9px] text-slate-400 font-bold block uppercase tracking-wider">{st.code}</span>
                          <span className="font-bold text-slate-800 text-sm mt-1 block truncate">{st.name}</span>
                          <span className={`mt-2 inline-block font-extrabold text-[11px] ${st.qty < 10 ? "text-rose-500" : "text-emerald-600"}`}>
                            {st.qty} {st.unit} Tersedia
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
            )}

            {superadminTab === "checking" && (
              <StockChecking
                requests={requests}
                stockList={stock}
                onUpdateStatus={handleUpdateStatus}
                onDistribute={handleDistribute}
                onProcure={handleProcure}
                onCompleteProcurement={handleCompleteProcurement}
                currentUser={currentUser}
              />
            )}

            {superadminTab === "stock_manage" && (
              <StockManagement stockList={stock} onUploadStock={handleUploadStock} />
            )}

            {superadminTab === "ocr" && (
              <ReceiptOCRProcessor
                receipts={receipts}
                requests={requests}
                onAddReceipt={handleAddReceipt}
                onVerifyReceipt={handleVerifyReceipt}
              />
            )}

            {superadminTab === "report" && <ReportExport receipts={receipts} />}

            {superadminTab === "bon" && (
              <BonDigitalForm onAddRequest={handleAddRequest} currentUser={currentUser} />
            )}

            {superadminTab === "monitoring" && (
              <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
                <div className="flex items-center gap-3 mb-6">
                  <div className="bg-amber-50 text-amber-600 p-2.5 rounded border border-amber-100">
                    <CheckSquare size={18} />
                  </div>
                  <div>
                    <h2 className="text-base font-extrabold text-slate-800 tracking-tight">Daftar Pengajuan Kebutuhan Barang</h2>
                    <p className="text-[11px] text-slate-500">
                      Pantau status real-time, ketersediaan stok, hasil pengecekan, serta status pengadaan unit kerja Anda
                    </p>
                  </div>
                </div>

                <div className="space-y-3">
                  {requests.map((req) => (
                    <div key={req.id} className="border border-slate-200 rounded p-4 hover:border-slate-300 transition-colors">
                      <div className="flex flex-col sm:flex-row justify-between sm:items-center gap-3">
                        <div>
                          <div className="flex items-center gap-2 flex-wrap text-[10px] font-mono font-bold text-slate-400 uppercase tracking-wider">
                            <span>{req.bonNo}</span>
                            <span className="text-slate-300">•</span>
                            <span>{req.date}</span>
                          </div>
                          <h3 className="text-sm font-extrabold text-slate-800 mt-1">
                            {req.itemName}
                            <span className="text-[11px] font-normal text-slate-500 ml-1 font-mono">
                              ({req.qtyRequested} {req.unit})
                            </span>
                          </h3>

                          {/* Fulfillments display info */}
                          <div className="flex items-center gap-4 mt-2.5 text-[10px] font-bold">
                            <span className="text-slate-500">
                              Jumlah Dipenuhi: <strong className="text-slate-800 font-extrabold">{req.qtyFulfilled} {req.unit}</strong>
                            </span>
                            <span className="text-slate-300">|</span>
                            <span className="text-slate-500">
                              Hasil Cek Stok: <strong className="text-indigo-600 font-extrabold">{req.qtyAvailable} {req.unit} tersedia di gudang</strong>
                            </span>
                          </div>
                        </div>

                        <div className="flex items-center gap-2 self-start sm:self-auto">
                          <span className={`px-2.5 py-0.5 rounded text-[10px] font-extrabold border ${
                            req.status === RequestStatus.SELESAI || req.status === RequestStatus.TERPENUHI
                              ? "bg-emerald-50 text-emerald-800 border-emerald-200"
                              : req.status === RequestStatus.DIAJUKAN
                              ? "bg-amber-50 text-amber-800 border-amber-300"
                              : req.status === RequestStatus.TERPENUHI_SEBAGIAN
                              ? "bg-amber-50 text-amber-700 border-amber-200"
                              : "bg-rose-50 text-rose-800 border-rose-200"
                          }`}>
                            {req.status}
                          </span>
                        </div>
                      </div>
                    </div>
                  ))}
                  {requests.length === 0 && (
                    <div className="text-center py-10 text-slate-400 text-xs bg-slate-50 border border-slate-200 rounded">
                      Belum ada pengajuan kebutuhan barang.
                    </div>
                  )}
                </div>
              </div>
            )}

            {superadminTab === "stock_catalog" && <RequesterStockList stock={stock} />}

            {superadminTab === "history" && <HistoryLog logs={logs} />}
          </div>
        )}
      </main>

      {/* Footer */}
      <footer className="bg-white border-t border-slate-200 py-6 mt-12 text-center text-[11px] text-slate-500 font-medium">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <p>© 2026 BBPSDM Komunikasi dan Digital Makassar. Seluruh hak cipta dilindungi.</p>
          <p className="mt-1 text-[9px] text-slate-400 font-bold uppercase tracking-wider">SIPERBANG v1.1 - Prototipe Antarmuka & Verifikasi Pajak Dinamis</p>
        </div>
      </footer>
    </div>
    )}
    </>
  );
}
