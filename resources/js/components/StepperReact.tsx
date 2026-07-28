import React, { useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { Check, ChevronRight, CheckCircle2, AlertCircle, XCircle, ArrowLeft, Save, Search, Trash2, ShieldCheck, ChevronDown, ListChecks } from "lucide-react";
import { AlertDialog } from "./AlertDialog";

interface StepperProps {
  batchId: number | null;
  onClose: () => void;
}

export const StepperReact: React.FC<StepperProps> = ({ batchId, onClose }) => {
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [data, setData] = useState<any>(null);
  const [error, setError] = useState<string | null>(null);
  const [alertDialog, setAlertDialog] = useState<{ title: string; message: string; variant: "danger" | "success"; onConfirm?: () => void } | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  
  // States for step 3 verifications
  const [verifications, setVerifications] = useState<Record<number, { action: string, kode_persediaan?: string }>>({});
  
  // Search states for dropdowns
  const [searchTerms, setSearchTerms] = useState<Record<number, string>>({});
  const [dropdownOpen, setDropdownOpen] = useState<Record<number, boolean>>({});

  const fetchBatch = () => {
    if (!batchId) return;
    setLoading(true);
    setError(null);
    fetch(`/api/stok-upload/${batchId}/stepper-api`)
      .then(res => res.json())
      .then(resData => {
        if (resData.error) {
          setError(resData.error);
        } else {
          setData(resData);
          // Initialize verifications state
          const initialVerifs: any = {};
          resData.details.all.forEach((row: any) => {
            if (row.status_verification === 'Pending') {
               initialVerifs[row.id] = { action: 'Setuju' };
            } else {
               initialVerifs[row.id] = { 
                 action: row.status_verification, 
                 kode_persediaan: row.verified_kode_persediaan || row.kode_persediaan_excel 
               };
            }
          });
          setVerifications(initialVerifs);
        }
      })
      .catch(err => setError("Gagal mengambil data batch."))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    fetchBatch();
  }, [batchId]);

  if (!batchId) {
    return (
      <div className="bg-white border border-slate-200 rounded-xl p-12 shadow-sm text-center animate-fade-in flex flex-col items-center justify-center min-h-[400px]">
        <div className="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 mb-5 border border-slate-100 shadow-inner">
          <ListChecks size={36} strokeWidth={1.5} />
        </div>
        <h2 className="text-xl font-extrabold text-slate-800 tracking-tight">Menunggu Data Verifikasi</h2>
        <p className="text-sm text-slate-500 mt-2 max-w-md mx-auto leading-relaxed">
          Anda belum memilih data apapun. Silakan melakukan <strong>Upload Excel</strong> terlebih dahulu, atau pilih dokumen yang ingin diverifikasi dari tab <strong>Riwayat Upload</strong>.
        </p>
      </div>
    );
  }

  if (loading && !data) {
    return (
      <div className="bg-white border border-slate-200 rounded-xl p-16 shadow-sm text-center">
        <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600 mx-auto"></div>
        <p className="mt-4 text-sm font-semibold text-slate-500">Memuat data batch...</p>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="bg-white border border-rose-200 rounded-xl p-8 shadow-sm text-center">
        <AlertCircle className="w-12 h-12 text-rose-500 mx-auto mb-3" />
        <h2 className="text-lg font-bold text-slate-800">Gagal Memuat</h2>
        <p className="text-sm text-slate-500 mt-1">{error || "Terjadi kesalahan sistem"}</p>
        <button onClick={onClose} className="mt-5 px-5 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-200">
          Kembali
        </button>
      </div>
    );
  }

  const { batch, step, masterCodes, stats, details } = data;
  
  const stepLabels = ['Upload File', 'Pemeriksaan Data', 'Verifikasi Kode', 'Review & Finalisasi'];

  const handleSaveVerifications = () => {
    const items = Object.entries(verifications).map(([detail_id, val]) => ({
      detail_id: parseInt(detail_id),
      action: val.action,
      kode_persediaan: val.kode_persediaan
    }));

    setSubmitting(true);
    fetch(`/api/stok-upload/${batch.id}/verifikasi-api`, {
      method: "POST",
      headers: { "Content-Type": "application/json", "Accept": "application/json" },
      body: JSON.stringify({ items, _token: (document.querySelector('meta[name="csrf-token"]') as any)?.content })
    })
    .then(async res => {
      const resData = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(resData.error || resData.message || "Terjadi kesalahan server");
      return resData;
    })
    .then(resData => {
       setShowSuccessModal(true);
    })
    .catch(err => setAlertDialog({ title: "Gagal", message: err.message || "Gagal menyimpan verifikasi.", variant: "danger" }))
    .finally(() => setSubmitting(false));
  };

  const handleFinalisasi = () => {
    if (!confirm("Anda yakin ingin memfinalisasi batch ini? Stok akan otomatis bertambah di histori.")) return;
    
    setSubmitting(true);
    fetch(`/api/stok-upload/${batch.id}/finalisasi-api`, {
      method: "POST",
      headers: { "Content-Type": "application/json", "Accept": "application/json" },
      body: JSON.stringify({ _token: (document.querySelector('meta[name="csrf-token"]') as any)?.content })
    })
    .then(async res => {
      const resData = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(resData.error || resData.message || "Terjadi kesalahan server");
      return resData;
    })
    .then(resData => {
       setAlertDialog({ 
         title: "Berhasil", 
         message: resData.message || "Berhasil difinalisasi!", 
         variant: "success",
         onConfirm: () => onClose() 
       });
    })
    .catch(err => setAlertDialog({ title: "Gagal", message: err.message || "Gagal memfinalisasi batch.", variant: "danger" }))
    .finally(() => setSubmitting(false));
  };

  // Helper to find Master Kode Label
  const getMasterLabel = (kode: string) => {
    const found = masterCodes.find((m: any) => m.kode === kode);
    return found ? `${found.kode} - ${found.nama_barang}` : kode;
  };

  const handleCloseSuccessModal = () => {
    setShowSuccessModal(false);
    fetchBatch(); // This will pull new data and move to step 4 automatically
  };

  return (
    <div className="space-y-6 animate-fade-in relative">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
          <div className="flex items-center gap-2 mb-1.5 flex-wrap">
            <span className="text-xs font-mono font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
              BATCH #{batch.id}
            </span>
            <span className="text-[11px] font-medium text-slate-400">
              {new Date(batch.upload_date || batch.created_at).toLocaleString('id-ID')}
            </span>
            <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
              ${batch.status === 'Selesai' ? 'bg-emerald-100 text-emerald-700' : 
                batch.status === 'Dibatalkan' ? 'bg-gray-100 text-gray-600' : 
                'bg-amber-100 text-amber-700'}`}>
              {batch.status}
            </span>
          </div>
          <h1 className="text-lg font-extrabold text-slate-800 tracking-tight">{batch.file_name_original}</h1>
          <p className="text-xs text-slate-500 mt-1">Diupload oleh {batch.user?.name || 'Sistem'}</p>
        </div>
        <button onClick={onClose} className="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 shadow-sm flex items-center gap-2 transition-colors">
          <ArrowLeft size={16} /> Tutup
        </button>
      </div>

      {/* Stepper Indicators */}
      <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm relative z-0">
        <div className="flex items-center justify-between relative">
          <div className="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-slate-100 rounded-full -z-10"></div>
          <div className="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-indigo-500 rounded-full -z-10 transition-all duration-500" style={{ width: `${((step - 1) / 3) * 100}%` }}></div>
          
          {stepLabels.map((label, i) => {
            const num = i + 1;
            const isDone = num < step || batch.status === 'Selesai';
            const isActive = num === step && batch.status !== 'Selesai';
            
            return (
              <div key={num} className="flex flex-col items-center">
                <div className={`w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm shadow-sm transition-all duration-300
                  ${isActive ? 'bg-indigo-600 text-white ring-4 ring-indigo-100 scale-110' : 
                    isDone ? 'bg-emerald-500 text-white' : 
                    'bg-slate-100 text-slate-400 border border-slate-200'}`}>
                  {isDone ? <Check size={18} strokeWidth={3} /> : num}
                </div>
                <span className={`mt-3 text-xs font-bold whitespace-nowrap hidden sm:block
                  ${isActive ? 'text-indigo-700' : isDone ? 'text-emerald-600' : 'text-slate-400'}`}>
                  {label}
                </span>
              </div>
            );
          })}
        </div>
      </div>

      {alertDialog && (
        <AlertDialog
          open={!!alertDialog}
          title={alertDialog.title}
          message={alertDialog.message}
          variant={alertDialog.variant}
          onClose={() => {
            if (alertDialog.onConfirm) alertDialog.onConfirm();
            setAlertDialog(null);
          }}
        />
      )}

      {showSuccessModal && typeof document !== 'undefined' && createPortal(
        <div 
          className="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in"
          style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0 }}
        >
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center transform animate-in zoom-in-95 duration-200">
            <div className="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-white shadow-sm">
              <Check size={32} strokeWidth={3} />
            </div>
            <h3 className="text-xl font-extrabold text-slate-800 tracking-tight">Verifikasi Disimpan!</h3>
            <p className="text-sm text-slate-500 mt-2">
              Data verifikasi Anda berhasil disimpan dengan aman.
            </p>
            <button 
              onClick={handleCloseSuccessModal}
              className="mt-6 w-full py-3 bg-indigo-600 text-white font-bold text-sm rounded-xl hover:bg-indigo-700 active:scale-95 transition-all shadow-md flex items-center justify-center gap-2"
            >
              Lanjut ke Finalisasi <ChevronRight size={16} />
            </button>
          </div>
        </div>,
        document.body
      )}

      {/* Step 2 Content: Pemeriksaan */}
      {step === 2 && (
        <div className="space-y-5 animate-fade-in">
           <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div className="bg-white border border-slate-200 rounded-xl p-5 text-center shadow-sm hover:border-slate-300 transition-colors">
                 <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Baris</span>
                 <span className="text-3xl font-extrabold text-slate-800 block mt-2">{stats.total_rows}</span>
              </div>
              <div className="bg-emerald-50 border border-emerald-100 rounded-xl p-5 text-center shadow-sm">
                 <span className="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Valid</span>
                 <span className="text-3xl font-extrabold text-emerald-700 block mt-2">{stats.valid_count}</span>
              </div>
              <div className="bg-rose-50 border border-rose-100 rounded-xl p-5 text-center shadow-sm">
                 <span className="text-[10px] font-bold text-rose-600 uppercase tracking-wider block">Perlu Perbaikan</span>
                 <span className="text-3xl font-extrabold text-rose-700 block mt-2">{stats.error_count}</span>
              </div>
              <div className="bg-white border border-slate-200 rounded-xl p-5 text-center shadow-sm hover:border-slate-300 transition-colors">
                 <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Sheet</span>
                 <span className="text-3xl font-extrabold text-slate-700 block mt-2">{batch.sheets_count}</span>
              </div>
           </div>

           {stats.error_count > 0 ? (
             <div className="bg-rose-50 border border-rose-200 rounded-xl p-8 text-center shadow-sm">
                <AlertCircle className="w-14 h-14 text-rose-500 mx-auto mb-4 opacity-80" />
                <h3 className="text-lg font-extrabold text-rose-900 tracking-tight">Data Memerlukan Perbaikan!</h3>
                <p className="text-sm text-rose-700 mt-2 max-w-lg mx-auto">
                  Sistem menemukan <strong>{stats.error_count} baris</strong> yang tidak valid. Silakan perbaiki file Excel Anda lalu upload ulang.
                </p>
             </div>
           ) : (
             <div className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                <div className="bg-slate-50 border-b border-slate-200 p-4 flex items-center justify-between">
                  <h3 className="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                    <ShieldCheck size={18} className="text-emerald-500" />
                    Semua Data Valid
                  </h3>
                </div>
                <div className="p-8 text-center bg-emerald-50/30">
                  <p className="text-sm text-slate-600 mb-6 font-medium">Data Excel Anda lolos pemeriksaan struktur dan format.</p>
                  <button onClick={() => {
                     // For UI simulation we can advance to step 3, but in real flow step 2 is already advanced.
                     fetchBatch(); // Reload handles correct step if it's actually step 3
                  }} className="px-6 py-3 bg-indigo-600 text-white rounded-lg font-bold text-sm hover:bg-indigo-700 shadow-md transition-all active:scale-95 flex items-center gap-2 mx-auto">
                    Lanjut Verifikasi Kode <ChevronRight size={16} />
                  </button>
                </div>
             </div>
           )}
        </div>
      )}

      {/* Step 3 Content: Verifikasi Kode */}
      {step === 3 && (
        <div className="space-y-5 animate-fade-in">
           <div className="bg-indigo-50 border border-indigo-100 p-4 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-indigo-200/50 rounded-full flex items-center justify-center text-indigo-700">
                  <Search size={20} />
                </div>
                <div>
                  <h3 className="text-sm font-extrabold text-indigo-900">Pencocokan Kode Barang</h3>
                  <p className="text-xs text-indigo-700 mt-0.5">Tentukan aksi untuk setiap barang: Setuju, Perbaiki, atau Tolak.</p>
                </div>
              </div>
              <div className="flex gap-4">
                <div className="text-center">
                  <span className="block text-[10px] font-bold text-indigo-500 uppercase">Menunggu</span>
                  <span className="block text-xl font-extrabold text-indigo-800">{stats.pending_count}</span>
                </div>
                <div className="w-px bg-indigo-200"></div>
                <div className="text-center">
                  <span className="block text-[10px] font-bold text-emerald-600 uppercase">Setuju</span>
                  <span className="block text-xl font-extrabold text-emerald-700">{stats.approved_count}</span>
                </div>
              </div>
           </div>

           <div className="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
             <div className="overflow-x-auto">
               <table className="w-full text-left text-sm">
                 <thead className="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 font-bold uppercase tracking-wider">
                   <tr>
                     <th className="px-4 py-3">Nama Barang Excel</th>
                     <th className="px-4 py-3">Aksi</th>
                     <th className="px-4 py-3 min-w-[300px]">Kode Persediaan Master</th>
                   </tr>
                 </thead>
                 <tbody className="divide-y divide-slate-100">
                   {details.all.map((row: any) => {
                     const isPending = row.status_verification === 'Pending';
                     const v = verifications[row.id] || { action: 'Setuju' };
                     
                     return (
                       <tr key={row.id} className="hover:bg-slate-50/50">
                         <td className="px-4 py-4">
                           <span className="font-bold text-slate-800 block">{row.nama_barang}</span>
                           <span className="text-xs text-slate-500 font-mono mt-1 block">Kode Excel: {row.kode_persediaan_excel || '-'}</span>
                         </td>
                         <td className="px-4 py-4">
                           <select 
                             className={`text-xs font-bold rounded-lg border px-3 py-2 outline-none focus:ring-2 transition-all
                               ${v.action === 'Setuju' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 focus:ring-emerald-200' : 
                                 v.action === 'Perbaiki' ? 'bg-amber-50 text-amber-700 border-amber-200 focus:ring-amber-200' : 
                                 'bg-rose-50 text-rose-700 border-rose-200 focus:ring-rose-200'}`}
                             value={v.action}
                             onChange={(e) => setVerifications({...verifications, [row.id]: { ...v, action: e.target.value }})}
                           >
                             <option value="Setuju">✓ Setuju</option>
                             <option value="Perbaiki">✎ Perbaiki</option>
                             <option value="Tolak">✕ Tolak</option>
                           </select>
                         </td>
                         <td className="px-4 py-4 relative">
                           {v.action === 'Perbaiki' ? (
                             <div className="relative">
                               <button 
                                 type="button"
                                 onClick={() => setDropdownOpen({...dropdownOpen, [row.id]: !dropdownOpen[row.id]})}
                                 className="w-full text-left px-3 py-2 text-xs border border-amber-300 bg-amber-50 rounded-lg flex items-center justify-between"
                               >
                                 <span className="truncate font-semibold text-amber-900">
                                   {v.kode_persediaan ? getMasterLabel(v.kode_persediaan) : "Pilih Kode Master..."}
                                 </span>
                                 <ChevronDown size={14} className="text-amber-600" />
                               </button>
                               
                               {dropdownOpen[row.id] && (
                                 <div className="absolute z-10 top-full left-0 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl overflow-hidden">
                                   <div className="p-2 border-b border-slate-100 bg-slate-50">
                                     <input 
                                       type="text" 
                                       placeholder="Cari nama atau kode..." 
                                       className="w-full text-xs px-3 py-1.5 border border-slate-200 rounded focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 outline-none"
                                       value={searchTerms[row.id] || ''}
                                       onChange={(e) => setSearchTerms({...searchTerms, [row.id]: e.target.value})}
                                       autoFocus
                                     />
                                   </div>
                                   <ul className="max-h-48 overflow-y-auto text-xs py-1">
                                     {masterCodes.filter((m: any) => 
                                       (m.kode + " " + m.nama_barang).toLowerCase().includes((searchTerms[row.id] || '').toLowerCase())
                                     ).map((m: any) => (
                                       <li 
                                         key={m.kode} 
                                         onClick={() => {
                                           setVerifications({...verifications, [row.id]: { ...v, kode_persediaan: m.kode }});
                                           setDropdownOpen({...dropdownOpen, [row.id]: false});
                                         }}
                                         className="px-3 py-2 hover:bg-indigo-50 cursor-pointer font-medium text-slate-700 hover:text-indigo-700"
                                       >
                                         <span className="font-mono font-bold text-slate-500 mr-2">{m.kode}</span>
                                         {m.nama_barang}
                                       </li>
                                     ))}
                                   </ul>
                                 </div>
                               )}
                             </div>
                           ) : v.action === 'Setuju' ? (
                             <span className="text-xs font-semibold text-slate-600 px-3 py-2 block bg-slate-50 rounded-lg border border-slate-100 truncate">
                               {getMasterLabel(row.verified_kode_persediaan || row.kode_persediaan_excel)}
                             </span>
                           ) : (
                             <span className="text-xs font-semibold text-rose-500 italic block mt-1 px-3">
                               Baris ini akan diabaikan
                             </span>
                           )}
                         </td>
                       </tr>
                     );
                   })}
                 </tbody>
               </table>
             </div>
             
             <div className="p-5 bg-slate-50 border-t border-slate-200 flex justify-end">
               <button 
                 onClick={handleSaveVerifications}
                 disabled={submitting}
                 className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg font-bold text-sm shadow-md hover:bg-indigo-700 transition-colors flex items-center gap-2 disabled:opacity-70"
               >
                 {submitting ? <div className="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full" /> : <Save size={16} />}
                 Simpan Verifikasi
               </button>
             </div>
           </div>
        </div>
      )}

      {/* Step 4 Content: Review & Finalisasi */}
      {step === 4 && (
        <div className="space-y-6 animate-fade-in">
           <div className="bg-gradient-to-br from-indigo-600 to-blue-700 rounded-2xl p-8 text-center shadow-lg relative overflow-hidden">
              <div className="relative z-10">
                <div className="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center text-white mx-auto mb-4 backdrop-blur-sm">
                  <ShieldCheck size={32} />
                </div>
                <h2 className="text-2xl font-extrabold text-white tracking-tight">Siap Difinalisasi</h2>
                <p className="text-indigo-100 mt-2 max-w-md mx-auto">
                  Anda telah memverifikasi {stats.approved_count} baris data persediaan.
                  Setelah finalisasi, stok pada sistem akan otomatis bertambah sesuai data yang disetujui.
                </p>
                
                <div className="mt-8 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-6 max-w-lg mx-auto flex justify-between items-center text-left">
                  <div>
                    <span className="text-indigo-200 text-xs font-bold uppercase tracking-wider block">Total Nilai Pembelanjaan</span>
                    <span className="text-3xl font-extrabold text-white block mt-1">
                      Rp{stats.total_value.toLocaleString('id-ID')}
                    </span>
                  </div>
                  <div className="text-right">
                    <span className="text-indigo-200 text-xs font-bold uppercase tracking-wider block">Item Masuk</span>
                    <span className="text-2xl font-extrabold text-white block mt-1">
                      {stats.approved_count}
                    </span>
                  </div>
                </div>

                <div className="mt-8">
                  <button 
                    onClick={handleFinalisasi}
                    disabled={submitting || !stats.can_finalize}
                    className="px-8 py-3.5 bg-white text-indigo-700 rounded-full font-extrabold text-sm shadow-xl hover:bg-indigo-50 transition-all active:scale-95 flex items-center gap-2 mx-auto disabled:opacity-70"
                  >
                    {submitting ? "Memproses..." : "Finalisasi & Tambah Stok Sekarang"}
                  </button>
                  {!stats.can_finalize && (
                    <p className="text-xs text-amber-200 mt-3 font-semibold">Terdapat baris yang belum selesai diverifikasi.</p>
                  )}
                </div>
              </div>
           </div>
        </div>
      )}
    </div>
  );
};
