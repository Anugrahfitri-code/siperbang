/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { StockItem, ItemRequest, RequestStatus, ReceiptData, ProcurementMethod } from "./types";

export const INITIAL_STOCK: StockItem[] = [
  {
    id: "st-01",
    category: "Alat Tulis Kantor (ATK)",
    code: "ATK-PAP-A4",
    name: "Kertas HVS A4 80gr Sinar Dunia",
    qty: 45,
    unit: "Rim",
    lastUpdated: "2026-07-01",
  },
  {
    id: "st-02",
    category: "Alat Tulis Kantor (ATK)",
    code: "ATK-PEN-PIL",
    name: "Balpoin Pilot G2 0.7 Black",
    qty: 120,
    unit: "Buah",
    lastUpdated: "2026-07-02",
  },
  {
    id: "st-03",
    category: "Alat Tulis Kantor (ATK)",
    code: "ATK-MKR-SND",
    name: "Spidol Boardmarker Snowman Black",
    qty: 8,
    unit: "Buah",
    lastUpdated: "2026-07-05",
  },
  {
    id: "st-04",
    category: "Peralatan Komputer",
    code: "KOM-MOU-LOG",
    name: "Mouse Wireless Logitech M170",
    qty: 15,
    unit: "Buah",
    lastUpdated: "2026-07-03",
  },
  {
    id: "st-05",
    category: "Peralatan Komputer",
    code: "KOM-INK-EPS",
    name: "Tinta Printer Epson 003 Black",
    qty: 3,
    unit: "Botol",
    lastUpdated: "2026-07-04",
  },
  {
    id: "st-06",
    category: "Rumah Tangga & Kebersihan",
    code: "RUM-TIS-PAS",
    name: "Tisu Paseo Facial 250 Sheets",
    qty: 35,
    unit: "Pak",
    lastUpdated: "2026-07-05",
  }
];

export const INITIAL_REQUESTS: ItemRequest[] = [
  {
    id: "req-01",
    bonNo: "BON/2026/07/001",
    section: "Subbagian Tata Usaha",
    itemName: "Kertas HVS A4 80gr Sinar Dunia",
    qtyRequested: 10,
    qtyAvailable: 45,
    qtyFulfilled: 10,
    unit: "Rim",
    status: RequestStatus.SELESAI,
    notes: "Kebutuhan kediklatan Pelatihan Kepemimpinan Pengawas.",
    date: "2026-07-05",
    requester: "Budi Santoso",
    lastUpdated: "2026-07-06",
  },
  {
    id: "req-02",
    bonNo: "BON/2026/07/002",
    section: "Seksi Penyelenggaraan",
    itemName: "Spidol Boardmarker Snowman Black",
    qtyRequested: 15,
    qtyAvailable: 8,
    qtyFulfilled: 8,
    unit: "Buah",
    status: RequestStatus.TERPENUHI_SEBAGIAN,
    notes: "Untuk ruangan kelas diklat teori lantai 2.",
    date: "2026-07-08",
    requester: "Siti Rahma",
    lastUpdated: "2026-07-09",
  },
  {
    id: "req-03",
    bonNo: "BON/2026/07/003",
    section: "Seksi Sumber Daya Manusia",
    itemName: "Tinta Printer Epson 003 Black",
    qtyRequested: 5,
    qtyAvailable: 3,
    qtyFulfilled: 0,
    unit: "Botol",
    status: RequestStatus.PERLU_PENGADAAN,
    notes: "Printer cetak sertifikat kelulusan peserta diklat.",
    date: "2026-07-10",
    requester: "Andi Wijaya",
    lastUpdated: "2026-07-10",
  },
  {
    id: "req-04",
    bonNo: "BON/2026/07/004",
    section: "Subbagian Keuangan",
    itemName: "Mouse Wireless Logitech M170",
    qtyRequested: 2,
    qtyAvailable: 15,
    qtyFulfilled: 0,
    unit: "Buah",
    status: RequestStatus.DIAJUKAN,
    notes: "Ganti mouse staf yang rusak double click.",
    date: "2026-07-11",
    requester: "Diana Putri",
    lastUpdated: "2026-07-11",
  }
];

export interface SampleReceiptPayload {
  name: string;
  description: string;
  imageUrl: string; // fallback icon or type
  invoiceNo: string;
  storeName: string;
  date: string;
  isTaxed: boolean;
  taxRate: number;
  items: { name: string; qty: number; price: number }[];
  method: ProcurementMethod;
}

export const SAMPLE_RECEIPTS: SampleReceiptPayload[] = [
  {
    name: "Struk Toko ATK Cahaya Baru (PPN 11%)",
    description: "Pembelian ATK umum dengan tarif PPN standar instansi pemerintah 11%",
    imageUrl: "receipt_atk",
    invoiceNo: "INV/2026/07/1109",
    storeName: "Toko ATK Cahaya Baru Makassar",
    date: "2026-07-10",
    isTaxed: true,
    taxRate: 11,
    method: ProcurementMethod.SENDIRI,
    items: [
      { name: "Spidol Boardmarker Snowman Black", qty: 10, price: 12500 },
      { name: "Penghapus Papan Tulis Magnetik", qty: 5, price: 18000 },
      { name: "Kertas Double Folio Bergaris", qty: 3, price: 45000 }
    ]
  },
  {
    name: "Nota Toko Buku Nusantara (Tanpa Pajak / 0%)",
    description: "Kuitansi bebas pajak karena merupakan toko kecil / non-PKP",
    imageUrl: "receipt_book",
    invoiceNo: "NTA-88271",
    storeName: "Toko Buku & ATK Nusantara",
    date: "2026-07-09",
    isTaxed: false,
    taxRate: 0,
    method: ProcurementMethod.SENDIRI,
    items: [
      { name: "Tinta Printer Epson 003 Black", qty: 5, price: 85000 },
      { name: "Kertas HVS A4 80gr Sinar Dunia", qty: 5, price: 54000 }
    ]
  },
  {
    name: "Kuitansi Kontrak CV Tech Solution (Pajak Khusus 12%)",
    description: "Pengadaan hardware lewat vendor dengan penyesuaian PPN baru 12%",
    imageUrl: "receipt_contract",
    invoiceNo: "KUIT/TS-092",
    storeName: "CV Tech Solution Makassar",
    date: "2026-07-08",
    isTaxed: true,
    taxRate: 12,
    method: ProcurementMethod.VENDOR,
    items: [
      { name: "Mouse Wireless Logitech M170", qty: 5, price: 150000 },
      { name: "Keyboard USB Logitech K120", qty: 5, price: 120000 }
    ]
  }
];
