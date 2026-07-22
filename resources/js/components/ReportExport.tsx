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
      const verifiedData = receiptsData.filter((r: any) => r.isVerified || r.is_verified);

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
        // Mode Per-Kuitansi
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

            // KONVERSI MUTLAK KE ANGKA (NUMBER)
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
        // Mode Rekap Gabungan (Template TA 2026)
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

          // Hitung total kuitansi secara mutlak
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
              row.getCell(3).value = ""; // BAST Dikosongkan
              row.getCell(4).value = ""; // Tanggal Buku Dikosongkan
              row.getCell(5).value = items.length === 1 
                ? { formula: `L${startRow}`, result: totalReceiptValue } 
                : { formula: `SUM(L${startRow}:L${endRow})`, result: totalReceiptValue };
              row.getCell(6).value = rc.invoiceNo || rc.invoice_no;
              row.getCell(7).value = rc.date;
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

        // BARIS TOTAL PALING BAWAH
        const totalRow = sheet.getRow(currentRow);
        totalRow.getCell(1).value = "TOTAL";
        sheet.mergeCells(`A${currentRow}:H${currentRow}`);

        // Merge Total Jumlah (I:J)
        sheet.mergeCells(`I${currentRow}:J${currentRow}`);
        totalRow.getCell(9).value = { formula: `SUM(I7:I${currentRow - 1})`, result: grandTotalQty };

        // Merge Total Nilai Akhir (K:L)
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

        <div className="bg-white rounded-lg border border-slate-200 p-4 mb-8 space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label className="block text-xs font-bold text-slate-500 mb-1.5">Format Tampilan Excel</label>
              <select
                value={exportMode}
                onChange={(e) => setExportMode(e.target.value as any)}
                className="w-full bg-white border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500"
              >
                <option value="rekap_ta">Rekap Gabungan (Template TA 2026)</option>
                <option value="per_kuitansi">Ekspor Per-Kuitansi (Format Teman)</option>
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