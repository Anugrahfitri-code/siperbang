import React, { useState } from "react";
import ExcelJS from "exceljs";
import { ReceiptData } from "../types";
import { FileSpreadsheet, Search, DownloadCloud, Check, RefreshCw } from "lucide-react";
import { AlertDialog } from "./AlertDialog";

interface ReportExportProps {
  receipts: ReceiptData[];
}

export const ReportExport: React.FC<ReportExportProps> = ({ receipts }) => {
  const [filterMonth, setFilterMonth] = useState("All");
  const [alertMsg, setAlertMsg] = useState<{ title: string; message: string } | null>(null);
  const [filterYear, setFilterYear] = useState("2026");
  const [searchQuery, setSearchQuery] = useState("");
  const [isAnnualRecap, setIsAnnualRecap] = useState(false);
  const [exportMode, setExportMode] = useState<"rekap_ta" | "per_kuitansi">("rekap_ta");
  const [isExporting, setIsExporting] = useState(false);
  const [exportSuccess, setExportSuccess] = useState(false);

  // 1. DEKLARASIKAN HELPER DI PALING ATAS AGAR SIAP DIPAKAI
  const formatDate = (dateStr: string | null | undefined) => {
    if (!dateStr || dateStr === "-") return "-";
    try {
      const d = new Date(dateStr);
      if (isNaN(d.getTime())) return dateStr;
      const day = String(d.getDate()).padStart(2, "0");
      const month = String(d.getMonth() + 1).padStart(2, "0");
      const year = d.getFullYear();
      return `${day}/${month}/${year}`;
    } catch {
      return dateStr;
    }
  };

  const formatIDR = (num: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(num);
  };

  const months = [
    { value: "All", label: "Semua Bulan" },
    { value: "01", label: "Januari" },
    { value: "02", label: "Februari" },
    { value: "03", label: "Maret" },
    { value: "04", label: "April" },
    { value: "05", label: "Mei" },
    { value: "06", label: "Juni" },
    { value: "07", label: "Juli" },
    { value: "08", label: "Agustus" },
    { value: "09", label: "September" },
    { value: "10", label: "Oktober" },
    { value: "11", label: "November" },
    { value: "12", label: "Desember" },
  ];

  // 2. BARU PANGGIL DATA & REPORT ROWS DI SINI
  const verifiedReceipts = receipts.filter((r) => r.isVerified || (r as any).is_verified);

  const filteredReceipts = verifiedReceipts.filter((r) => {
    if (filterYear !== "All") {
      const year = r.date.split("-")[0];
      if (year !== filterYear) return false;
    }

    if (!isAnnualRecap && filterMonth !== "All") {
      const month = r.date.split("-")[1];
      if (month !== filterMonth) return false;
    }

    if (searchQuery.trim() !== "") {
      const query = searchQuery.toLowerCase();
      const matchesStore = r.storeName.toLowerCase().includes(query);
      const matchesInvoice = r.invoiceNo.toLowerCase().includes(query);
      const matchesItem = r.items.some(
        (it) =>
          it.name.toLowerCase().includes(query) ||
          (it.inventoryCode && it.inventoryCode.toLowerCase().includes(query)) ||
          (it.unit && it.unit.toLowerCase().includes(query))
      );
      return matchesStore || matchesInvoice || matchesItem;
    }

    return true;
  });

  const reportRows = filteredReceipts.flatMap((rc) =>
    rc.items.map((it) => {
      const qty = Number(it.qty) || 0;
      const price = Number(it.price) || 0;
      const subtotal = qty * price;
      const taxAmount = rc.isTaxed ? Math.round(subtotal * (rc.taxRate / 100)) : 0;
      const total = subtotal + taxAmount;

      return {
        invoiceNo: rc.invoiceNo,
        date: formatDate(rc.date), // Sekarang aman karena formatDate sudah ada!
        storeName: rc.storeName,
        inventoryCode: it.inventoryCode,
        itemName: it.name,
        qty: qty,
        unit: it.unit,
        price: price,
        subtotal: subtotal,
        taxAmount: taxAmount,
        total: total,
        method: rc.method,
        bastName: isAnnualRecap ? "" : rc.bastName || "-",
        bastDate: isAnnualRecap ? "" : formatDate(rc.bastDate) || "-",
        bookDate: isAnnualRecap ? "" : formatDate(rc.date),
      };
    })
  );

  const handleRealExport = async () => {
    try {
      setIsExporting(true);
      setExportSuccess(false);

      const queryParams = new URLSearchParams({
        month: isAnnualRecap ? "All" : filterMonth,
        year: filterYear,
        search: searchQuery,
      });

      const response = await fetch(`/api/receipts?${queryParams.toString()}`);
      if (!response.ok) throw new Error("Gagal mengambil data dari server.");

      const receiptsData = await response.json();
      let verifiedData = receiptsData.filter((r: any) => r.isVerified || r.is_verified);

      verifiedData = verifiedData.filter((r: any) => {
        const rDate = r.date || "";
        if (filterYear !== "All") {
          const year = rDate.split("-")[0];
          if (year !== filterYear) return false;
        }
        if (!isAnnualRecap && filterMonth !== "All") {
          const month = rDate.split("-")[1];
          if (month !== filterMonth) return false;
        }
        if (searchQuery.trim() !== "") {
          const query = searchQuery.toLowerCase();
          const matchesStore = (r.storeName || r.store_name || "").toLowerCase().includes(query);
          const matchesInvoice = (r.invoiceNo || r.invoice_no || "").toLowerCase().includes(query);
          const matchesItem = (r.items || []).some(
            (it: any) =>
              (it.name || "").toLowerCase().includes(query) ||
              (it.inventoryCode || it.inventory_code || "").toLowerCase().includes(query) ||
              (it.unit || "").toLowerCase().includes(query)
          );
          return matchesStore || matchesInvoice || matchesItem;
        }
        return true;
      });

      if (verifiedData.length === 0) {
        setAlertMsg({ title: "Data Kosong", message: "Tidak ada data kuitansi tervalidasi untuk diekspor." });
        return;
      }

      const workbook = new ExcelJS.Workbook();
      const thinBorder: Partial<ExcelJS.Borders> = {
        top: { style: "thin" },
        left: { style: "thin" },
        bottom: { style: "thin" },
        right: { style: "thin" },
      };

      if (exportMode === "per_kuitansi") {
        verifiedData.forEach((rc: any, idx: number) => {
          const storeName = rc.storeName || rc.store_name || "SUPPLIER";
          const sheetName = `${idx + 1}_${storeName.substring(0, 20).replace(/[/\\?*:[\]]/g, "")}`;
          const sheet = workbook.addWorksheet(sheetName);

          const supplierCell = sheet.getCell("A2");
          supplierCell.value = `SUPPLIER : ${storeName.toUpperCase()}`;
          supplierCell.font = { name: "Arial", size: 11, bold: true };

          const headers = ["No", "Kode Persediaan", "Nama Barang", "Jumlah", "Satuan", "Harga Satuan", "Total"];
          const headerRow = sheet.getRow(4);
          headerRow.values = headers;
          headerRow.font = { name: "Arial", size: 11, bold: true };
          headerRow.height = 24;

          headerRow.eachCell((cell) => {
            cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFCCCCCC" } };
            cell.alignment = { horizontal: "center", vertical: "middle" };
            cell.border = thinBorder;
          });

          const items = rc.items || [];
          let receiptTotal = 0;

          items.forEach((it: any, itemIdx: number) => {
            const rowNum = 5 + itemIdx;
            const row = sheet.getRow(rowNum);

            const qty = Number(it.qty) || 0;
            const price = Number(it.price) || 0;
            const subtotal = qty * price;
            receiptTotal += subtotal;

            row.getCell(1).value = itemIdx + 1;
            row.getCell(2).value = it.inventoryCode || it.inventory_code || "-";
            row.getCell(3).value = it.name || it.itemName;
            row.getCell(4).value = qty;
            row.getCell(5).value = (it.unit || "PCS").toUpperCase();
            row.getCell(6).value = price;
            row.getCell(7).value = { formula: `D${rowNum}*F${rowNum}`, result: subtotal };

            row.getCell(1).alignment = { horizontal: "center" };
            row.getCell(2).alignment = { horizontal: "center" };
            row.getCell(4).alignment = { horizontal: "center" };
            row.getCell(5).alignment = { horizontal: "center" };

            row.getCell(6).numFmt = '"Rp "#,##0';
            row.getCell(7).numFmt = '"Rp "#,##0';

            row.eachCell((cell) => {
              cell.font = { name: "Arial", size: 11 };
              cell.border = thinBorder;
            });
          });

          const totalRowNum = 5 + items.length;
          const totalRow = sheet.getRow(totalRowNum);
          totalRow.getCell(7).value = { formula: `SUM(G5:G${totalRowNum - 1})`, result: receiptTotal };
          totalRow.getCell(7).font = { name: "Arial", size: 11, bold: true };
          totalRow.getCell(7).fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFCCCCCC" } };
          totalRow.getCell(7).numFmt = '"Rp "#,##0';
          totalRow.getCell(7).border = thinBorder;

          sheet.columns = [
            { width: 6 }, { width: 18 }, { width: 35 },
            { width: 10 }, { width: 12 }, { width: 16 }, { width: 18 },
          ];
        });

      } else {
        const sheet = workbook.addWorksheet("REKAP");

        sheet.getCell("A1").value = "REKAP BELANJA PERSEDIAAN BARANG HABIS PAKAI";
        sheet.getCell("A2").value = "BBLSDM KOMDIGI MAKASSAR";
        sheet.getCell("A3").value = `TA ${filterYear}`;

        sheet.mergeCells("A1:L1");
        sheet.mergeCells("A2:L2");
        sheet.mergeCells("A3:L3");

        ["A1", "A2", "A3"].forEach((cellId, i) => {
          const cell = sheet.getCell(cellId);
          cell.font = { name: "Aptos Narrow", size: i === 0 ? 14 : 11, bold: true };
          cell.alignment = { horizontal: "center", vertical: "middle" };
        });

        sheet.getRow(5).values = ["No", "Nama Supplier", "BAST", "Tanggal Buku", "Total Nilai", "Kwitansi", "", "Nama Barang", "Jumlah", "Satuan", "Harga Satuan", "Total Harga"];
        sheet.getRow(6).values = ["", "", "", "", "", "Nomor", "Tanggal", "", "", "", "", ""];

        sheet.mergeCells("A5:A6");
        sheet.mergeCells("B5:B6");
        sheet.mergeCells("C5:C6");
        sheet.mergeCells("D5:D6");
        sheet.mergeCells("E5:E6");
        sheet.mergeCells("F5:G5");
        sheet.mergeCells("H5:H6");
        sheet.mergeCells("I5:I6");
        sheet.mergeCells("J5:J6");
        sheet.mergeCells("K5:K6");
        sheet.mergeCells("L5:L6");

        [5, 6].forEach((r) => {
          const row = sheet.getRow(r);
          row.height = 20;
          row.eachCell((cell) => {
            cell.font = { name: "Aptos Narrow", size: 10, bold: true };
            cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFE0E0E0" } };
            cell.alignment = { horizontal: "center", vertical: "middle", wrapText: true };
            cell.border = thinBorder;
          });
        });

        let currentRow = 7;
        let no = 1;
        let grandTotalQty = 0;
        let grandTotalAmount = 0;

        verifiedData.forEach((rc: any) => {
          const items = rc.items || [];
          if (items.length === 0) return;

          const startRow = currentRow;
          const endRow = startRow + items.length - 1;

          const totalReceiptValue = items.reduce((acc: number, it: any) => {
            return acc + ((Number(it.qty) || 0) * (Number(it.price) || 0));
          }, 0);

          items.forEach((it: any, idx: number) => {
            const rowNum = startRow + idx;
            const row = sheet.getRow(rowNum);

            const qty = Number(it.qty) || 0;
            const price = Number(it.price) || 0;
            const subtotal = qty * price;

            grandTotalQty += qty;
            grandTotalAmount += subtotal;

            if (idx === 0) {
              row.getCell(1).value = no;
              row.getCell(2).value = rc.storeName || rc.store_name;
              row.getCell(3).value = ""; // BAST Dikosongkan untuk SAKTI
              row.getCell(4).value = ""; // Tanggal Buku Dikosongkan untuk SAKTI
              row.getCell(5).value = items.length === 1 
                ? { formula: `L${startRow}`, result: totalReceiptValue } 
                : { formula: `SUM(L${startRow}:L${endRow})`, result: totalReceiptValue };
              row.getCell(6).value = rc.invoiceNo || rc.invoice_no;
              row.getCell(7).value = formatDate(rc.date);
            }

            row.getCell(8).value = it.name || it.itemName;
            row.getCell(9).value = qty;
            row.getCell(10).value = (it.unit || "Pak").toUpperCase();
            row.getCell(11).value = price;
            row.getCell(12).value = { formula: `I${rowNum}*K${rowNum}`, result: subtotal };

            row.getCell(1).alignment = { horizontal: "center", vertical: "middle" };
            row.getCell(7).alignment = { horizontal: "center", vertical: "middle" };
            row.getCell(9).alignment = { horizontal: "center", vertical: "middle" };
            row.getCell(10).alignment = { horizontal: "center", vertical: "middle" };

            row.getCell(5).numFmt = '"Rp "#,##0';
            row.getCell(11).numFmt = '"Rp "#,##0';
            row.getCell(12).numFmt = '"Rp "#,##0';

            row.eachCell((cell) => {
              cell.font = { name: "Aptos Narrow", size: 10 };
              cell.border = thinBorder;
            });
          });

          if (items.length > 1) {
            sheet.mergeCells(`A${startRow}:A${endRow}`);
            sheet.mergeCells(`B${startRow}:B${endRow}`);
            sheet.mergeCells(`C${startRow}:C${endRow}`);
            sheet.mergeCells(`D${startRow}:D${endRow}`);
            sheet.mergeCells(`E${startRow}:E${endRow}`);
            sheet.mergeCells(`F${startRow}:F${endRow}`);
            sheet.mergeCells(`G${startRow}:G${endRow}`);
          }

          no++;
          currentRow = endRow + 1;
        });

        const totalRow = sheet.getRow(currentRow);
        totalRow.getCell(1).value = "TOTAL";
        sheet.mergeCells(`A${currentRow}:H${currentRow}`);

        sheet.mergeCells(`I${currentRow}:J${currentRow}`);
        totalRow.getCell(9).value = { formula: `SUM(I7:I${currentRow - 1})`, result: grandTotalQty };

        sheet.mergeCells(`K${currentRow}:L${currentRow}`);
        totalRow.getCell(11).value = { formula: `SUM(L7:L${currentRow - 1})`, result: grandTotalAmount };

        totalRow.getCell(1).font = { name: "Aptos Narrow", size: 10, bold: true };
        totalRow.getCell(1).alignment = { horizontal: "center", vertical: "middle" };
        totalRow.getCell(9).font = { name: "Aptos Narrow", size: 10, bold: true };
        totalRow.getCell(9).alignment = { horizontal: "center", vertical: "middle" };
        totalRow.getCell(11).font = { name: "Aptos Narrow", size: 10, bold: true };
        totalRow.getCell(11).alignment = { horizontal: "right", vertical: "middle" };
        totalRow.getCell(11).numFmt = '"Rp "#,##0';

        totalRow.eachCell((cell) => {
          cell.border = thinBorder;
        });

        sheet.columns = [
          { width: 6 }, { width: 25 }, { width: 20 }, { width: 15 },
          { width: 18 }, { width: 18 }, { width: 14 }, { width: 35 },
          { width: 10 }, { width: 10 }, { width: 16 }, { width: 18 }
        ];
      }

      const buffer = await workbook.xlsx.writeBuffer();
      const blob = new Blob([buffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = exportMode === "per_kuitansi" 
        ? `Ekspor_Kuitansi_${filterYear}.xlsx`
        : `Rekap_Belanja_Persediaan_TA_${filterYear}.xlsx`;
      
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);

      setExportSuccess(true);
      setTimeout(() => setExportSuccess(false), 4000);

    } catch (error) {
      console.error("Proses ekspor gagal:", error);
      setAlertMsg({ title: "Gagal Ekspor", message: "Terjadi kesalahan saat membuat berkas Excel." });
    } finally {
      setIsExporting(false);
    }
  };

  return (
    <>
      <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        {/* Header Modul */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 border-b border-slate-100 pb-6">
          <div className="flex items-center gap-4">
            <div className="flex size-14 shrink-0 items-center justify-center rounded-xl border bg-emerald-50 text-emerald-600 border-emerald-100">
              <FileSpreadsheet size={24} />
            </div>
            <div>
              <h2 className="text-lg font-semibold leading-7 text-slate-900">Rekap Laporan & Export Excel</h2>
              <p className="text-sm font-normal leading-5 text-slate-500 mt-0.5">
                Saring laporan kuitansi tervalidasi dan unduh spreadsheet Excel berformat rapi
              </p>
            </div>
          </div>

          <button
            onClick={handleRealExport}
            disabled={isExporting}
            className="px-5 py-2.5 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-sm bg-blue-600 hover:bg-blue-700 text-white"
          >
            {isExporting ? (
              <>
                <RefreshCw className="animate-spin" size={14} />
                Mengekspor...
              </>
            ) : (
              <>
                <DownloadCloud size={14} />
                Ekspor Rekap Laporan (.xlsx)
              </>
            )}
          </button>
        </div>

        {exportSuccess && (
          <div className="bg-emerald-50 border border-emerald-150 text-emerald-800 rounded p-3.5 mb-6 text-xs font-semibold animate-fade-in flex items-center gap-2">
            <Check size={14} className="text-emerald-600" />
            Ekspor berhasil! Berkas Excel berformat rapi telah diunduh ke komputer Anda.
          </div>
        )}

        {/* Filter Bar */}
        <div className="bg-white rounded-lg border border-slate-200 p-4 mb-8 space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label className="block text-xs font-bold text-slate-500 mb-1.5">Format Tampilan Excel</label>
              <select
                value={exportMode}
                onChange={(e) => setExportMode(e.target.value as any)}
                className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500"
              >
                <option value="rekap_ta">Rekap Gabungan</option>
                <option value="per_kuitansi">Ekspor Per-Kuitansi</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-500 mb-1.5">Saring Bulan</label>
              <select
                disabled={isAnnualRecap}
                value={filterMonth}
                onChange={(e) => setFilterMonth(e.target.value)}
                className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:bg-slate-50 disabled:text-slate-400"
              >
                {months.map((m) => (
                  <option key={m.value} value={m.value}>{m.label}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-500 mb-1.5">Saring Tahun</label>
              <select
                value={filterYear}
                onChange={(e) => setFilterYear(e.target.value)}
                className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500"
              >
                <option value="2026">2026</option>
                <option value="All">Semua Tahun</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-500 mb-1.5">Cari Kuitansi / Barang</label>
              <div className="relative">
                <Search className="absolute left-3 top-2.5 text-slate-400" size={14} />
                <input
                  type="text"
                  placeholder="Cari toko, invoice, barang..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="w-full bg-white border border-slate-200 rounded pl-9 pr-4 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>
            </div>
          </div>
        </div>


        <div className="mt-4">
          <div className="flex items-center gap-3 mb-4">
            <div className="flex size-10 shrink-0 items-center justify-center rounded-lg border bg-emerald-50 text-emerald-600 border-emerald-100">
              <FileSpreadsheet size={20} />
            </div>
            <h3 className="text-sm font-extrabold text-slate-800 tracking-wide">
              Pratinjau Spreadsheet Excel
            </h3>
            <span className="text-slate-300">|</span>
            <span className="text-sm font-medium text-slate-500">
              {reportRows.length} baris data terpilih
            </span>
          </div>

          <div className="overflow-x-auto border border-slate-200 rounded-lg shadow-xs">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-slate-50 text-slate-700 text-xs font-bold uppercase tracking-wider border-b border-slate-200">
                  <th className="px-4 py-3">No Nota</th>
                  <th className="px-4 py-3">Tanggal</th>
                  <th className="px-4 py-3">Nama Toko</th>
                  <th className="px-4 py-3">Kode Persediaan</th>
                  <th className="px-4 py-3">Nama Barang</th>
                  <th className="px-4 py-3 text-center">Jumlah</th>
                  <th className="px-4 py-3">Satuan</th>
                  <th className="px-4 py-3 text-right">Harga Satuan</th>
                  <th className="px-4 py-3 text-right">Subtotal</th>
                  <th className="px-4 py-3 text-right">PPN (Pajak)</th>
                  <th className="px-4 py-3 text-right">Total</th>
                  <th className="px-4 py-3">Metode</th>
                  <th className="px-4 py-3">BAST (Nama)</th>
                  <th className="px-4 py-3">BAST (Tgl)</th>
                  <th className="px-4 py-3">Tgl Buku</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {reportRows.map((row, idx) => (
                  <tr key={idx} className="hover:bg-slate-50/50 transition-colors text-xs font-mono">
                    <td className="px-4 py-3 text-slate-600 font-bold">{row.invoiceNo}</td>
                    <td className="px-4 py-3 text-slate-500 font-sans">{row.date}</td>
                    <td className="px-4 py-3 font-sans font-bold text-slate-800">{row.storeName}</td>
                    <td className="px-4 py-3 font-mono font-bold text-indigo-700">{row.inventoryCode || "-"}</td>
                    <td className="px-4 py-3 font-sans font-medium text-slate-700">{row.itemName}</td>
                    <td className="px-4 py-3 text-center font-sans font-bold text-slate-800">{row.qty}</td>
                    <td className="px-4 py-3 font-sans font-semibold text-slate-700">{row.unit || "-"}</td>
                    <td className="px-4 py-3 text-right text-slate-600">{formatIDR(row.price)}</td>
                    <td className="px-4 py-3 text-right text-slate-700 font-semibold">{formatIDR(row.subtotal)}</td>
                    <td className="px-4 py-3 text-right text-indigo-700 font-semibold">
                      {row.taxAmount > 0 ? formatIDR(row.taxAmount) : "-"}
                    </td>
                    <td className="px-4 py-3 text-right text-slate-900 font-extrabold">{formatIDR(row.total)}</td>
                    <td className="px-4 py-3 font-sans font-semibold text-slate-600">{row.method || "-"}</td>
                    <td className="px-4 py-3 font-sans text-slate-400">
                      {row.bastName ? (
                        <span className="text-slate-600 font-semibold">{row.bastName}</span>
                      ) : (
                        <span className="text-slate-300 italic">NIL (Kosong)</span>
                      )}
                    </td>
                    <td className="px-4 py-3 font-sans text-slate-400">
                      {row.bastDate ? (
                        <span className="text-slate-600 font-semibold">{row.bastDate}</span>
                      ) : (
                        <span className="text-slate-300 italic">NIL (Kosong)</span>
                      )}
                    </td>
                    <td className="px-4 py-3 font-sans text-slate-400">
                      {row.bookDate ? (
                        <span className="text-slate-600 font-semibold">{row.bookDate}</span>
                      ) : (
                        <span className="text-slate-300 italic">NIL (Kosong)</span>
                      )}
                    </td>
                  </tr>
                ))}
                {reportRows.length === 0 && (
                  <tr>
                    <td colSpan={15} className="py-16">
                      <div className="flex flex-col items-center justify-center text-center">
                        <div className="relative mb-4">
                          <FileSpreadsheet size={48} className="text-slate-300" strokeWidth={1} />
                          <Search size={24} className="text-slate-400 absolute -bottom-2 -right-2 bg-white rounded-full p-0.5" strokeWidth={2} />
                        </div>
                        <p className="text-sm font-medium text-slate-500">
                          Belum ada data kuitansi tervalidasi yang cocok dengan kriteria saringan Anda.
                        </p>
                      </div>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {alertMsg && (
        <AlertDialog
          open
          title={alertMsg.title}
          message={alertMsg.message}
          variant="danger"
          onClose={() => setAlertMsg(null)}
        />
      )}
    </>
  );
};