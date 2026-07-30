import React, { useState } from "react";
import { StockItem } from "../types";
import { Search, Filter, Package, AlertCircle, CheckCircle } from "lucide-react";

interface RequesterStockListProps {
  stock: StockItem[];
}

export function RequesterStockList({ stock }: RequesterStockListProps) {
  const [searchTerm, setSearchTerm] = useState("");
  const [filterStatus, setFilterStatus] = useState<"all" | "available" | "low" | "empty">("all");

  const filteredStock = stock.filter((item) => {
    const matchesSearch = item.name.toLowerCase().includes(searchTerm.toLowerCase()) || 
                          item.code.toLowerCase().includes(searchTerm.toLowerCase());
    
    let matchesFilter = true;
    if (filterStatus === "available") matchesFilter = item.qty > 10;
    else if (filterStatus === "low") matchesFilter = item.qty > 0 && item.qty <= 10;
    else if (filterStatus === "empty") matchesFilter = item.qty === 0;

    return matchesSearch && matchesFilter;
  });

  return (
    <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm space-y-6">
      <div className="flex items-center gap-3 mb-2">
        <div className="bg-amber-50 text-amber-600 p-2.5 rounded border border-amber-100">
          <Package size={18} />
        </div>
        <div>
          <h2 className="text-base font-extrabold text-slate-800 tracking-tight">Katalog Stok Gudang</h2>
          <p className="text-xs text-slate-500">
            Cari dan periksa ketersediaan barang di gudang sebelum mengajukan BON Digital.
          </p>
        </div>
      </div>

      <div className="flex flex-col sm:flex-row gap-4">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
          <input
            type="text"
            placeholder="Cari nama barang atau kode..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-9 pr-4 py-2 text-xs border border-slate-200 rounded-md focus:outline-none focus:ring-1 focus:ring-amber-500 bg-slate-50 text-slate-800 font-medium"
          />
        </div>
        
        <div className="flex items-center gap-2">
          <Filter size={16} className="text-slate-400" />
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value as any)}
            className="py-2 pl-3 pr-8 text-xs border border-slate-200 rounded-md focus:outline-none focus:ring-1 focus:ring-amber-500 bg-slate-50 text-slate-700 font-bold"
          >
            <option value="all">Semua Status</option>
            <option value="available">Stok Aman ({">"} 10)</option>
            <option value="low">Stok Menipis (1-10)</option>
            <option value="empty">Stok Kosong (0)</option>
          </select>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {filteredStock.length > 0 ? (
          filteredStock.map((item) => (
            <div key={item.id} className="border border-slate-200 rounded p-4 flex flex-col justify-between hover:border-amber-300 hover:shadow-xs transition-all">
              <div>
                <div className="flex justify-between items-start mb-2">
                  <span className="text-xs font-mono font-bold text-slate-400 bg-slate-50 px-2 py-0.5 rounded uppercase">{item.code}</span>
                  {item.qty > 10 ? (
                    <span className="flex items-center gap-1 text-xs font-extrabold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">
                      <CheckCircle size={10} /> Aman
                    </span>
                  ) : item.qty > 0 ? (
                    <span className="flex items-center gap-1 text-xs font-extrabold text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-100">
                      <AlertCircle size={10} /> Menipis
                    </span>
                  ) : (
                    <span className="flex items-center gap-1 text-xs font-extrabold text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-100">
                      <AlertCircle size={10} /> Kosong
                    </span>
                  )}
                </div>
                <h3 className="text-sm font-extrabold text-slate-800 line-clamp-2">{item.name}</h3>
                <p className="text-xs text-slate-500 mt-1">Update: {item.lastUpdated}</p>
              </div>
              <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                <span className="text-xs text-slate-400 font-bold uppercase tracking-wider">Ketersediaan</span>
                <div className="flex items-baseline gap-1">
                  <span className={`text-xl font-black ${item.qty === 0 ? 'text-rose-500' : 'text-slate-800'}`}>
                    {item.qty}
                  </span>
                  <span className="text-xs font-bold text-slate-500">{item.unit}</span>
                </div>
              </div>
            </div>
          ))
        ) : (
          <div className="col-span-full py-12 text-center bg-slate-50 border border-slate-200 rounded-lg">
            <Package size={32} className="mx-auto text-slate-300 mb-3" />
            <h3 className="text-sm font-bold text-slate-700">Tidak ada barang yang sesuai</h3>
            <p className="text-xs text-slate-500 mt-1">Coba sesuaikan kata kunci pencarian atau filter status.</p>
          </div>
        )}
      </div>
    </div>
  );
}
