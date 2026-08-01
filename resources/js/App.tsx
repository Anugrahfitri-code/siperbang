/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState, useEffect } from "react";
import { UserRole, UserAccount, ItemRequest, StockItem, ReceiptData, HistoryLog as LogType, RequestStatus, ProcurementMethod } from "./shared/types";
import { Navbar } from "./shared/components/layout/Navbar";
import { DashboardStats } from "./features/dashboard/components/DashboardStats";
import { BonDigitalForm } from "./features/requests/components/BonDigitalForm";
import { StockManagement } from "./features/inventory-upload/components/StockManagement";
import { StockChecking } from "./features/inventory/components/StockChecking";
import { ReceiptOCRProcessor } from "./features/receipts/components/ReceiptOCRProcessor";
import { ReportExport } from "./features/reports/components/ReportExport";
import { HistoryLog } from "./features/audit/components/HistoryLog";
import { LoginScreen } from "./features/auth/components/LoginScreen";
import { Sidebar } from "./shared/components/layout/Sidebar";
import { RequesterStockList } from "./features/inventory/components/RequesterStockList";
import { UserManagement } from "./features/users/components/UserManagement";
import { KetuaTimDashboard } from "./features/dashboard/components/KetuaTimDashboard";
import { BonMonitoringList, type BonHeaderRow } from "./features/requests/components/BonMonitoringList";
import type { BonDraft } from "./features/requests/components/BonDigitalForm";
import { AlertDialog } from "./shared/components/feedback/AlertDialog";
import { LayoutDashboard, FileSpreadsheet, ClipboardList, Package, Receipt, History, AlertCircle, Info, ChevronRight, CheckSquare, Loader2, Bell, User, FileText, Search } from "lucide-react";
import { apiFetch } from "./shared/api";
import { useSettings } from "./shared/context/SettingsContext";
import { SiteSettings } from "./features/settings/components/SiteSettings";
import { renderBrandingTemplate } from "./shared/settings";
import { ColoredText } from "./shared/components/branding/ColoredText";

type AuthenticatedUser = {
  id: number | string;
  name: string;
  username: string;
  role: UserRole;
  section?: string | null;
};

import { normalizeRequest, normalizeRequestStatus, toDateOnly } from "./shared/utils/requestUtils";

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
  const [bons, setBons] = useState<any[]>([]);
  const [editingDraft, setEditingDraft] = useState<any | null>(null);

  const [requestsLoading, setRequestsLoading] = useState(true);
  const [requestsError, setRequestsError] = useState<string | null>(null);

  // Navigation tab states
  const [isSidebarOpen, setIsSidebarOpen] = useState(() => {
    if (typeof window === "undefined") {
      return false;
    }
    return window.matchMedia("(min-width: 1024px)").matches;
  });

  useEffect(() => {
    const desktopMedia = window.matchMedia("(min-width: 1024px)");

    const syncSidebarWithViewport = (event: MediaQueryListEvent) => {
      setIsSidebarOpen(event.matches);
    };

    setIsSidebarOpen(desktopMedia.matches);
    desktopMedia.addEventListener("change", syncSidebarWithViewport);

    return () => {
      desktopMedia.removeEventListener("change", syncSidebarWithViewport);
    };
  }, []);
  const [officerTab, setOfficerTab] = useState<"dashboard" | "checking" | "stock" | "ocr" | "report" | "history">(
    () => {
      const requestedModule = new URLSearchParams(window.location.search).get("module");
      if (requestedModule === "excel") {
        return "stock";
      }
      return (localStorage.getItem("officerTab") as any) || "dashboard";
    }
  );
  const [requesterTab, setRequesterTab] = useState<"dashboard" | "bon" | "monitoring" | "history" | "stock">(
    () => (localStorage.getItem("requesterTab") as any) || "dashboard"
  );
  const [superadminTab, setSuperadminTab] = useState<"users" | "site_settings" | "dashboard" | "checking" | "stock_manage" | "ocr" | "report" | "bon" | "monitoring" | "stock_catalog" | "history">(
    () => {
      const requestedModule = new URLSearchParams(window.location.search).get("module");
      if (requestedModule === "excel") {
        return "stock_manage";
      }
      return (localStorage.getItem("superadminTab") as any) || "users";
    }
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

  useEffect(() => {
    const url = new URL(window.location.href);
    if (url.searchParams.get("module") === "excel") {
      url.searchParams.delete("module");
      window.history.replaceState({}, "", `${url.pathname}${url.search}${url.hash}`);
    }
  }, []);

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
  
  // Fetch initial data from API
  const loadData = async () => {
    setRequestsLoading(true);
    setRequestsError(null);
    try {
      const isKetuaTim = currentRole === UserRole.KETUA_TIM;

      const fetchRequests = apiFetch("/api/requests");
      const fetchLogs = apiFetch("/api/logs");

      const fetchBons = apiFetch("/api/requests/bon?all=true");
      let fetchStocks = Promise.resolve(null as any);
      let fetchReceipts = Promise.resolve(null as any);
      let fetchUsers = Promise.resolve(null as any);

      if (!isKetuaTim) {
        fetchStocks = apiFetch("/api/stocks");
        fetchReceipts = apiFetch("/api/receipts");
        fetchUsers = apiFetch("/api/users");
      }

      const [reqRes, logRes, bonRes, stockRes, recRes, userRes] =
        await Promise.all([
          fetchRequests,
          fetchLogs,
          fetchBons,
          fetchStocks,
          fetchReceipts,
          fetchUsers,
        ]);
      
      if (reqRes && reqRes.ok) {
        const reqs = await reqRes.json();
        setRequests(reqs.map(normalizeRequest));
        setRequestsError(null);
      } else {
        setRequestsError("Gagal mengambil data pengajuan dari server.");
      }

      if (bonRes && bonRes.ok) {
        const fetchedBons = await bonRes.json();
        setBons(fetchedBons);
      }

      if (stockRes && stockRes.ok) {
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
      } else {
        setStock([]);
      }

      if (userRes && userRes.ok) {
        const fetchedUsers = await userRes.json();
        setUsers(fetchedUsers.map((u: any) => ({
          id: String(u.id),
          name: u.name,
          username: u.username,
          role: u.role,
          section: u.section,
          status: u.status
        })));
      } else {
        setUsers([]);
      }

      if (logRes && logRes.ok) {
        const fetchedLogs = await logRes.json();
        setLogs(fetchedLogs.map((l: any) => ({
          id: String(l.id),
          timestamp: l.created_at,
          actor: l.actor,
          action: l.action,
          details: l.details
        })));
      }

      if (recRes && recRes.ok) {
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
          items: Array.isArray(r.items)
            ? r.items.map((item: any) => ({
                id: String(item.id),
                name: String(item.name ?? ""),
                qty: Number(item.qty ?? 0),
                unit: String(item.unit ?? ""),
                inventoryCode: String(
                  item.inventory_code ?? ""
                ).replace(/\D/g, ""),
                inventoryCodeDescription:
                  item.inventory_code_master
                    ?.nama_barang
                    ?? null,
                stockItemId:
                  item.stock_item_id != null
                  && Number.isInteger(
                    Number(item.stock_item_id)
                  )
                  && Number(item.stock_item_id) > 0
                    ? Number(item.stock_item_id)
                    : null,
                codeConfidence: null,
                price: Number(item.price ?? 0),
                subtotal: Number(
                  item.subtotal ?? 0
                ),
              }))
            : []
        })));
      } else {
        setReceipts([]);
      }
    } catch (err) {
      console.error("Error fetching data:", err);
      setRequestsError("Terjadi kesalahan koneksi saat memuat data.");
    } finally {
      setRequestsLoading(false);
    }
  };

  useEffect(() => {
    if (isLoggedIn) {
      loadData();
    }
  }, [isLoggedIn, currentRole]);

  // local storage sync removed

  // Handle Switching Role
  const handleRoleChange = (role: UserRole) => {
    setCurrentRole(role);
    if (role === UserRole.SUPERADMIN) {
      setCurrentUser("Admin Utama (Superadmin)");
      setSuperadminTab("site_settings");
    } else if (role === UserRole.KETUA_TIM) {
      setCurrentUser("Budi Santoso (Ketua Tim TU)");
    } else {
      setCurrentUser("Iwan Setiawan (Petugas Persediaan)");
    }
  };

  // User Management Actions
  const handleAddUser = async (newUser: Omit<UserAccount, "id">) => {
    try {
      const response = await apiFetch("/api/users", {
        method: "POST",
        body: JSON.stringify(newUser),
      });

      if (response.ok) {
        const createdUser = await response.json();
        setUsers((prev) => [...prev, createdUser]);
        addLog(currentUser, "Tambah Pengguna", `Menambahkan akun baru: ${newUser.name} (${newUser.role})`);
        
        // Show success alert
        setAlertMsg({
          title: "Berhasil",
          message: "Akun pengguna baru berhasil ditambahkan.",
          variant: "success",
        });
        setTimeout(() => setAlertMsg(null), 3000);
      } else {
        const data = await response.json();
        setAlertMsg({
          title: "Gagal Menambah Akun",
          message: data.message || "Terjadi kesalahan saat menambah akun.",
          variant: "danger",
        });
        setTimeout(() => setAlertMsg(null), 4000);
      }
    } catch (error) {
      console.error("Gagal menambah pengguna:", error);
      setAlertMsg({
        title: "Kesalahan",
        message: "Gagal menghubungi server.",
        variant: "danger",
      });
      setTimeout(() => setAlertMsg(null), 4000);
    }
  };

  const handleUpdateUser = async (id: string, updates: Partial<UserAccount>) => {
    try {
      const response = await apiFetch(`/api/users/${id}`, {
        method: "PUT",
        body: JSON.stringify(updates),
      });

      if (response.ok) {
        const updatedUser = await response.json();
        setUsers((prev) => prev.map((u) => (u.id === id ? { ...u, ...updatedUser } : u)));
        addLog(currentUser, "Update Pengguna", `Memperbarui data akun ID: ${id}`);
        setAlertMsg({
          title: "Berhasil",
          message: "Data akun berhasil diperbarui.",
          variant: "success",
        });
        setTimeout(() => setAlertMsg(null), 3000);
      } else {
        const data = await response.json();
        setAlertMsg({
          title: "Gagal Update",
          message: data.message || "Terjadi kesalahan saat memperbarui akun.",
          variant: "danger",
        });
        setTimeout(() => setAlertMsg(null), 4000);
      }
    } catch (error) {
      console.error("Gagal update pengguna:", error);
      setAlertMsg({
        title: "Kesalahan",
        message: "Gagal menghubungi server.",
        variant: "danger",
      });
      setTimeout(() => setAlertMsg(null), 4000);
    }
  };

  const handleDeleteUser = async (id: string) => {
    try {
      const response = await apiFetch(`/api/users/${id}`, {
        method: "DELETE",
      });
      if (response.ok) {
        setUsers((prev) => prev.filter((u) => u.id !== id));
        addLog(currentUser, "Hapus Pengguna", `Menghapus akun ID: ${id}`);
        setAlertMsg({
          title: "Berhasil",
          message: "Akun berhasil dihapus.",
          variant: "success",
        });
        setTimeout(() => setAlertMsg(null), 3000);
      } else {
        const data = await response.json();
        setAlertMsg({
          title: "Gagal Hapus",
          message: data.message || "Terjadi kesalahan saat menghapus akun.",
          variant: "danger",
        });
        setTimeout(() => setAlertMsg(null), 4000);
      }
    } catch (error) {
      console.error("Gagal hapus pengguna:", error);
      setAlertMsg({
        title: "Kesalahan",
        message: "Gagal menghubungi server.",
        variant: "danger",
      });
      setTimeout(() => setAlertMsg(null), 4000);
    }
  };

  const [alertMsg, setAlertMsg] = useState<{ title: string; message: string; variant?: "danger" | "warning" | "info" | "success" } | null>(null);

  // Log activity helper
  // Helper: tulis log ke frontend state DAN backend DB
  const addLog = async (actor: string, action: string, details: string, userId?: number) => {
    const newLog: LogType = {
      id: "log-" + Math.random().toString(36).substring(2, 9),
      timestamp: new Date().toISOString().replace("T", " ").substring(0, 19),
      actor,
      action,
      details,
    };
    // Update local state immediately for instant UI feedback
    setLogs((prev) => [newLog, ...prev]);

    // Persist to DB so ALL roles see this log
    try {
      await apiFetch("/api/logs", {
        method: "POST",
        body: JSON.stringify({ actor, action, details, user_id: userId }),
      });
    } catch {
      // Non-critical — log already in UI state
    }
  };

  // --- ACTIONS ---

  // 1. Submit a new BON Digital Request
  const handleAddRequest = async (
    payload: import("./features/requests/components/BonDigitalForm").BonSubmitPayload
  ): Promise<void> => {
    const response = await apiFetch("/api/requests", {
      method: "POST",
      body: JSON.stringify(payload),
    });
    const data: any = await response.json().catch(() => ({}));

    if (!response.ok) {
      if (response.status === 401) {
        setIsLoggedIn(false);
        throw new Error("Sesi login berakhir. Silakan masuk kembali.");
      }
      const validationMessage =
        data.errors && typeof data.errors === "object"
          ? Object.values(data.errors as Record<string, string[]>)
              .flat()
              .join(" ")
          : "";
      throw new Error(
        validationMessage || data.message || "BON gagal disimpan ke database."
      );
    }

    // Backend returns BonHeader — refresh the bon list so monitoring tab updates
    const bonRes = await apiFetch("/api/requests/bon?all=true");
    if (bonRes.ok) {
      const freshBons = await bonRes.json();
      setBons(freshBons);
    }

    // Log: Ketua Tim kirim BON
    const statusLabel = payload.status === "draft" ? "Simpan Draft" : "Kirim Pengajuan";
    const itemCount   = payload.items.length;
    const isOnBehalf  = currentUser.toLowerCase().includes("admin") && payload.requester;
    
    let detailMsg = `${statusLabel} BON berhasil. ${itemCount} jenis barang diminta. Keperluan: "${payload.keperluan}".`;
    if (isOnBehalf) {
      detailMsg += ` (Diajukan atas nama ${payload.requester} sebagai Ketua Tim)`;
    }

    await addLog(
      currentUser,
      payload.status === "draft" ? "Simpan Draft BON" : "Kirim BON",
      detailMsg,
      data.user_id
    );
  };

  // 1b. Update existing draft (PUT /api/requests/bon/{id})
  const handleUpdateDraft = async (
    bonId: number,
    payload: import("./features/requests/components/BonDigitalForm").BonSubmitPayload
  ): Promise<void> => {
    const response = await apiFetch(`/api/requests/bon/${bonId}`, {
      method: "PUT",
      body:   JSON.stringify(payload),
    });
    const data: any = await response.json().catch(() => ({}));

    if (!response.ok) {
      if (response.status === 401) { setIsLoggedIn(false); throw new Error("Sesi berakhir."); }
      const msg = data.errors
        ? Object.values(data.errors as Record<string, string[]>).flat().join(" ")
        : data.message ?? "Gagal memperbarui draft.";
      throw new Error(msg);
    }

    // Refresh bons list AND requests list so dashboard shows updated data
    const [bonRes, reqRes] = await Promise.all([
      apiFetch("/api/requests/bon?all=true"),
      apiFetch("/api/requests"),
    ]);
    if (bonRes.ok) setBons(await bonRes.json());
    if (reqRes.ok) {
      const reqs = await reqRes.json();
      setRequests(reqs.map(normalizeRequest));
    }

    // If submitted (not draft), clear editingDraft
    if (payload.status !== "draft") setEditingDraft(null);

    const label = payload.status === "draft" ? "Update Draft BON" : "Kirim BON (dari Draft)";
    const isOnBehalf = currentUser.toLowerCase().includes("admin") && payload.requester;
    let detailMsg = `BON ${editingDraft?.bonNo ?? ""} ${payload.status === "draft" ? "diperbarui." : "dikirim ke verifikasi."}`;
    
    if (isOnBehalf) {
      detailMsg += ` (Diajukan atas nama ${payload.requester} sebagai Ketua Tim)`;
    }

    await addLog(currentUser, label, detailMsg, data.user_id);
  };

  // 1c. Delete a draft (DELETE /api/requests/bon/{id})
  const handleDeleteDraft = async (bonId: number, bonNo: string): Promise<void> => {
    const response = await apiFetch(`/api/requests/bon/${bonId}`, { method: "DELETE" });
    if (!response.ok) {
      const data: any = await response.json().catch(() => ({}));
      throw new Error(data.message ?? "Gagal menghapus draft.");
    }
    const targetUserId = bons.find((b: any) => b.id === bonId)?.user_id;
    setBons((prev) => prev.filter((b: any) => b.id !== bonId));
    // Juga hapus dari requests state agar dashboard langsung sinkron
    setRequests((prev) => prev.filter((r) => r.bonNo !== bonNo));
    await addLog(currentUser, "Hapus Draft BON", `Draft ${bonNo} dihapus.`, targetUserId);
  };

  // 1d. Batalkan / tolak satu item request (POST /api/requests/{id}/reject)
  const handleCompletePartial = async (reqId: string) => {
    try {
      const response = await fetch(`/api/requests/${reqId}/complete-partial`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || "Gagal menyelesaikan pengajuan.");
      }

      await loadData();
    } catch (err: any) {
      alert("Error: " + err.message);
    }
  };

  const handleReject = async (reqId: string, alasan: string): Promise<void> => {
    const response = await apiFetch(`/api/requests/${reqId}/reject`, {
      method: "POST",
      body:   JSON.stringify({ alasan }),
    });
    const data: any = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(data.message ?? "Gagal membatalkan pengajuan.");
    }

    // Refresh requests state
    const updated = normalizeRequest(data.data ?? data);
    setRequests((prev) => prev.map((r) => r.id === reqId ? updated : r));

    await addLog(
      currentUser,
      "Batalkan Pengajuan",
      `Pengajuan ${updated.bonNo} (${updated.itemName}) dibatalkan. Alasan: ${alasan}`
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
    deductStock?: { id: string | number; qtyToDeduct: number }
  ) => {
    try {
      const payload = {
        status,
        qtyAvailable,
        qtyFulfilled,
        deductStock: deductStock ? {
          id: deductStock.id,
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
            if (s.code === (deductStock as any).code || (s as any).id === deductStock.id) {
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

      await addLog(currentUser, "Verifikasi Stok", logMessage);
    } catch (err: any) {
      console.error(err);
      setAlertMsg({ title: "Gagal Verifikasi Stok", message: err.message || "Terjadi kesalahan saat memverifikasi stok", variant: "danger" });
    }
  };

  // 4. Manual OCR Verified Invoice Saver
  const handleVerifyReceipt = async (id: string, verifiedReceipt: ReceiptData, logMsg: string) => {
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

    await addLog(
      currentUser,
      "Verifikasi Kuitansi",
      logMsg
    );

    /*
     * Verifikasi kuitansi juga mengubah stock_items di backend.
     * Muat ulang agar menu Master Barang langsung menampilkan
     * barang baru atau jumlah stok terbaru tanpa refresh browser.
     */
    await loadData();
  };

  const handleUnverifyReceipt = async (id: string, logMsg: string) => {
    setReceipts((prev) => prev.filter((r) => r.id !== id));

    await addLog(
      currentUser,
      "Batalkan Verifikasi",
      logMsg
    );

    await loadData();
  };

  const handleAddReceipt = async (newReceipt: ReceiptData) => {
    setReceipts((prev) => [newReceipt, ...prev]);
    await addLog(currentUser, "Tambah Kuitansi", `Menambahkan kuitansi manual/baru dari ${newReceipt.storeName} senilai ${formatIDR(newReceipt.total)}.`);
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
    const response = await apiFetch(
      `/api/requests/${reqId}/distribute`,
      { method: "POST", body: JSON.stringify(data) }
    );

    const raw = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(raw.message || "Gagal melakukan distribusi");
    }

    const updated = normalizeRequest(raw);
    setRequests((prev) => prev.map((req) => req.id === reqId ? updated : req));

    await addLog(
      currentUser,
      "Distribusi Barang",
      `BON distribusi: ${data.qtyDistributed} unit dari stok oleh ${data.distributedBy}`
    );
  };

  // 6. Handle Procurement
  const handleProcure = async (
    reqId: string,
    data: {
      method: "Pengadaan Vendor" | "Pengadaan Sendiri (Toko)";
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
    const response = await apiFetch(
      `/api/requests/${reqId}/procure`,
      { method: "POST", body: JSON.stringify(data) }
    );

    const raw = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(raw.message || "Gagal membuat pengadaan");
    }

    const updated = normalizeRequest(raw);
    setRequests((prev) => prev.map((req) => req.id === reqId ? updated : req));

    await addLog(
      currentUser,
      "Pengadaan Barang",
      `BON pengadaan: ${data.qtyProcured} unit via ${data.method}`
    );
  };

  // 7. Handle Complete Procurement
  const handleCompleteProcurement = async (
    reqId: string,
    procurementId: string,
    processedBy: string
  ) => {
    const response = await apiFetch(
      `/api/requests/${reqId}/complete-procurement`,
      { method: "POST", body: JSON.stringify({ procurementId, processedBy }) }
    );

    const raw = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(raw.message || "Gagal menyelesaikan pengadaan");
    }

    const updated = normalizeRequest(raw);
    setRequests((prev) => prev.map((req) => req.id === reqId ? updated : req));

    await addLog(
      currentUser,
      "Terima Pengadaan",
      `Pengadaan #${procurementId} diterima. Barang masuk ke stok gudang.`
    );
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

      setAlertMsg({ title: "Logout Gagal", message: "Logout gagal. Silakan coba kembali.", variant: "danger" });
    }
  };

  const formatIDR = (num: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(num);
  };

  const { settings } = useSettings();

  useEffect(() => {
    const appName = settings.app_name || "SIPERBANG";
    document.title = isLoggedIn
      ? `${appName} — Modul Stok & Persediaan`
      : `${appName} — ${settings.app_subtitle || "Sistem Informasi Persediaan Barang"}`;
  }, [isLoggedIn, settings.app_name, settings.app_subtitle]);

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
            onToggleSidebar={() =>
              setIsSidebarOpen((previous) => !previous)
            }
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

          <div
            className={`flex min-h-[calc(100vh-4rem)] flex-1 flex-col transition-[margin] duration-300 ease-in-out ${
              isSidebarOpen ? "lg:ml-72" : "lg:ml-0"
            }`}
          >
            <main className="mx-auto w-full max-w-[1600px] flex-1 px-4 py-7 sm:px-6 lg:px-8">

        {/* Stats Section */}
        {currentRole !== UserRole.KETUA_TIM && (
          <div className="space-y-6 animate-fade-in">
            <DashboardStats requests={requests} receipts={receipts} />
          </div>
        )}

        {/* Role-Specific Workspaces */}
        {currentRole === UserRole.PETUGAS_PERSERDIAN ? (
          /* =========================================================
             1. ROLE WORKSPACE: PETUGAS PERSERDIAN (OFFICER)
             ========================================================= */
          <div className="w-full space-y-6">
            {officerTab === "dashboard" && (
              <div className="space-y-6">
                {/* Task list quick peek */}
                <div className="space-y-6">
                  {/* Banner */}
                  <div className="relative bg-gradient-to-r from-[#f8faff] to-[#f0f4ff] rounded-2xl border border-indigo-50/50 p-6 shadow-sm overflow-hidden flex flex-col md:flex-row md:items-center gap-5">
                    {/* Glow effects */}
                    <div className="absolute right-0 top-0 w-64 h-64 bg-indigo-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
                    <div className="absolute left-0 bottom-0 w-48 h-48 bg-blue-500/5 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2 pointer-events-none"></div>
                    
                    <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white shadow-sm border border-slate-100 text-amber-500 relative z-10">
                      <Bell size={26} strokeWidth={2.5} />
                    </div>
                    <div className="relative z-10">
                      <h3 className="text-base font-extrabold text-slate-800 uppercase tracking-wide">
                        Antrian Pengajuan BON Masuk Baru
                      </h3>
                      <p className="text-xs font-medium text-slate-500 mt-1">
                        Daftar pengajuan bon masuk baru yang perlu Anda proses pemeriksaan dan pengecekan.
                      </p>
                    </div>
                  </div>

                  <div className="space-y-4">
                      {requests.filter((r) => r.status === RequestStatus.DIAJUKAN).map((r) => {
                      const relatedBon = bons.find(b => (b.bonNo || (b as any).bon_no) === (r.bonNo || (r as any).bon_no));
                      return (
                        <div key={r.id} className="flex flex-col sm:flex-row justify-between sm:items-center bg-white border border-slate-100 border-l-4 border-l-amber-400 rounded-xl p-5 shadow-sm gap-4">
                          <div>
                            <span className="font-mono text-xs font-bold text-slate-400 block uppercase tracking-wider mb-1">{r.bonNo}</span>
                            <span className="font-extrabold text-slate-800 text-base block">{r.itemName}</span>
                            
                            {relatedBon && (relatedBon.keperluan || relatedBon.catatan) && (
                              <div className="mt-2 mb-2 text-xs text-slate-600 bg-slate-50 p-2.5 rounded-md border border-slate-200/60 leading-relaxed">
                                {relatedBon.keperluan && (
                                  <div><span className="font-bold text-slate-700">Keperluan:</span> {relatedBon.keperluan}</div>
                                )}
                                {relatedBon.catatan && (
                                  <div className={relatedBon.keperluan ? "mt-1.5 pt-1.5 border-t border-slate-200/60" : ""}><span className="font-bold text-slate-700">Catatan BON:</span> {relatedBon.catatan}</div>
                                )}
                              </div>
                            )}

                            <div className="flex items-center gap-2 text-xs text-slate-500 font-medium mt-2">
                              <User size={12} className="text-slate-400" />
                              <span>Diminta oleh {r.requester}</span>
                              <span className="text-slate-300 mx-1">•</span>
                              <FileText size={12} className="text-slate-400" />
                              <span>{r.section}</span>
                            </div>
                          </div>
                          <button
                            onClick={() => {
                              setOfficerTab("checking");
                            }}
                            className="bg-blue-600 text-white px-5 py-2.5 rounded-lg font-bold flex items-center gap-2 hover:bg-blue-700 transition-colors text-xs shadow-sm self-start sm:self-auto"
                          >
                            <Search size={14} />
                            <span>Proses Cek</span>
                            <ChevronRight size={14} />
                          </button>
                        </div>
                      );})}
                      {requests.filter((r) => r.status === RequestStatus.DIAJUKAN).length === 0 && (
                        <div className="text-center py-6 text-slate-400 text-xs font-semibold bg-white rounded-2xl border border-slate-200 shadow-sm">
                          Semua antrean BON digital telah diproses. Bersih!
                        </div>
                      )}
                    </div>
                  </div>


                </div>
              )}

              {officerTab === "checking" && (
                <StockChecking
                  requests={requests}
                  stockList={stock}
                  bons={bons}
                  onUpdateStatus={handleUpdateStatus}
                  onDistribute={handleDistribute}
                  onProcure={handleProcure}
                  onCompleteProcurement={handleCompleteProcurement}
                  onReject={handleReject}
                  onCompletePartial={handleCompletePartial}
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
                  onUnverifyReceipt={handleUnverifyReceipt}
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
            {requesterTab === "dashboard" && (
              <KetuaTimDashboard
                requests={requests}
                loading={requestsLoading}
                error={requestsError}
                onRefresh={loadData}
                currentUser={currentUser}
                onEditDraft={(bonNo) => {
                  // Cari BonHeader dari bons state berdasarkan bonNo
                  const bon = (bons as any[]).find((b) => b.bon_no === bonNo || b.bonNo === bonNo);
                  if (bon) {
                    setEditingDraft({
                      id:        bon.id,
                      bonNo:     bon.bon_no ?? bon.bonNo,
                      keperluan: bon.keperluan ?? "",
                      catatan:   bon.catatan   ?? "",
                      items:     (bon.items ?? []).map((it: any) => ({
                        stockItemId:   it.stock_item_id ?? it.stockItemId ?? 0,
                        namaBarang:    it.item_name     ?? it.namaBarang  ?? "",
                        satuan:        it.unit          ?? it.satuan      ?? "",
                        jumlahDiminta: it.qty_requested ?? it.jumlahDiminta ?? 1,
                        catatan:       it.notes         ?? it.catatan     ?? "",
                      })),
                    });
                    setRequesterTab("bon");
                  }
                }}
              />
            )}

            {requesterTab === "bon" && (
              <BonDigitalForm
                onSubmit={async (payload) => {
                  if (editingDraft) {
                    // Edit mode → PUT
                    await handleUpdateDraft(editingDraft.id, payload);
                    // Kalau simpan draft: tetap di form edit
                    // Kalau kirim: handleUpdateDraft sudah setEditingDraft(null)
                  } else {
                    // Buat baru → POST
                    await handleAddRequest(payload);
                  }
                }}
                currentUser={currentUser}
                initialData={editingDraft}
                onCancel={() => {
                  setEditingDraft(null);
                  setRequesterTab("monitoring");
                }}
              />
            )}

              {requesterTab === "monitoring" && (
                <BonMonitoringList 
                  bons={bons}
                  loading={requestsLoading}
                  error={requestsError}
                  onRefresh={loadData}
                  onEditDraft={(bon) => {
                    setEditingDraft(bon);
                    setRequesterTab("bon");
                  }}
                  onDeleteDraft={handleDeleteDraft}
                />
              )}

              {requesterTab === "stock" && <RequesterStockList />}

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

            {/* 🎯 TAMPILKAN KELOLA SITUS DI SUPERADMIN */}
            {superadminTab === "site_settings" && (
              <SiteSettings onSettingsUpdated={() => loadData()} />
            )}
            
            {superadminTab === "dashboard" && (
              <div className="space-y-6">
                {/* Task list quick peek */}
                <div className="space-y-6">
                  {/* Banner */}
                  <div className="relative bg-gradient-to-r from-[#f8faff] to-[#f0f4ff] rounded-2xl border border-indigo-50/50 p-6 shadow-sm overflow-hidden flex flex-col md:flex-row md:items-center gap-5">
                    {/* Glow effects */}
                    <div className="absolute right-0 top-0 w-64 h-64 bg-indigo-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
                    <div className="absolute left-0 bottom-0 w-48 h-48 bg-blue-500/5 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2 pointer-events-none"></div>
                    
                    <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white shadow-sm border border-slate-100 text-amber-500 relative z-10">
                      <Bell size={26} strokeWidth={2.5} />
                    </div>
                    <div className="relative z-10">
                      <h3 className="text-base font-extrabold text-slate-800 uppercase tracking-wide">
                        Antrian Pengajuan BON Masuk Baru
                      </h3>
                      <p className="text-xs font-medium text-slate-500 mt-1">
                        Daftar pengajuan bon masuk baru yang perlu Anda pantau dan proses.
                      </p>
                    </div>
                  </div>

                  <div className="space-y-4">
                    {requests.filter((r) => r.status === RequestStatus.DIAJUKAN).map((r) => {
                      const relatedBon = bons.find(b => (b.bonNo || (b as any).bon_no) === (r.bonNo || (r as any).bon_no));
                      return (
                      <div key={r.id} className="flex flex-col sm:flex-row justify-between sm:items-center bg-white border border-slate-100 border-l-4 border-l-amber-400 rounded-xl p-5 shadow-sm gap-4 transition-all duration-300 hover:shadow-md hover:-translate-y-1 hover:border-indigo-100">
                        <div>
                          <span className="font-mono text-xs font-bold text-slate-400 block uppercase tracking-wider mb-1">{r.bonNo}</span>
                          <span className="font-extrabold text-slate-800 text-base block">{r.itemName}</span>
                          
                          {relatedBon && (relatedBon.keperluan || relatedBon.catatan) && (
                            <div className="mt-2 mb-2 text-xs text-slate-600 bg-slate-50 p-2.5 rounded-md border border-slate-200/60 leading-relaxed">
                              {relatedBon.keperluan && (
                                <div><span className="font-bold text-slate-700">Keperluan:</span> {relatedBon.keperluan}</div>
                              )}
                              {relatedBon.catatan && (
                                <div className={relatedBon.keperluan ? "mt-1.5 pt-1.5 border-t border-slate-200/60" : ""}><span className="font-bold text-slate-700">Catatan BON:</span> {relatedBon.catatan}</div>
                              )}
                            </div>
                          )}

                          <div className="flex items-center gap-2 text-xs text-slate-500 font-medium mt-2">
                            <User size={12} className="text-slate-400" />
                            <span>Diminta oleh {r.requester}</span>
                            <span className="text-slate-300 mx-1">•</span>
                            <FileText size={12} className="text-slate-400" />
                            <span>{r.section}</span>
                          </div>
                        </div>
                        <button
                          onClick={() => {
                            setSuperadminTab("checking");
                          }}
                          className="bg-indigo-600 text-white px-5 py-2.5 rounded-xl font-bold flex items-center gap-2 hover:bg-indigo-700 transition-colors text-xs shadow-sm self-start sm:self-auto"
                        >
                          <Search size={14} />
                          <span>Proses Cek</span>
                          <ChevronRight size={14} />
                        </button>
                      </div>
                    );})}
                    {requests.filter((r) => r.status === RequestStatus.DIAJUKAN).length === 0 && (
                      <div className="text-center py-6 text-slate-400 text-xs font-semibold bg-white rounded-2xl border border-slate-200 shadow-sm">
                        Semua antrean BON digital telah diproses. Bersih!
                      </div>
                    )}
                  </div>
                </div>


                </div>
            )}

            {superadminTab === "checking" && (
              <StockChecking
                requests={requests}
                stockList={stock}
                bons={bons}
                onUpdateStatus={handleUpdateStatus}
                onDistribute={handleDistribute}
                onProcure={handleProcure}
                onCompleteProcurement={handleCompleteProcurement}
                onReject={handleReject}
                onCompletePartial={handleCompletePartial}
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
                onUnverifyReceipt={handleUnverifyReceipt}
              />
            )}

            {superadminTab === "report" && <ReportExport receipts={receipts} />}

            {superadminTab === "bon" && (
              <BonDigitalForm onSubmit={handleAddRequest} currentUser={currentUser} />
            )}

            {superadminTab === "monitoring" && (
              <BonMonitoringList 
                bons={bons}
                loading={requestsLoading}
                error={requestsError}
                onRefresh={loadData}
                onEditDraft={(bon) => {
                  setEditingDraft(bon);
                  setSuperadminTab("bon");
                }}
                onDeleteDraft={handleDeleteDraft}
              />
            )}

            {superadminTab === "stock_catalog" && <RequesterStockList />}

            {superadminTab === "history" && <HistoryLog logs={logs} />}
          </div>
        )}
            </main>
          </div>

      {/* Footer */}
      <footer className="mt-12 border-t border-slate-200 bg-white py-6 text-center text-xs font-medium text-slate-500 lg:pl-72">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <p>{renderBrandingTemplate(settings.footer_copyright, settings)}</p>
          <p className="mt-1 text-2xs text-slate-400 font-bold uppercase tracking-wider"><ColoredText text={settings.app_name || "SIPERBANG"} colorsJson={settings.app_name_colors} /> v1.1.0</p>
        </div>
      </footer>
    </div>
    )}

    {alertMsg && (
      <AlertDialog
        open
        title={alertMsg.title}
        message={alertMsg.message}
        variant={alertMsg.variant}
        onClose={() => setAlertMsg(null)}
      />
    )}
    </>
  );
}
