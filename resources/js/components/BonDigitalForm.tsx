import React, { useState } from "react";
import { ItemRequest } from "../types";
import {
  ClipboardList,
  Loader2,
  Sparkles,
  Send,
} from "lucide-react";

interface BonDigitalFormProps {
  onAddRequest: (
    newReq: Omit<
      ItemRequest,
      | "id"
      | "bonNo"
      | "status"
      | "qtyAvailable"
      | "qtyFulfilled"
      | "lastUpdated"
    >
  ) => Promise<void>;

  currentUser: string;
}

const COMMON_ITEMS = [
  {
    name: "Kertas HVS A4 80gr Sinar Dunia",
    unit: "Rim",
  },
  {
    name: "Spidol Boardmarker Snowman Black",
    unit: "Buah",
  },
  {
    name: "Tinta Printer Epson 003 Black",
    unit: "Botol",
  },
  {
    name: "Mouse Wireless Logitech M170",
    unit: "Buah",
  },
  {
    name: "Tisu Paseo Facial 250 Sheets",
    unit: "Pak",
  },
  {
    name: "Buku Catatan Agenda Komdigi",
    unit: "Buah",
  },
];

const getLocalDate = (): string => {
  const now = new Date();

  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(
    2,
    "0"
  );
  const day = String(now.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
};

export const BonDigitalForm: React.FC<
  BonDigitalFormProps
> = ({
  onAddRequest,
  currentUser,
}) => {
  const [section, setSection] = useState(
    "Subbagian Tata Usaha"
  );

  const [itemName, setItemName] = useState("");
  const [qty, setQty] = useState(5);
  const [unit, setUnit] = useState("Rim");
  const [notes, setNotes] = useState("");

  const [successMsg, setSuccessMsg] =
    useState("");

  const [errorMsg, setErrorMsg] =
    useState("");

  const [isSubmitting, setIsSubmitting] =
    useState(false);

  const handleSubmit = async (
    event: React.FormEvent<HTMLFormElement>
  ) => {
    event.preventDefault();

    if (
      !itemName.trim() ||
      qty <= 0 ||
      isSubmitting
    ) {
      return;
    }

    setSuccessMsg("");
    setErrorMsg("");
    setIsSubmitting(true);

    try {
      await onAddRequest({
        section,
        itemName: itemName.trim(),
        qtyRequested: Number(qty),
        unit,
        notes: notes.trim() || null ,
        requester: currentUser,
        date: getLocalDate(),
      });

      setItemName("");
      setQty(5);
      setUnit("Rim");
      setNotes("");

      setSuccessMsg(
        "BON Digital berhasil diajukan dan tersimpan di database."
      );

      window.setTimeout(() => {
        setSuccessMsg("");
      }, 4000);
    } catch (error) {
      console.error(
        "Gagal menyimpan BON Digital:",
        error
      );

      setErrorMsg(
        error instanceof Error
          ? error.message
          : "Pengajuan BON gagal disimpan. Silakan coba kembali."
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  const selectQuickItem = (
    name: string,
    defaultUnit: string
  ) => {
    setItemName(name);
    setUnit(defaultUnit);
    setSuccessMsg("");
    setErrorMsg("");
  };

  const handleQuantityChange = (
    event: React.ChangeEvent<HTMLInputElement>
  ) => {
    const value = Number(event.target.value);

    if (Number.isNaN(value)) {
      setQty(1);
      return;
    }

    setQty(Math.max(1, value));
  };

  return (
    <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
      <div className="flex items-center gap-3 mb-6">
        <div className="bg-amber-50 text-amber-600 p-2 rounded border border-amber-100">
          <ClipboardList size={18} />
        </div>

        <div>
          <h2 className="text-base font-extrabold text-slate-800 tracking-tight">
            BON Digital
          </h2>

          <p className="text-[11px] text-slate-500">
            Form pengajuan kebutuhan barang
            persediaan unit kerja
          </p>
        </div>
      </div>

      {successMsg && (
        <div className="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded p-3.5 mb-6 text-xs font-semibold animate-fade-in flex items-center gap-2">
          <Sparkles
            size={14}
            className="text-emerald-600 animate-bounce"
          />

          <span>{successMsg}</span>
        </div>
      )}

      {errorMsg && (
        <div className="bg-rose-50 border border-rose-100 text-rose-700 rounded p-3.5 mb-6 text-xs font-semibold">
          {errorMsg}
        </div>
      )}

      <form
        onSubmit={handleSubmit}
        className="space-y-4"
      >
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
              Bagian / Seksi / Unit Kerja
            </label>

            <select
              value={section}
              onChange={(event) =>
                setSection(event.target.value)
              }
              disabled={isSubmitting}
              className="w-full bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed"
            >
              <option value="Subbagian Tata Usaha">
                Subbagian Tata Usaha
              </option>

              <option value="Seksi Penyelenggaraan">
                Seksi Penyelenggaraan
              </option>

              <option value="Seksi Sumber Daya Manusia">
                Seksi Sumber Daya Manusia
              </option>

              <option value="Subbagian Keuangan">
                Subbagian Keuangan
              </option>

              <option value="Tim Media Kreatif & Kehumasan">
                Tim Media Kreatif &amp; Kehumasan
              </option>
            </select>
          </div>

          <div>
            <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
              Nama Pengaju
            </label>

            <input
              type="text"
              value={currentUser}
              disabled
              className="w-full bg-slate-100 border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-500 cursor-not-allowed"
            />
          </div>
        </div>

        <div>
          <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
            Nama Barang Persediaan
          </label>

          <input
            type="text"
            required
            disabled={isSubmitting}
            placeholder="Ketik nama barang yang dibutuhkan..."
            value={itemName}
            onChange={(event) => {
              setItemName(event.target.value);
              setSuccessMsg("");
              setErrorMsg("");
            }}
            className="w-full bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed"
          />

          <div className="mt-3">
            <span className="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">
              Rekomendasi Cepat Barang
            </span>

            <div className="flex flex-wrap gap-1.5">
              {COMMON_ITEMS.map((item) => (
                <button
                  key={item.name}
                  type="button"
                  disabled={isSubmitting}
                  onClick={() =>
                    selectQuickItem(
                      item.name,
                      item.unit
                    )
                  }
                  className={`text-[11px] px-2.5 py-1 rounded transition-all font-semibold disabled:opacity-60 disabled:cursor-not-allowed ${
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
          <div>
            <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
              Jumlah Diminta
            </label>

            <input
              type="number"
              min={1}
              required
              disabled={isSubmitting}
              value={qty}
              onChange={handleQuantityChange}
              className="w-full bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed"
            />
          </div>

          <div>
            <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
              Satuan Barang
            </label>

            <select
              value={unit}
              disabled={isSubmitting}
              onChange={(event) =>
                setUnit(event.target.value)
              }
              className="w-full bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed"
            >
              <option value="Rim">Rim</option>
              <option value="Buah">Buah</option>
              <option value="Botol">Botol</option>
              <option value="Pak">Pak</option>
              <option value="Kotak">Kotak</option>
              <option value="Lusin">Lusin</option>
            </select>
          </div>
        </div>

        <div>
          <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
            Maksud &amp; Keterangan Penggunaan
          </label>

          <textarea
            rows={3}
            disabled={isSubmitting}
            placeholder="Tulis tujuan pengajuan barang ini..."
            value={notes}
            onChange={(event) =>
              setNotes(event.target.value)
            }
            className="w-full bg-slate-50 border border-slate-200 rounded px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed"
          />
        </div>

        <button
          type="submit"
          disabled={
            isSubmitting ||
            !itemName.trim() ||
            qty <= 0
          }
          className="w-full bg-indigo-600 text-white hover:bg-indigo-700 disabled:bg-indigo-300 disabled:cursor-not-allowed font-bold py-2.5 px-4 rounded text-xs transition-all shadow-xs flex items-center justify-center gap-2"
        >
          {isSubmitting ? (
            <Loader2
              size={13}
              className="animate-spin"
            />
          ) : (
            <Send size={13} />
          )}

          {isSubmitting
            ? "Menyimpan BON..."
            : "Kirim BON Pengajuan Kebutuhan"}
        </button>
      </form>
    </div>
  );
};