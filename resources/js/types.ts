/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

export enum UserRole {
  KETUA_TIM = "Ketua Tim Kerja",
  PETUGAS_PERSERDIAN = "Petugas Persediaan",
  SUPERADMIN = "Superadmin"
}

export interface UserAccount {
  id: string;
  name: string;
  role: UserRole;
  section?: string;
  username: string;
  status: "Aktif" | "Nonaktif";
}

export enum RequestStatus {
  DIAJUKAN = "Diajukan",
  DICEK = "Dicek",
  TERPENUHI = "Terpenuhi",
  TERPENUHI_SEBAGIAN = "Terpenuhi Sebagian",
  SIAP_DIDISTRIBUSIKAN = "Siap Didistribusikan",
  PERLU_PENGADAAN = "Perlu Pengadaan",
  DALAM_PENGADAAN = "Dalam Pengadaan",
  DITOLAK = "Ditolak",
  SELESAI = "Selesai"
}

export enum ProcurementMethod {
  VENDOR = "Pengadaan Vendor",
  SENDIRI = "Pengadaan Sendiri (Toko)"
}

export interface ItemRequest {
  id: string;
  bonNo: string;
  section: string; // bagian/seksi
  itemName: string;
  qtyRequested: number;
  qtyAvailable: number; // stok tersedia saat pengecekan
  qtyFulfilled: number; // jumlah terpenuhi
  qtyToProcure: number; // jumlah yang perlu diadakan
  stockAllocated: boolean; // apakah stok sudah dialokasikan
  unit: string; // satuan (rim, pak, buah, dll)
  status: RequestStatus;
  notes: string;
  date: string;
  requester: string;
  lastUpdated: string;
  stockItemId?: string;
  procurementMethod?: ProcurementMethod;
  vendorName?: string;
  distribution?: Distribution;
  procurements?: Procurement[];
}

export interface StockItem {
  id: string;
  category: string;
  code: string;
  name: string;
  qty: number;
  unit: string;
  lastUpdated: string;
}

export interface Distribution {
  id: string;
  itemRequestId: string;
  stockItemId: string;
  qtyDistributed: number;
  distributedBy: string;
  distributedAt: string;
  notes?: string;
}

export interface Procurement {
  id: string;
  itemRequestId: string;
  method: ProcurementMethod;
  vendorName?: string;
  storeName?: string;
  qtyProcured: number;
  unitPrice: number;
  totalPrice: number;
  isTaxed: boolean;
  taxRate: number;
  status: "Diproses" | "Diterima" | "Dibatalkan";
  invoiceNo?: string;
  bastName?: string;
  bastDate?: string;
  contractNo?: string;
  processedBy: string;
  procurementDate: string;
}

export interface ReceiptItem {
  id: string;
  name: string;
  qty: number;
  price: number;
  subtotal: number;
}

export interface ReceiptData {
  id: string;
  invoiceNo: string;
  storeName: string;
  date: string;
  isTaxed: boolean;
  taxRate: number; // e.g., 11
  subtotal: number;
  taxAmount: number;
  total: number;
  isVerified: boolean;
  items: ReceiptItem[];
  method: ProcurementMethod;
  bastName?: string; // BAST cukup memuat nama toko dan tanggal
  bastDate?: string;
  contractNo?: string; // untuk vendor
  status: "Menunggu Verifikasi" | "Dokumen Valid" | "Perlu Perbaikan";
}

export interface HistoryLog {
  id: string;
  timestamp: string;
  actor: string;
  action: string;
  details: string;
}
