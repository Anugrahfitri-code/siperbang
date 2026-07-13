import React, { useState } from "react";
import { ItemRequest, RequestStatus } from "../types";
import { ClipboardList, Plus, Sparkles, Send } from "lucide-react";



interface BonDigitalFormProps {
  onAddRequest: (newReq: Omit<ItemRequest, "id" | "bonNo" | "status" | "qtyAvailable" | "qtyFulfilled" | "lastUpdated">) => void;
  currentUser: string;
}

const COMMON_ITEMS = [
  { name: "Kertas HVS A4 80gr Sinar Dunia", unit: "Rim" },
  { name: "Spidol Boardmarker Snowman Black", unit: "Buah" },
  { name: "Tinta Printer Epson 003 Black", unit: "Botol" },
  { name: "Mouse Wireless Logitech M170", unit: "Buah" },
  { name: "Tisu Paseo Facial 250 Sheets", unit: "Pak" },
  { name: "Buku Catatan Agenda Komdigi", unit: "Buah" },
];

export const BonDigitalForm: React.FC<BonDigitalFormProps> = ({
  onAddRequest,
  currentUser,
}) => {
  const [section, setSection] = useState("Subbagian Tata Usaha");
  const [itemName, setItemName] = useState("");
  const [qty, setQty] = useState(5);
  const [unit, setUnit] = useState("Rim");
  const [notes, setNotes] = useState("");
  const [successMsg, setSuccessMsg] = useState("");

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!itemName.trim() || qty <= 0) return;

    onAddRequest({
      section,
      itemName,
      qtyRequested: Number(qty),
      unit,
      notes,
      requester: currentUser,
      date: new Date().toISOString().split("T")[0],
    });

    setItemName("");
    setQty(5);
    setNotes("");
    setSuccessMsg("BON Digital berhasil diajukan dan disimpan dalam sistem!");
    setTimeout(() => setSuccessMsg(""), 4000);
  };

  const selectQuickItem = (name: string, defaultUnit: string) => {
    setItemName(name);
    setUnit(defaultUnit);
  };

  return (
    <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
      <div className="flex items-center gap-3 mb-6">
        <div className="bg-amber-50 text-amber-600 p-2 rounded border border-amber-100">
          <ClipboardList size={18} />
        </div>
        <div>
          <h2 className="text-base font-extrabold text-slate-800 tracking-tight">BON Digital</h2>
          <p className="text-[11px] text-slate-500">
            Form pengajuan kebutuhan barang persediaan unit kerja
          </p>
        </div>
      </div>

      {successMsg && (
        <div className="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded p-3.5 mb-6 text-xs font-semibold animate-fade-in flex items-center gap-2">
          <Sparkles size={14} className="text-emerald-600 animate-bounce" />
          {successMsg}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {/* Bagian / Seksi */}
          <div>
            <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
              Bagian / Seksi / Unit Kerja
            </label>
            <select
              value={section}
              onChange={(e) => setSection(e.target.value)}
              className="w-full bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option>Subbagian Tata Usaha</option>
              <option>Seksi Penyelenggaraan</option>
              <option>Seksi Sumber Daya Manusia</option>
              <option>Subbagian Keuangan</option>
              <option>Tim Media Kreatif & Kehumasan</option>
            </select>
          </div>

          {/* Pengaju */}
          <div>
            <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
              Nama Pengaju (Ketua Tim)
            </label>
            <input
              type="text"
              value={currentUser}
              disabled
              className="w-full bg-slate-100 border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-400 cursor-not-allowed"
            />
          </div>
        </div>

        {/* Nama Barang */}
        <div>
          <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
            Nama Barang Persediaan
          </label>
          <input
            type="text"
            required
            placeholder="Ketik nama barang yang dibutuhkan..."
            value={itemName}
            onChange={(e) => setItemName(e.target.value)}
            className="w-full bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />

          {/* Quick Selection */}
          <div className="mt-3">
            <span className="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">
              Rekomendasi Cepat Barang:
            </span>
            <div className="flex flex-wrap gap-1.5">
              {COMMON_ITEMS.map((item, idx) => (
                <button
                  key={idx}
                  type="button"
                  onClick={() => selectQuickItem(item.name, item.unit)}
                  className={`text-[11px] px-2.5 py-1 rounded transition-all font-semibold ${
                    itemName === item.name
                      ? "bg-indigo-50 text-indigo-700 border border-indigo-200"
                      : "bg-white text-slate-600 border border-slate-200 hover:border-slate-300"
                  }`}
                >
                  {item.name}
                </button>
              ))}
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {/* Jumlah */}
          <div>
            <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
              Jumlah Diminta
            </label>
            <input
              type="number"
              min="1"
              required
              value={qty}
              onChange={(e) => setQty(Math.max(1, parseInt(e.target.value) || 0))}
              className="w-full bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
          </div>

          {/* Satuan */}
          <div>
            <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
              Satuan Barang
            </label>
            <select
              value={unit}
              onChange={(e) => setUnit(e.target.value)}
              className="w-full bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option>Rim</option>
              <option>Buah</option>
              <option>Botol</option>
              <option>Pak</option>
              <option>Kotak</option>
              <option>Lusin</option>
            </select>
          </div>
        </div>

        {/* Keterangan */}
        <div>
          <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
            Maksud & Keterangan Penggunaan
          </label>
          <textarea
            rows={3}
            placeholder="Tulis tujuan pengajuan barang ini..."
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            className="w-full bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />
        </div>

        {/* Submit */}
        <button
          type="submit"
          className="w-full bg-indigo-600 text-white hover:bg-indigo-700 font-bold py-2.5 px-4 rounded text-xs transition-all shadow-xs flex items-center justify-center gap-2"
        >
          <Send size={13} />
          Kirim BON Pengajuan Kebutuhan
        </button>
      </form>
    </div>
  );
};
